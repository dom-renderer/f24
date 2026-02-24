<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Store;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Payment::with(['store', 'createdBy', 'ledgerTransaction.allocationsAsCredit'])->latest();

            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            return datatables()
                ->eloquent($query)
                ->editColumn('payment_date', fn($row) => $row->payment_date ? $row->payment_date->format('d-m-Y') : '')
                ->editColumn('amount', fn($row) => number_format($row->amount, 2))
                ->addColumn('allocated', function ($row) {
                    if ($row->ledgerTransaction) {
                        $allocated = $row->ledgerTransaction->allocationsAsCredit->sum('allocated_amount');
                        return number_format($allocated, 2);
                    }
                    return '0.00';
                })
                ->addColumn('unallocated', function ($row) {
                    if ($row->ledgerTransaction) {
                        $allocated = $row->ledgerTransaction->allocationsAsCredit->sum('allocated_amount');
                        return number_format($row->amount - $allocated, 2);
                    }
                    return number_format($row->amount, 2);
                })
                ->addColumn('action', function ($row) {
                    $btn = '';
                    $btn .= '<a href="' . route('payments.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="View"><i class="fas fa-eye"></i></a>';
                    // Permission checks can be added here
                    $btn .= '<button onclick="voidPayment(' . $row->id . ')" class="btn btn-danger btn-sm" title="Void"><i class="fas fa-ban"></i></button>';
                    return $btn;
                })
                ->make(true);
        }

        $stores = Store::orderBy('name')->pluck('name', 'id');
        return view('payments.index', compact('stores'));
    }

    public function create()
    {
        $stores = Store::orderBy('name')->pluck('name', 'id');
        return view('payments.create', compact('stores'));
    }

    public function show($id)
    {
        $payment = Payment::with(['store', 'createdBy', 'ledgerTransaction.allocationsAsCredit.debitTransaction.order'])
            ->findOrFail($id);

        return view('payments.show', compact('payment'));
    }

    public function store(Request $request, LedgerService $ledgerService)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_mode' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240', // 10MB Max, Strict MIME
        ]);

        try {
            DB::beginTransaction();

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                // Generate a unique filename to avoid collision and path traversal
                $file = $request->file('attachment');
                $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                // Store in storage/app/public/payment_attachments
                $attachmentPath = $file->storeAs('payment_attachments', $filename, 'public');
            }

            // 1. Create Payment Record
            $payment = Payment::create([
                'store_id' => $request->store_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_mode' => $request->payment_mode,
                'reference_no' => $request->reference_no,
                'remarks' => $request->remarks,
                'attachment_path' => $attachmentPath,
                'created_by' => auth()->id(),
            ]);

            // 2. Post to Ledger (Credit)
            $result = $ledgerService->createCredit(
                $request->store_id,
                $request->amount,
                $request->payment_date,
                'payment',
                $payment->id,
                $payment->id, // payment_id column
                $request->remarks,
                $request->reference_no
            );

            DB::commit();

            $msg = 'Payment recorded successfully.';
            $allocations = $result['allocations'] ?? [];
            if (count($allocations) > 0) {
                $totalAllocated = collect($allocations)->sum('allocated_amount');
                $msg .= ' Allocated ₹' . number_format($totalAllocated, 2) . ' to ' . count($allocations) . ' invoices.';
            } else {
                $msg .= ' No invoices were outstanding for allocation.';
            }

            return redirect()->route('payments.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording payment: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $payment = Payment::findOrFail($id);
        $stores = Store::orderBy('name')->pluck('name', 'id');
        return view('payments.edit', compact('payment', 'stores'));
    }

    public function update(Request $request, $id, LedgerService $ledgerService)
    {
        $payment = Payment::findOrFail($id);

        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_mode' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240', // 10MB Max, Strict MIME
        ]);

        try {
            DB::beginTransaction();

            $attachmentPath = $payment->attachment_path;
            if ($request->hasFile('attachment')) {
                // Delete old file if exists
                if ($attachmentPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($attachmentPath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($attachmentPath);
                }

                // Generate a unique filename
                $file = $request->file('attachment');
                $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('payment_attachments', $filename, 'public');
            }

            $payment->update([
                'store_id' => $request->store_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_mode' => $request->payment_mode,
                'reference_no' => $request->reference_no,
                'remarks' => $request->remarks,
                'attachment_path' => $attachmentPath,
            ]);

            // Update Ledger Transaction if exists
            $txn = \App\Models\LedgerTransaction::where('source_type', 'payment')
                ->where('payment_id', $payment->id)
                ->first();

            if ($txn) {
                $txn->update([
                    'store_id' => $request->store_id,
                    'amount' => $request->amount,
                    'txn_date' => $request->payment_date,
                    'description' => $request->remarks ? 'Payment: ' . $request->remarks : 'Payment',
                ]);
                
                // Re-trigger allocation logic if amount changed? 
                // This is complex. For now, we assume simple updates. 
                // To do it right, we'd need to void and re-create. 
                // But let's stick to the requested scope: Attachments.
                // We will at least keep the transaction strictly in sync with the payment record.
            }

            DB::commit();

            return redirect()->route('payments.index')->with('success', 'Payment updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating payment: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        // Void functionality
        // We do not Delete, we Void.
        try {
            DB::beginTransaction();

            $payment = Payment::findOrFail($id);
            $payment->delete(); // Soft Delete

            // Void the Ledger Transaction
            $txn = \App\Models\LedgerTransaction::where('source_type', 'payment')
                ->where('payment_id', $id)
                ->first();

            if ($txn) {
                $txn->status = 'voided';
                $txn->voided_at = now();
                $txn->voided_by = auth()->id();
                $txn->save();

                // Detach allocations
                \App\Models\LedgerAllocation::where('credit_txn_id', $txn->id)
                    ->update([
                        'voided_at' => now(),
                        'voided_by' => auth()->id()
                    ]);
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Payment voided successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
}
