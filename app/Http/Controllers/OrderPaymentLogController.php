<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPaymentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderPaymentLogController extends Controller
{
    public function store(Request $request, \App\Services\LedgerService $ledgerService)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'type' => 'required|in:0,1',
            'amount' => 'required|numeric|min:0.01',
            'text' => 'nullable|string',
        ]);

        // Additional validation: Cannot deduct more than current deposit
        $order = Order::findOrFail($request->order_id);
        if ($request->type == 1) { // Deduct
            if ($request->amount > $order->amount_collected) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Cannot deduct more than current deposit (' . number_format($order->amount_collected, 2) . ').'
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $log = OrderPaymentLog::create([
                'order_id' => $request->order_id,
                'received_by_user_id' => Auth::id(),
                'type' => $request->type,
                'amount' => $request->amount,
                'text' => $request->text,
            ]);

            $storeId = $order->receiver_store_id; // Customer

            if ($storeId) {
                if ($request->type == 0) {
                    $ledgerService->createCredit(
                        $storeId,
                        $request->amount,
                        now()->format('Y-m-d'),
                        'order_payment_log',
                        $log->id,
                        null, // Not a manual payment ID
                        'Payment Collected on Order #' . $order->order_number . ($request->text ? ': ' . $request->text : ''),
                        $order->order_number
                    );
                } else {
                    $ledgerService->createDebit(
                        $storeId,
                        $request->amount,
                        now()->format('Y-m-d'),
                        'order_payment_log',
                        $log->id,
                        $order->id,
                        'Payment Correction/Deduction on Order #' . $order->order_number . ($request->text ? ': ' . $request->text : '')
                    );
                }
            }
            
            $this->updateOrderAmountCollected($request->order_id);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment log added successfully.',
                'log' => $log->load('user'),
                'formatted_date' => $log->created_at->format('d M Y, h:i A')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Something went wrong: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $log = OrderPaymentLog::findOrFail($id);
            $orderId = $log->order_id;

            $txn = \App\Models\LedgerTransaction::where('source_type', 'order_payment_log')
                ->where('source_id', $id)
                ->first();

            if ($txn) {
                $txn->status = 'voided';
                $txn->voided_at = now();
                $txn->voided_by = auth()->id();
                $txn->save();

                // Void allocations
                \App\Models\LedgerAllocation::where('credit_txn_id', $txn->id)
                    ->orWhere('debit_txn_id', $txn->id)
                    ->update([
                        'voided_at' => now(),
                        'voided_by' => auth()->id()
                    ]);
            }

            $log->delete();

            $this->updateOrderAmountCollected($orderId);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Payment log deleted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Something went wrong: ' . $e->getMessage()]);
        }
    }

    private function updateOrderAmountCollected($orderId)
    {
        $order = Order::find($orderId);
        if ($order) {
            $totalAdded = OrderPaymentLog::where('order_id', $orderId)->where('type', 0)->sum('amount');
            $totalDeducted = OrderPaymentLog::where('order_id', $orderId)->where('type', 1)->sum('amount');
            
            $order->amount_collected = $totalAdded - $totalDeducted;
            $order->save();
        }
    }
}
