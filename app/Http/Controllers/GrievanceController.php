<?php

namespace App\Http\Controllers;

use App\Models\Grievance;
use App\Models\GrievanceItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GrievanceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Grievance Reporting";
        $page_description = "Manage grievance reports here";
        $statuses = [
            0 => 'Pending',
            1 => 'Resolved',
            2 => 'Rejected'
        ];
        return view('grievance-reporting.index', compact('page_title', 'page_description', 'statuses'));
    }

    public function ajax(Request $request)
    {
        $data = Grievance::query()->with(['order', 'reportedBy']);

        return datatables()
            ->eloquent($data)
            ->addColumn('order_number', fn($row) => $row->order->order_number ?? 'N/A')
            ->addColumn('reported_by_name', fn($row) => $row->reportedBy->name ?? 'N/A')
            ->addColumn('status_label', function ($row) {
                if (auth()->user()->can('grievance-reporting.status-change')) {
                    $statuses = [
                        0 => 'Pending',
                        1 => 'Resolved',
                        2 => 'Rejected'
                    ];
                    $options = '';
                    foreach ($statuses as $val => $label) {
                        $selected = $row->status == $val ? 'selected' : '';
                        $options .= "<option value='{$val}' {$selected}>{$label}</option>";
                    }
                    return "<select class='form-select form-select-sm status-select' data-oldstatus='{$row->status}' data-id='{$row->id}'>{$options}</select>";
                }
                $labels = [
                    0 => '<span class="badge bg-warning text-dark">Pending</span>',
                    1 => '<span class="badge bg-success">Resolved</span>',
                    2 => '<span class="badge bg-danger">Rejected</span>',
                ];
                return $labels[$row->status] ?? '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('action', function ($row) {
                $action = '';
                if (auth()->user()->can('grievance-reporting.show')) {
                    $action .= '<a href="' . route('grievance-reporting.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="View">Show</a>';
                }
                if (auth()->user()->can('grievance-reporting.edit')) {
                    $action .= '<a href="' . route('grievance-reporting.edit', $row->id) . '" class="btn btn-warning btn-sm me-1" title="Edit">Edit</a>';
                }
                if (auth()->user()->can('grievance-reporting.destroy')) {
                    $action .= '<button onclick="deleteGrievance(' . $row->id . ')" class="btn btn-danger btn-sm me-1" title="Delete">Delete</button>';
                }
                return $action;
            })
            ->editColumn('created_at', function ($row) {
                return date('d-m-Y H:i', strtotime($row->created_at));
            })
            ->rawColumns(['status_label', 'action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        $page_title = "Report Grievance";
        $page_description = "Create a new grievance report";
        $orders = Order::orderBy('created_at', 'desc')->get();
        return view('grievance-reporting.create', compact('page_title', 'page_description', 'orders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.issue_type' => 'required|in:not_received,partially_received,defective',
            'items.*.note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $grievance = Grievance::create([
                'order_id' => $request->order_id,
                'reported_by' => Auth::id(),
                'status' => 0, // Pending
                'remarks' => $request->remarks,
            ]);

            foreach ($request->items as $item) {
                GrievanceItem::create([
                    'grievance_id' => $grievance->id,
                    'order_item_id' => $item['order_item_id'],
                    'quantity' => $item['quantity'],
                    'issue_type' => $item['issue_type'],
                    'note' => $item['note'],
                ]);
            }

            DB::commit();
            return redirect()->route('grievance-reporting.index')->with('success', 'Grievance reported successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $grievance = Grievance::with(['order', 'reportedBy', 'items.orderItem.product', 'items.orderItem.unit'])->findOrFail($id);
        $page_title = "Grievance Details";
        $page_description = "View grievance report details";
        return view('grievance-reporting.show', compact('grievance', 'page_title', 'page_description'));
    }

    public function edit($id)
    {
        $grievance = Grievance::with(['items.orderItem.product'])->findOrFail($id);
        $page_title = "Edit Grievance";
        $page_description = "Edit grievance report details";
        $orders = Order::orderBy('created_at', 'desc')->get();

        // Get items for the selected order
        $orderItems = OrderItem::with(['product', 'unit'])
            ->where('order_id', $grievance->order_id)
            ->get();

        return view('grievance-reporting.edit', compact('grievance', 'page_title', 'page_description', 'orders', 'orderItems'));
    }

    public function update(Request $request, $id)
    {
        $grievance = Grievance::findOrFail($id);

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.issue_type' => 'required|in:not_received,partially_received,defective',
            'items.*.note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $grievance->update([
                'order_id' => $request->order_id,
                'remarks' => $request->remarks,
            ]);

            GrievanceItem::where('grievance_id', $grievance->id)->forceDelete();

            foreach ($request->items as $item) {
                GrievanceItem::create([
                    'grievance_id' => $grievance->id,
                    'order_item_id' => $item['order_item_id'],
                    'quantity' => $item['quantity'],
                    'issue_type' => $item['issue_type'],
                    'note' => $item['note'],
                ]);
            }

            DB::commit();
            return redirect()->route('grievance-reporting.index')->with('success', 'Grievance updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, \App\Services\LedgerService $ledgerService)
    {
        $request->validate([
            'id' => 'required|exists:grievances,id',
            'status' => 'required|in:0,1,2',
        ]);

        try {
            DB::beginTransaction();

            $grievance = Grievance::with(['order', 'items.orderItem'])->findOrFail($request->id);
            $oldStatus = $grievance->status;
            $grievance->status = $request->status;
            $grievance->save();

            // If status changed to Resolved (1), Create Credit Note
            if ($request->status == 1 && $oldStatus != 1) {

                // Check if already credited
                $exists = \App\Models\LedgerTransaction::where('source_type', 'sales_return')
                    ->where('source_id', $grievance->id)
                    ->where('type', 'credit')
                    ->exists();

                if (!$exists) {
                    $totalCreditAmount = 0;
                    $order = $grievance->order;

                    // Tax Calculation Factors
                    // Assuming tax is on top of unit_price. 
                    // effective_tax_rate = (cgst + sgst) / 100
                    // But we need to check if tax was applied.

                    $taxPercent = ($order->cgst_percentage ?? 0) + ($order->sgst_percentage ?? 0);

                    foreach ($grievance->items as $gItem) {
                        $orderItem = $gItem->orderItem;
                        if (!$orderItem)
                            continue;

                        $qty = $gItem->quantity;
                        $price = $orderItem->unit_price;

                        // Apply Discount if it was percentage
                        // If discunt_type == 0 (Percentage)
                        if ($order->discunt_type == 0 && $order->discount_amount > 0) {
                            // discount_amount here is value or percent?
                            // In OrderController: $discountValue = $subtotal * ($discountValue / 100);
                            // So 'discount_amount' column in DB likely stores the percentage value if type is 0?
                            // Checking OrderController: 'discount_amount' => floatval($request->discount_amount ?? 0)
                            // Yes, if type 0, it stores the percentage.

                            $discountMultiplier = (1 - ($order->discount_amount / 100));
                            $price = $price * $discountMultiplier;
                        }

                        $lineTotal = $price * $qty;
                        $totalCreditAmount += $lineTotal;
                    }

                    // Add Tax
                    $taxAmount = $totalCreditAmount * ($taxPercent / 100);
                    $finalCreditAmount = $totalCreditAmount + $taxAmount;

                    if ($finalCreditAmount > 0) {
                        // Store ID from Order (Customer)
                        $storeId = $order->receiver_store_id; // Customer Store

                        if ($storeId) {
                            $ledgerService->createCredit(
                                $storeId,
                                $finalCreditAmount,
                                now()->format('Y-m-d'),
                                'sales_return',
                                $grievance->id,
                                null,
                                'Grievance Resolved #' . $grievance->id,
                                $grievance->order->order_number ?? null
                            );
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Grievance Status Update Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Something went wrong: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            Grievance::findOrFail($id)->delete();
            return response()->json(['status' => true, 'message' => 'Grievance deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }

    public function getOrderItems($orderId)
    {
        $items = OrderItem::with(['product', 'unit'])
            ->where('order_id', $orderId)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product->name ?? 'N/A',
                    'unit_name' => $item->unit->name ?? 'N/A',
                    'quantity' => $item->quantity,
                ];
            });
        return response()->json($items);
    }
}
