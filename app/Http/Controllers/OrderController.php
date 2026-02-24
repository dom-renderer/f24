<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderCategory;
use App\Models\OrderProduct;
use App\Models\OrderProductUnit;
use App\Models\UnitPriceTier;
use App\Models\UnitDiscountTier;
use App\Models\Store;
use App\Models\User;
use App\Models\Utencil;
use App\Models\OrderUtencil;
use App\Models\OrderUtencilHistory;
use App\Models\HandlingInstruction;
use App\Models\Vehicle;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Orders";
        $page_description = "Manage orders here";
        $statuses = Order::getStatuses();
        $stores = Store::orderBy('name')->pluck('name', 'id');
        return view('orders.index', compact('page_title', 'page_description', 'statuses', 'stores'));
    }

    public function ajax(Request $request)
    {
        $data = Order::query()->with(['senderStore', 'receiverStore', 'createdBy'])->latest();

        if ($request->status !== null && $request->status !== '') {
            $data->where('status', $request->status);
        }

        if ($request->sender_store_id) {
            $data->where('sender_store_id', $request->sender_store_id);
        }

        if ($request->receiver_store_id) {
            $data->where('receiver_store_id', $request->receiver_store_id);
        }

        if ($request->date_range) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) == 2) {
                $start = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $end = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                $data->whereBetween('created_at', [$start, $end]);
            }
        }

        if ($request->delivery_date_range) {
            $dates = explode(' - ', $request->delivery_date_range);
            if (count($dates) == 2) {
                $start = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $end = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                $data->whereBetween('delivery_schedule_from', [$start, $end]);
            }
        }

        return datatables()
            ->eloquent($data)
            ->addColumn('sender', fn($row) => $row->senderStore->name ?? 'N/A')
            ->addColumn('receiver', function ($row) {
                if ($row->order_type === 'dealer') {
                    $dealer = User::find($row->bill_to_id);
                    return $dealer ? $dealer->name . ' (Dealer)' : 'N/A';
                }
                return $row->receiverStore->name ?? 'N/A';
            })
            ->editColumn('delivery_schedule_from', function ($row) {
                return $row->delivery_schedule_from ? (date('d-m-Y H:i', strtotime($row->delivery_schedule_from)) . ' - ' . date('H:i', strtotime($row->delivery_schedule_to))) : 'N/A';
            })
            ->addColumn('created_by_name', fn($row) => $row->createdBy->name ?? 'N/A')
            ->addColumn('status_label', function ($row) {
                if (auth()->user()->can('orders.status-change')) {
                    $options = '';
                    foreach (Order::getStatuses() as $val => $label) {
                        $selected = $row->status == $val ? 'selected' : '';
                        $options .= "<option value='{$val}' {$selected}>{$label}</option>";
                    }
                    return "<select class='form-select form-select-sm status-select' data-oldstatus='{$row->status}' data-id='{$row->id}'>{$options}</select>";
                }
                return $row->status_label;
            })
            ->addColumn('action', function ($row) {
                $action = '';
                if (auth()->user()->can('orders.show')) {
                    $action .= '<a href="' . route('orders.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="View">Show</a>';
                }
                if (auth()->user()->can('orders.edit')) {
                    $action .= '<a href="' . route('orders.edit', $row->id) . '" class="btn btn-warning btn-sm me-1" title="Edit">Edit</a>';
                }
                if (auth()->user()->can('orders.destroy')) {
                    $action .= '<button onclick="deleteOrder(' . $row->id . ')" class="btn btn-danger btn-sm me-1" title="Delete">Delete</button>';
                }
                if (auth()->user()->can('orders.reorder')) {
                    $action .= '<a href="' . route('orders.reorder', $row->id) . '" class="btn btn-secondary btn-sm me-1" title="Reorder">Reorder</a>';
                }

                if ($row->order_type == 'franchise' || $row->order_type == 'dealer') {
                    $action .= '<a href="' . route('orders.download-invoice', $row->id) . '" class="btn btn-primary btn-sm me-1" title="Download Invoice">Purchase Order</a>';
                    if (!in_array($row->status, [Order::STATUS_PENDING, Order::STATUS_CANCELLED])) {
                        $action .= '<a href="' . route('orders.download-invoice', $row->id) . '?type=invoice" class="btn btn-primary btn-sm me-1" title="Download Invoice">Sales Invoice</a>';
                    }
                } else {
                    $action .= '<a href="' . route('orders.download-invoice', $row->id) . '" class="btn btn-primary btn-sm me-1" title="Download Invoice">Sales Invoice</a>';
                }

                $action .= '<a href="' . route('orders.download-challan', ['id' => $row->id, 'type' => 'wp']) . '" class="btn btn-success btn-sm" title="Download Item-list Without Price">Challan with Price</a>';
                $action .= '<a href="' . route('orders.download-challan', ['id' => $row->id, 'type' => 'wop']) . '" class="btn btn-success btn-sm" title="Download Item-list With Price">Challan Without Price </a>';

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
        $page_title = "Create Order";
        $page_description = "Create a new order";
        $stores = Store::orderBy('name')->pluck('name', 'id');
        $storesWithType = Store::with('storetype')->orderBy('name')->get();
        $categories = OrderCategory::where('status', 1)->withCount('products')->orderByDesc('products_count')->pluck('name', 'id');
        $dealers = User::role('Dealer')->orderBy('name')->pluck('name', 'id');
        $users = User::orderBy('name')->pluck('name', 'id');
        $factories = Store::orderBy('name')->pluck('name', 'id');
        $drivers = User::role('Driver')->orderBy('name')->pluck('name', 'id');
        $setting = \App\Models\Setting::first();
        $cgstPercentage = $setting->cgst_percentage ?? 0;
        $sgstPercentage = $setting->sgst_percentage ?? 0;
        $utencils = Utencil::orderBy('name')->pluck('name', 'id');
        $handlingInstructions = HandlingInstruction::orderBy('name')->pluck('name', 'id');
        $vehicles = Vehicle::orderBy('name')->get();

        $otherItems = \App\Models\OtherItem::where('status', 1)->orderBy('name')->pluck('name', 'id');
        $taxSlabs = \App\Models\TaxSlab::where('status', 1)->get();

        return view('orders.create', compact(
            'page_title',
            'page_description',
            'stores',
            'storesWithType',
            'categories',
            'dealers',
            'users',
            'factories',
            'cgstPercentage',
            'sgstPercentage',
            'utencils',
            'handlingInstructions',
            'drivers',
            'vehicles',
            'otherItems',
            'taxSlabs'
        ));
    }

    public function getItemDetails(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        $item = null;
        if ($type == 'other_item') {
            $item = \App\Models\OtherItem::with('taxSlab')->find($id);
        }

        if ($item) {
            $taxSlab = $item->taxSlab;
            $pricePP = $item->price_per_piece ?? 0;
            $cgst = $taxSlab ? (float) $taxSlab->cgst : 0;
            $sgst = $taxSlab ? (float) $taxSlab->sgst : 0;
            $taxSlabId = $taxSlab ? $taxSlab->id : null;

            if (($item->price_includes_tax ?? 0) == 0) {
                $pricePP = $pricePP + (($pricePP * ($cgst + $sgst)) / 100);
            }

            return response()->json([
                'price' => $pricePP,
                'name' => $item->name,
                'pricing_type' => $item->pricing_type ?? 'fixed',
                'price_includes_tax' => (int) ($item->price_includes_tax ?? 0),
                'cgst_percent' => $cgst,
                'sgst_percent' => $sgst,
                'tax_slab_id' => $taxSlabId,
            ]);
        }

        return response()->json(['price' => 0, 'pricing_type' => 'fixed', 'price_includes_tax' => 0, 'cgst_percent' => 0, 'sgst_percent' => 0, 'tax_slab_id' => null]);
    }

    public function store(Request $request, \App\Services\LedgerService $ledgerService)
    {
        // Map "Collect Amount on Delivery" to payment_received flag for validation & logging
        if ($request->has('collect_on_delivery')) {
            $request->merge(['payment_received' => 1]);
        }

        $rules = [
            'order_type' => 'required|in:company,franchise,dealer',
            'sender_store_id' => 'required|exists:stores,id',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:order_products,id',
            'items.*.unit_id' => 'required|exists:order_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_type' => 'required|in:0,1',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'payment_received' => 'nullable|boolean',
            'amount_collected' => 'required_if:payment_received,1|nullable|numeric|min:0',
            'billing_name' => 'nullable|string|max:255',
            'billing_contact_number' => 'nullable|string|max:20',
            'billing_address_1' => 'nullable|string|max:500',
            'billing_address_2' => 'nullable|string|max:500',
            'billing_pincode' => 'nullable|string|max:10',
            'billing_gst_in' => 'nullable|string|max:50',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_contact_number' => 'nullable|string|max:20',
            'shipping_address_1' => 'nullable|string|max:500',
            'shipping_address_2' => 'nullable|string|max:500',
            'shipping_pincode' => 'nullable|string|max:10',
            'shipping_gst_in' => 'nullable|string|max:50',
            'billing_latitude' => 'nullable|numeric',
            'billing_longitude' => 'nullable|numeric',
            'billing_google_map_link' => 'nullable|string',
            'shipping_latitude' => 'nullable|numeric',
            'shipping_longitude' => 'nullable|numeric',
            'shipping_google_map_link' => 'nullable|string',
            'delivery_user' => 'required|exists:users,id',
            'delivery_date' => 'required|date',
            'time_slot' => 'required|string',
            'delivery_address' => 'required|string|max:1000',
            'delivery_link' => 'nullable|string|max:1000',
            'handling_instructions' => 'nullable|array',
            'handling_instructions.*' => 'exists:handling_instructions,id',
            'handling_note' => 'nullable|string',
            'other_items' => 'nullable|array',
            'other_items.*.other_item_id' => 'required_with:other_items|exists:other_items,id',
            'other_items.*.quantity' => 'required_with:other_items|numeric|min:0.01',
            'other_items.*.unit_price' => 'required_with:other_items|numeric|min:0',
            'other_items.*.tax_slab_id' => 'nullable|exists:tax_slabs,id',
        ];

        // Utencils sent on create
        $rules['utencils'] = 'nullable|array';
        $rules['utencils.*.utencil_id'] = 'required_with:utencils|exists:utencils,id';
        $rules['utencils.*.quantity'] = 'required_with:utencils|numeric|min:0.01';
        $rules['utencils.*.note'] = 'nullable|string|max:255';

        if (in_array($request->order_type, ['company', 'franchise'])) {
            $rules['receiver_store_id'] = 'required|exists:stores,id|different:sender_store_id';
        }

        if ($request->has('status') && in_array($request->status, [2, 3])) {
            $rules['delivery_user'] = 'required|exists:users,id';
            $rules['delivery_date'] = 'required|date';
            $rules['time_slot'] = 'required|string';
        } else {
            $rules['delivery_user'] = 'nullable|exists:users,id';
            $rules['delivery_date'] = 'nullable|date';
            $rules['time_slot'] = 'nullable|string';
        }

        if ($request->has('for_customer') && $request->for_customer) {
            $rules['customer_first_name'] = 'required|string|max:255';
        }

        $rules['vehicle_id'] = 'nullable|exists:vehicles,id';

        // Utencils validation for update (new sends + returns)
        $rules['utencils'] = 'nullable|array';
        $rules['utencils.*.utencil_id'] = 'required_with:utencils|exists:utencils,id';
        $rules['utencils.*.quantity'] = 'required_with:utencils|numeric|min:0.01';
        $rules['utencils.*.note'] = 'nullable|string|max:255';

        $rules['utencil_returns'] = 'nullable|array';
        $rules['utencil_returns.*.quantity'] = 'nullable|numeric|min:0.01';
        $rules['utencil_returns.*.note'] = 'nullable|string|max:255';

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $subtotal2 = 0;
            $cgstAmount = 0;
            $sgstAmount = 0;
            $taxValue = 0;

            // Per-product tax calculation using each product's own tax slab
            foreach ($request->items as $item) {
                $gePrice = floatval($item['ge_price']);
                $giPrice = floatval($item['gi_price']);
                $subtotal += $gePrice * floatval($item['quantity']);
                $itemTotal = floatval($item['unit_price']) * floatval($item['quantity']);
                $subtotal2 += $itemTotal;

                $productModel = OrderProduct::with('taxSlab')->find($item['product_id']);
                $itemCgst = (float) ($productModel->taxSlab->cgst ?? 0);
                $itemSgst = (float) ($productModel->taxSlab->sgst ?? 0);
                $baseTotal = $gePrice * floatval($item['quantity']);
                $baseTotal2 = $giPrice * floatval($item['quantity']);

                $cgstAmount += $baseTotal2 * ($itemCgst / 100);
                $sgstAmount += $baseTotal2 * ($itemSgst / 100);
            }

            $processNonProductItem = function ($item) use (&$subtotal, &$subtotal2, &$cgstAmount, &$sgstAmount) {
                $qty = floatval($item['quantity'] ?? 0);
                $enteredPrice = floatval($item['unit_price'] ?? 0);
                $priceIncludesTax = isset($item['price_includes_tax']) ? (int) $item['price_includes_tax'] : 0;

                $slabId = $item['tax_slab_id'] ?? null;
                $cgstPercent = 0;
                $sgstPercent = 0;

                if ($slabId) {
                    $taxSlabInfo = \App\Models\TaxSlab::find($slabId);
                    if ($taxSlabInfo) {
                        $cgstPercent = (float) $taxSlabInfo->cgst;
                        $sgstPercent = (float) $taxSlabInfo->sgst;
                    }
                }

                $totalTaxPercent = $cgstPercent + $sgstPercent;
                $unitBasePrice = 0;
                $unitTaxAmount = 0;

                if ($priceIncludesTax === 1 && $totalTaxPercent > 0) {
                    $unitBasePrice = $enteredPrice / (1 + $totalTaxPercent / 100);
                    $unitTaxAmount = $enteredPrice - $unitBasePrice;
                } else {
                    $unitBasePrice = $enteredPrice;
                    $unitTaxAmount = $enteredPrice * ($totalTaxPercent / 100);
                }

                $amt = $qty * $unitBasePrice;
                $totalTax = $unitTaxAmount * $qty;

                $itemCgstAmt = 0;
                $itemSgstAmt = 0;
                if ($totalTax > 0 && $totalTaxPercent > 0) {
                    $itemCgstAmt = $totalTax * (($cgstPercent) / $totalTaxPercent);
                    $itemSgstAmt = $totalTax * (($sgstPercent) / $totalTaxPercent);
                }

                $subtotal += $amt;
                $subtotal2 += $amt;
                return $amt;
            };

            if ($request->has('other_items') && is_array($request->other_items)) {
                foreach ($request->other_items as $oi) {
                    if (empty($oi['other_item_id']))
                        continue;
                    $processNonProductItem($oi);
                }
            }

            // Sum additional charges (if any)
            $additionalChargesTotal = 0;
            if (is_array($request->additional_charges)) {
                foreach ($request->additional_charges as $charge) {
                    if (!empty($charge['title']) && isset($charge['amount'])) {
                        $additionalChargesTotal += floatval($charge['amount']);
                    }
                }
            }

            $discountValue = floatval($request->discount_amount ?? 0);
            if ($request->discount_type == 0 && $discountValue > 0) {
                $discountValue = $subtotal * ($discountValue / 100);
            }

            $afterDiscount = $subtotal - $discountValue + $additionalChargesTotal;

            $netAmount = $afterDiscount + ($cgstAmount + $sgstAmount);

            $billToType = 'factory';
            $billToId = 1;

            if ($request->order_type === 'franchise') {
                $billToType = 'store';
                $billToId = $request->receiver_store_id;
            } elseif ($request->order_type === 'dealer') {
                $billToType = 'dealer';
                $billToId = $request->dealer_id;
            } elseif ($request->order_type === 'company') {
                // For company orders, usually bill to customer (User) or Guest
                // If for_customer is true, we might want to bill to the customer if they exist as a User, 
                // but the current logic seems to treat 'Bill To' as the entity responsible for the order internally?
                // The requirement says "In Sales Invoice, Bill to will be customer". 
                // We'll set it to 'user' if a user is selected, or default. 
                // For now, let's keep 'factory' as default fallback but try to set to 'user' if possible?
                // Actually, if it's a company store order for a customer, the 'Bill To' in the DB is often used for Ledger.
                // If we want Ledger entries to be correct, 'Bill To' should be the Customer.
                // But we don't always have a User-ID for a walk-in customer.
                // However, the `bill_to_type` enum usually supports 'user', 'store', 'dealer', 'factory'.
                // Let's set it to 'user' if we have a user_id (not passed commonly in this form?), 
                // or just keep 'factory' internal but ensure the Invoice VIEW shows the Customer details as "Bill To".
                // VALIDATION: The Invoice View *already* has logic to show Customer Name if `billing_name` is set.
                // So focusing on `bill_to_type` for Ledger (backend):
                // Usage of bill_to in Ledger: likely triggers a debit to that entity.
                // If we don't have a registered User entity for the customer, we can't set bill_to_type='user' with a valid ID without creating a User.
                // Given the constraints, let's Stick to updating Franchise and Dealer which have clear IDs. 
                // For Company/Customer, we'll leave it as default or handled by existing flow, 
                // BUT the requirement says "Bill to will be customer". 
                // If the system requires a bill_to_id, and we don't have one, we might need to rely on the `billing_name` text fields for the PDF,
                // and keeping `bill_to_type`='factory' (Self) or specific 'Walk-in' account if exists.
                // For this task, I will primarily fix Franchise/Dealer as requested.
                // Re-reading: "In Sales Invoice, Bill to will be customer... Make sure ledger enteries are also done accordingly."
                // Only strict requirement is for Franchise/Dealer POs.
            }

            $deliveryDate = $request->delivery_date;
            $timeSlot = explode('-', $request->time_slot);
            $deliveryScheduleFrom = null;
            $deliveryScheduleTo = null;

            if (count($timeSlot) == 2) {
                $deliveryScheduleFrom = date('Y-m-d H:i:s', strtotime("$deliveryDate " . trim($timeSlot[0])));
                $deliveryScheduleTo = date('Y-m-d H:i:s', strtotime("$deliveryDate " . trim($timeSlot[1])));
            }

            $dealerId = User::whereHas('stores', function ($tq)  {
                $tq->where('stores.id', request('receiver_store_id'));
            })->first();

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'order_type' => $request->order_type,
                'dealer_id' => $request->order_type == 'dealer' ? (isset($dealerId->id) ? $dealerId->id : $request->dealer_id) : $request->dealer_id,
                'for_customer' => $request->has('for_customer') && $request->for_customer,
                'sender_store_id' => $request->sender_store_id,
                'receiver_store_id' => $request->receiver_store_id,
                'customer_first_name' => $request->customer_first_name,
                'customer_second_name' => $request->customer_second_name,
                'customer_phone_number' => $request->customer_phone_number,
                'alternate_name' => $request->alternate_name,
                'alternate_phone_number' => $request->alternate_phone_number,
                'bill_to_type' => $billToType,
                'bill_to_same_as_ship_to' => $request->bill_to_same_as_ship_to ? 1 : 0,
                'bill_to_id' => $billToId,
                'status' => Order::STATUS_PENDING,
                'is_approved' => false,
                'collect_on_delivery' => $request->has('collect_on_delivery'),
                'total_amount' => $subtotal,
                'tax_type' => 0,
                'tax_amount' => $taxValue,
                'cgst_percentage' => 0,
                'sgst_percentage' => 0,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'discunt_type' => $request->discount_type,
                'discount_amount' => floatval($request->discount_amount ?? 0),
                'net_amount' => $netAmount,
                'amount_collected' => floatval($request->amount_collected ?? 0),
                'delivery_user' => $request->delivery_user,
                'delivery_schedule_from' => $deliveryScheduleFrom,
                'delivery_schedule_to' => $deliveryScheduleTo,
                'utencils_collected' => $request->has('utencils_collected'),
                'payment_received' => $request->has('payment_received'),
                'created_by' => Auth::id(),
                'remarks' => $request->remarks,
                'delivery_address' => $request->delivery_address,
                'delivery_link' => $request->delivery_link,
                'delivery_to_store' => $request->has('delivery_to_store') ? 1 : 0,
                'handling_instructions' => $request->handling_instructions ?? [],
                'handling_note' => $request->handling_note,
                'billing_name' => $request->billing_name,
                'billing_contact_number' => $request->billing_contact_number,
                'billing_address_1' => $request->billing_address_1,
                'billing_address_2' => $request->billing_address_2,
                'billing_pincode' => $request->billing_pincode,
                'billing_gst_in' => $request->billing_gst_in,
                'billing_latitude' => $request->billing_latitude,
                'billing_longitude' => $request->billing_longitude,
                'billing_google_map_link' => $request->billing_google_map_link,
                'shipping_name' => $request->shipping_name,
                'shipping_contact_number' => $request->shipping_contact_number,
                'shipping_address_1' => $request->shipping_address_1,
                'shipping_address_2' => $request->shipping_address_2,
                'shipping_pincode' => $request->shipping_pincode,
                'shipping_gst_in' => $request->shipping_gst_in,
                'shipping_latitude' => $request->shipping_latitude,
                'shipping_longitude' => $request->shipping_longitude,
                'shipping_google_map_link' => $request->shipping_google_map_link,
                'vehicle_id' => $request->vehicle_id,
            ]);

            foreach ($request->items as $item) {

                $tProduct = OrderProduct::find($item['product_id']);
                $tSlab = \App\Models\TaxSlab::find($tProduct->tax_slab_id ?? null);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => floatval($item['unit_price']) * floatval($item['quantity']),
                    'ge_price' => $item['ge_price'] ?? 0,
                    'gi_price' => $item['gi_price'] ?? 0,
                    'tax_slab_id' => $tProduct->tax_slab_id ?? null,
                    'cgst' => $tSlab->cgst ?? 0,
                    'sgst' => $tSlab->sgst ?? 0
                ]);
            }

            // Persist additional charges
            if (is_array($request->additional_charges)) {
                foreach ($request->additional_charges as $charge) {
                    if (!empty($charge['title']) && isset($charge['amount']) && floatval($charge['amount']) > 0) {
                        \App\Models\OrderCharge::create([
                            'order_id' => $order->id,
                            'title' => $charge['title'],
                            'amount' => floatval($charge['amount']),
                        ]);
                    }
                }
            }

            // Utencils (sent) + history
            if ($request->has('utencils') && is_array($request->utencils)) {
                foreach ($request->utencils as $utencilRow) {
                    if (empty($utencilRow['utencil_id'])) {
                        continue;
                    }
                    $qty = isset($utencilRow['quantity']) ? (float) $utencilRow['quantity'] : 0;
                    if ($qty <= 0) {
                        continue;
                    }

                    $orderUtencil = OrderUtencil::create([
                        'order_id' => $order->id,
                        'utencil_id' => (int) $utencilRow['utencil_id'],
                        'quantity' => $qty,
                        'note' => $utencilRow['note'] ?? null,
                    ]);

                    OrderUtencilHistory::create([
                        'order_id' => $order->id,
                        'utencil_id' => $orderUtencil->utencil_id,
                        'quantity' => $qty,
                        'type' => OrderUtencilHistory::TYPE_SENT,
                        'note' => $orderUtencil->note,
                    ]);
                }
            }

            // Other Items
            if ($request->has('other_items') && is_array($request->other_items)) {
                foreach ($request->other_items as $oi) {
                    if (empty($oi['other_item_id']))
                        continue;

                    \App\Models\OrderOtherItem::create([
                        'order_id' => $order->id,
                        'other_item_id' => $oi['other_item_id'],
                        'quantity' => $oi['quantity'],
                        'unit_price' => $oi['unit_price'],
                        'subtotal' => floatval($oi['unit_price']) * floatval($oi['quantity']),
                        'ge_price' => 0,
                        'gi_price' => 0,
                        'price_includes_tax' => isset($oi['price_includes_tax']) ? (int) $oi['price_includes_tax'] : 0,
                        'pricing_type' => $oi['pricing_type'] ?? 'fixed',
                        'tax_slab_id' => $oi['tax_slab_id'] ?? null,
                    ]);
                }
            }

            if ($request->has('payment_received') && $request->payment_received) {
                $log = \App\Models\OrderPaymentLog::create([
                    'order_id' => $order->id,
                    'received_by_user_id' => Auth::id(),
                    'type' => 0, // Add
                    'amount' => floatval($request->amount_collected ?? 0),
                    'text' => 'Initial payment collected on creation.',
                ]);

                if ($order->receiver_store_id) {
                    $ledgerService->createCredit(
                        $order->receiver_store_id,
                        $log->amount,
                        now()->format('Y-m-d'),
                        'order_payment_log',
                        $log->id,
                        null,
                        'Initial Payment on Order #' . $order->order_number,
                        $order->order_number
                    );
                }
            }

            DB::commit();
            return redirect()->route('orders.index')->with('success', 'Order created successfully. Order #' . $order->order_number);
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $order = Order::with([
            'items.product',
            'items.unit',
            'senderStore',
            'receiverStore',
            'createdBy',
            'dealer',
            'bill2',
            'deliveryUser',
            'activityLogs.user',
            'paymentLogs.user',
            'vehicle',
            'charges',
            'otherItems',
            'utencils.utencil',
            'utencilHistories',
            'otherItems.otherItem.taxSlab',
        ])->findOrFail($id);

        // Utencil summary for show view (read-only)
        $utencilSummaries = [];
        $sentByUtencil = $order->utencils->groupBy('utencil_id');
        $historyByUtencil = $order->utencilHistories->groupBy('utencil_id');

        foreach ($sentByUtencil as $utencilId => $rows) {
            $sentQty = $rows->sum('quantity');
            $receivedQty = 0;
            if (isset($historyByUtencil[$utencilId])) {
                $receivedQty = $historyByUtencil[$utencilId]
                    ->where('type', OrderUtencilHistory::TYPE_RECEIVED)
                    ->sum('quantity');
            }
            $pendingQty = max(0, $sentQty - $receivedQty);

            $utencilSummaries[] = (object) [
                'utencil_id' => $utencilId,
                'utencil' => $rows->first()->utencil,
                'sent' => $sentQty,
                'received' => $receivedQty,
                'pending' => $pendingQty,
            ];
        }

        $page_title = "Order #" . $order->order_number;
        $page_description = "View order details";
        return view('orders.show', compact('order', 'page_title', 'page_description', 'utencilSummaries'));
    }

    public function edit($id)
    {
        $order = Order::with(['items.product.category', 'utencils.utencil', 'utencilHistories'])->findOrFail($id);
        $page_title = "Edit Order #" . $order->order_number;
        $page_description = "Edit order details";
        $stores = Store::orderBy('name')->pluck('name', 'id');
        $storesWithType = Store::with('storetype')->orderBy('name')->get();
        $categories = OrderCategory::where('status', 1)->withCount('products')->orderByDesc('products_count')->pluck('name', 'id');
        $dealers = User::role('Dealer')->orderBy('name')->pluck('name', 'id');
        $users = User::orderBy('name')->pluck('name', 'id');
        $drivers = User::role('Driver')->orderBy('name')->pluck('name', 'id');
        $factories = Store::orderBy('name')->pluck('name', 'id');
        $utencils = Utencil::orderBy('name')->pluck('name', 'id');
        $handlingInstructions = HandlingInstruction::orderBy('name')->pluck('name', 'id');
        $vehicles = Vehicle::orderBy('name')->get();
        $setting = \App\Models\Setting::first();
        $cgstPercentage = $order->cgst_percentage ?? ($setting->cgst_percentage ?? 0);
        $sgstPercentage = $order->sgst_percentage ?? ($setting->sgst_percentage ?? 0);
        $cgstAmt = $order->cgst_amount ?? ($setting->cgst_amount ?? 0);
        $sgstAmt = $order->sgst_amount ?? ($setting->sgst_amount ?? 0);

        $totalAdded = \App\Models\OrderPaymentLog::where('order_id', $order->id)->where('type', 0)->sum('amount');
        $totalDeducted = \App\Models\OrderPaymentLog::where('order_id', $order->id)->where('type', 1)->sum('amount');

        $amountCollected = $totalAdded - $totalDeducted;

        // Utencil summary for edit view
        $utencilSummaries = [];
        $sentByUtencil = $order->utencils->groupBy('utencil_id');
        $historyByUtencil = $order->utencilHistories->groupBy('utencil_id');

        foreach ($sentByUtencil as $utencilId => $rows) {
            $sentQty = $rows->sum('quantity');
            $receivedQty = 0;
            if (isset($historyByUtencil[$utencilId])) {
                $receivedQty = $historyByUtencil[$utencilId]
                    ->where('type', OrderUtencilHistory::TYPE_RECEIVED)
                    ->sum('quantity');
            }
            $pendingQty = max(0, $sentQty - $receivedQty);

            $utencilSummaries[] = (object) [
                'utencil_id' => $utencilId,
                'utencil' => $rows->first()->utencil,
                'sent' => $sentQty,
                'received' => $receivedQty,
                'pending' => $pendingQty,
            ];
        }

        $utencilSummariesArr = collect($utencilSummaries)->map(function ($s) {
            return [
                'id' => $s->utencil_id,
                'name' => $s->utencil->name ?? ('#' . $s->utencil_id),
                'sent' => (float) $s->sent,
                'pending' => (float) $s->sent - $s->received,
                'received' => (float) $s->received,
            ];
        });
        $otherItems = \App\Models\OtherItem::with('taxSlab')->where('status', 1)->orderBy('name')->get();
        $taxSlabs = \App\Models\TaxSlab::where('status', 1)->get();

        return view('orders.edit', compact(
            'order',
            'page_title',
            'page_description',
            'stores',
            'storesWithType',
            'categories',
            'dealers',
            'users',
            'drivers',
            'factories',
            'handlingInstructions',
            'cgstPercentage',
            'sgstPercentage',
            'cgstAmt',
            'sgstAmt',
            'amountCollected',
            'utencils',
            'vehicles',
            'utencilSummaries',
            'utencilSummariesArr',
            'otherItems',
            'taxSlabs'
        ));
    }

    public function reorder($id)
    {
        $order = Order::with([
            'items.product.category',
            'items.unit',
            'otherItems.otherItem.taxSlab',
            'utencils.utencil',
            'charges',
        ])->findOrFail($id);

        $page_title = "Reorder — #" . $order->order_number;
        $page_description = "Create a new order based on order #" . $order->order_number;
        $stores = Store::orderBy('name')->pluck('name', 'id');
        $storesWithType = Store::with('storetype')->orderBy('name')->get();
        $categories = OrderCategory::where('status', 1)->withCount('products')->orderByDesc('products_count')->pluck('name', 'id');
        $dealers = User::role('Dealer')->orderBy('name')->pluck('name', 'id');
        $users = User::orderBy('name')->pluck('name', 'id');
        $drivers = User::role('Driver')->orderBy('name')->pluck('name', 'id');
        $factories = Store::orderBy('name')->pluck('name', 'id');
        $utencils = Utencil::orderBy('name')->pluck('name', 'id');
        $handlingInstructions = HandlingInstruction::orderBy('name')->pluck('name', 'id');
        $vehicles = Vehicle::orderBy('name')->get();
        $setting = \App\Models\Setting::first();
        $cgstPercentage = $setting->cgst_percentage ?? 0;
        $sgstPercentage = $setting->sgst_percentage ?? 0;

        $otherItems = \App\Models\OtherItem::with('taxSlab')->where('status', 1)->orderBy('name')->get();
        $taxSlabs = \App\Models\TaxSlab::where('status', 1)->get();

        return view('orders.reorder', compact(
            'order',
            'page_title',
            'page_description',
            'stores',
            'storesWithType',
            'categories',
            'dealers',
            'users',
            'drivers',
            'factories',
            'handlingInstructions',
            'cgstPercentage',
            'sgstPercentage',
            'utencils',
            'vehicles',
            'otherItems',
            'taxSlabs'
        ));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $rules = [
            'order_type' => 'required|in:company,franchise,dealer',
            'sender_store_id' => 'required|exists:stores,id',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:order_products,id',
            'items.*.unit_id' => 'required|exists:order_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_type' => 'required|in:0,1',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'billing_name' => 'nullable|string|max:255',
            'billing_contact_number' => 'nullable|string|max:20',
            'billing_address_1' => 'nullable|string|max:500',
            'billing_address_2' => 'nullable|string|max:500',
            'billing_pincode' => 'nullable|string|max:10',
            'billing_gst_in' => 'nullable|string|max:50',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_contact_number' => 'nullable|string|max:20',
            'shipping_address_1' => 'nullable|string|max:500',
            'shipping_address_2' => 'nullable|string|max:500',
            'shipping_pincode' => 'nullable|string|max:10',
            'shipping_gst_in' => 'nullable|string|max:50',
            'billing_latitude' => 'nullable|numeric',
            'billing_longitude' => 'nullable|numeric',
            'billing_google_map_link' => 'nullable|string',
            'shipping_latitude' => 'nullable|numeric',
            'shipping_longitude' => 'nullable|numeric',
            'shipping_google_map_link' => 'nullable|string',
            'delivery_user' => 'nullable|exists:users,id',
            'delivery_date' => 'nullable|date',
            'time_slot' => 'nullable|string',
            'delivery_address' => 'nullable|string|max:1000',
            'delivery_link' => 'nullable|string|max:1000',
            'handling_instructions' => 'nullable|array',
            'handling_instructions.*' => 'exists:handling_instructions,id',
            'handling_note' => 'nullable|string',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ];

        if (in_array($request->order_type, ['company', 'franchise'])) {
            $rules['receiver_store_id'] = 'required|exists:stores,id|different:sender_store_id';
        }



        $rules['other_items'] = 'nullable|array';
        $rules['other_items.*.other_item_id'] = 'required_with:other_items|exists:other_items,id';
        $rules['other_items.*.quantity'] = 'required_with:other_items|numeric|min:0.01';
        $rules['other_items.*.unit_price'] = 'required_with:other_items|numeric|min:0';

        if ($request->has('for_customer') && $request->for_customer) {
            $rules['customer_first_name'] = 'required|string|max:255';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $subtotal2 = 0;
            $cgstAmount = 0;
            $sgstAmount = 0;
            $taxValue = 0;

            // Per-product tax calculation using each product's own tax slab
            foreach ($request->items as $item) {
                $gePrice = floatval($item['ge_price']);
                $giPrice = floatval($item['gi_price']);
                $subtotal += $gePrice * floatval($item['quantity']);
                $itemTotal = floatval($item['unit_price']) * floatval($item['quantity']);
                $subtotal2 += $itemTotal;

                $productModel = OrderProduct::with('taxSlab')->find($item['product_id']);
                $itemCgst = (float) ($productModel->taxSlab->cgst ?? 0);
                $itemSgst = (float) ($productModel->taxSlab->sgst ?? 0);
                $baseTotal = $gePrice * floatval($item['quantity']);
                $baseTotal2 = $giPrice * floatval($item['quantity']);
                $cgstAmount += $baseTotal2 * ($itemCgst / 100);
                $sgstAmount += $baseTotal2 * ($itemSgst / 100);
            }

            if ($request->has('other_items') && is_array($request->other_items)) {
                foreach ($request->other_items as $oi) {
                    if (empty($oi['other_item_id']))
                        continue;
                    $amt = floatval($oi['unit_price']) * floatval($oi['quantity']);
                    $subtotal += $amt;
                    $subtotal2 += $amt;
                }
            }

            // Sum additional charges (if any)
            $additionalChargesTotal = 0;
            if (is_array($request->additional_charges)) {
                foreach ($request->additional_charges as $charge) {
                    if (!empty($charge['title']) && isset($charge['amount'])) {
                        $additionalChargesTotal += floatval($charge['amount']);
                    }
                }
            }

            $discountValue = floatval($request->discount_amount ?? 0);
            if ($request->discount_type == 0 && $discountValue > 0) {
                $discountValue = $subtotal * ($discountValue / 100);
            }

            $afterDiscount = $subtotal - $discountValue + $additionalChargesTotal;
            $netAmount = $afterDiscount + ($cgstAmount + $sgstAmount);

            $billToType = 'factory';
            $billToId = 1;

            $deliveryDate = $request->delivery_date;
            $timeSlot = explode('-', $request->time_slot);
            $deliveryScheduleFrom = null;
            $deliveryScheduleTo = null;

            if (count($timeSlot) == 2) {
                $deliveryScheduleFrom = date('Y-m-d H:i:s', strtotime("$deliveryDate " . trim($timeSlot[0])));
                $deliveryScheduleTo = date('Y-m-d H:i:s', strtotime("$deliveryDate " . trim($timeSlot[1])));
            }

            $totalAdded = \App\Models\OrderPaymentLog::where('order_id', $order->id)->where('type', 0)->sum('amount');
            $totalDeducted = \App\Models\OrderPaymentLog::where('order_id', $order->id)->where('type', 1)->sum('amount');

            $amountCollected = $totalAdded - $totalDeducted;

            $dealerId = User::whereHas('stores', function ($tq)  {
                $tq->where('stores.id', request('receiver_store_id'));
            })->first();

            $order->update([
                'status' => $request->status,
                'order_type' => $request->order_type,
                'delivery_to_store' => $request->has('delivery_to_store') ? 1 : 0,
                'for_customer' => $request->has('for_customer') && $request->for_customer,
                'sender_store_id' => $request->sender_store_id,
                'receiver_store_id' => $request->receiver_store_id,
                'customer_first_name' => $request->customer_first_name,
                'customer_second_name' => $request->customer_second_name,
                'dealer_id' => $request->order_type == 'dealer' ? (isset($dealerId->id) ? $dealerId->id : $request->dealer_id) : $request->dealer_id,
                'customer_phone_number' => $request->customer_phone_number,
                'bill_to_type' => $billToType,
                'bill_to_id' => $billToId,
                'collect_on_delivery' => $request->has('collect_on_delivery'),
                'total_amount' => $subtotal,
                'tax_amount' => $taxValue,
                'alternate_name' => $request->alternate_name,
                'alternate_phone_number' => $request->alternate_phone_number,
                'cgst_percentage' => 0,
                'sgst_percentage' => 0,
                'bill_to_same_as_ship_to' => $request->bill_to_same_as_ship_to ? 1 : 0,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'discunt_type' => $request->discount_type,
                'discount_amount' => floatval($request->discount_amount ?? 0),
                'net_amount' => $netAmount,
                'amount_collected' => floatval($amountCollected ?? 0),
                'delivery_user' => $request->delivery_user,
                'delivery_schedule_from' => $deliveryScheduleFrom,
                'delivery_schedule_to' => $deliveryScheduleTo,
                'utencils_collected' => $request->has('utencils_collected'),
                'payment_received' => $request->has('payment_received'),
                'remarks' => $request->remarks,
                'billing_name' => $request->billing_name,
                'billing_contact_number' => $request->billing_contact_number,
                'billing_address_1' => $request->billing_address_1,
                'billing_address_2' => $request->billing_address_2,
                'billing_pincode' => $request->billing_pincode,
                'billing_gst_in' => $request->billing_gst_in,
                'billing_latitude' => $request->billing_latitude,
                'billing_longitude' => $request->billing_longitude,
                'billing_google_map_link' => $request->billing_google_map_link,
                'shipping_name' => $request->shipping_name,
                'shipping_contact_number' => $request->shipping_contact_number,
                'shipping_address_1' => $request->shipping_address_1,
                'shipping_address_2' => $request->shipping_address_2,
                'shipping_pincode' => $request->shipping_pincode,
                'shipping_gst_in' => $request->shipping_gst_in,
                'shipping_latitude' => $request->shipping_latitude,
                'shipping_longitude' => $request->shipping_longitude,
                'shipping_google_map_link' => $request->shipping_google_map_link,
                'vehicle_id' => $request->vehicle_id,
            ]);

            OrderItem::where('order_id', $order->id)->forceDelete();

            foreach ($request->items as $item) {
                $tProduct = OrderProduct::find($item['product_id']);
                $tSlab = \App\Models\TaxSlab::find($tProduct->tax_slab_id ?? null);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'ge_price' => $item['ge_price'] ?? 0,
                    'gi_price' => $item['gi_price'] ?? 0,
                    'subtotal' => floatval($item['unit_price']) * floatval($item['quantity']),
                    'tax_slab_id' => $tProduct->tax_slab_id ?? null,
                    'cgst' => $tSlab->cgst ?? 0,
                    'sgst' => $tSlab->sgst ?? 0
                ]);
            }

            // Update Other Items
            \App\Models\OrderOtherItem::where('order_id', $order->id)->forceDelete();
            if ($request->has('other_items') && is_array($request->other_items)) {
                foreach ($request->other_items as $oi) {
                    if (empty($oi['other_item_id']))
                        continue;
                    \App\Models\OrderOtherItem::create([
                        'order_id' => $order->id,
                        'other_item_id' => $oi['other_item_id'],
                        'quantity' => $oi['quantity'],
                        'unit_price' => $oi['unit_price'],
                        'subtotal' => floatval($oi['unit_price']) * floatval($oi['quantity']),
                        'price_includes_tax' => isset($oi['price_includes_tax']) ? (int) $oi['price_includes_tax'] : 0,
                        'pricing_type' => $oi['pricing_type'] ?? 'fixed',
                        'tax_slab_id' => $oi['tax_slab_id'] ?? null,
                    ]);
                }
            }

            // Sync additional charges: soft delete old, recreate current
            \App\Models\OrderCharge::where('order_id', $order->id)->delete();
            if (is_array($request->additional_charges)) {
                foreach ($request->additional_charges as $charge) {
                    if (!empty($charge['title']) && isset($charge['amount']) && floatval($charge['amount']) > 0) {
                        \App\Models\OrderCharge::create([
                            'order_id' => $order->id,
                            'title' => $charge['title'],
                            'amount' => floatval($charge['amount']),
                        ]);
                    }
                }
            }

            // New utencils sent on edit
            if ($request->has('utencils') && is_array($request->utencils)) {
                foreach ($request->utencils as $utencilRow) {
                    if (empty($utencilRow['utencil_id'])) {
                        continue;
                    }
                    $qty = isset($utencilRow['quantity']) ? (float) $utencilRow['quantity'] : 0;
                    if ($qty <= 0) {
                        continue;
                    }

                    $orderUtencil = OrderUtencil::create([
                        'order_id' => $order->id,
                        'utencil_id' => (int) $utencilRow['utencil_id'],
                        'quantity' => $qty,
                        'note' => $utencilRow['note'] ?? null,
                    ]);

                    OrderUtencilHistory::create([
                        'order_id' => $order->id,
                        'utencil_id' => $orderUtencil->utencil_id,
                        'quantity' => $qty,
                        'type' => OrderUtencilHistory::TYPE_SENT,
                        'note' => $orderUtencil->note,
                    ]);
                }
            }

            // Returns (received)
            if ($request->has('utencil_returns') && is_array($request->utencil_returns)) {
                // Compute sent and already received for validation
                $sentByUtencil = OrderUtencil::where('order_id', $order->id)
                    ->select('utencil_id', DB::raw('SUM(quantity) as qty'))
                    ->groupBy('utencil_id')
                    ->pluck('qty', 'utencil_id');

                $receivedByUtencil = OrderUtencilHistory::where('order_id', $order->id)
                    ->where('type', OrderUtencilHistory::TYPE_RECEIVED)
                    ->select('utencil_id', DB::raw('SUM(quantity) as qty'))
                    ->groupBy('utencil_id')
                    ->pluck('qty', 'utencil_id');

                foreach ($request->utencil_returns as $utencilId => $row) {
                    $qty = isset($row['quantity']) ? (float) $row['quantity'] : 0;
                    if ($qty <= 0) {
                        continue;
                    }

                    $sent = (float) ($sentByUtencil[$utencilId] ?? 0);
                    $received = (float) ($receivedByUtencil[$utencilId] ?? 0);
                    $pending = max(0.0, $sent - $received);

                    if ($qty > $pending) {
                        DB::rollBack();
                        return back()
                            ->with('error', 'Cannot receive more utencils than pending for one or more items.')
                            ->withInput();
                    }

                    OrderUtencilHistory::create([
                        'order_id' => $order->id,
                        'utencil_id' => (int) $utencilId,
                        'quantity' => $qty,
                        'type' => OrderUtencilHistory::TYPE_RECEIVED,
                        'note' => $row['note'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Order updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, \App\Services\LedgerService $ledgerService)
    {
        $request->validate([
            'id' => 'required|exists:orders,id',
            'status' => 'required|in:0,1,2,3,4',
            'cancellation_note' => 'required_if:status,4',
            'delivery_user' => 'required_if:status,2',
        ]);

        try {
            $order = Order::findOrFail($request->id);
            $order->status = $request->status;

            if ($request->status == 4) {
                $order->cancellation_note = $request->cancellation_note;
            }
            if ($request->status == 2) {
                $order->delivery_user = $request->delivery_user;

                $exists = \App\Models\LedgerTransaction::where('order_id', $order->id)
                    ->where('source_type', 'order')
                    ->where('type', 'debit')
                    ->exists();

                if (!$exists) {
                    // Check receiver store exist
                    if ($order->receiver_store_id) {
                        $ledgerService->createDebit(
                            $order->receiver_store_id,
                            $order->net_amount,
                            $order->dispatched_at ? $order->dispatched_at->format('Y-m-d') : date('Y-m-d'),
                            'order',
                            $order->id,
                            $order->id,
                            'Order Dispatch #' . $order->order_number
                        );
                    }
                }
            }

            $order->save();
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }

    public function getDeliveryPersons()
    {
        // For demonstration, all users. In real case, filter by role.
        $users = User::orderBy('name')->get(['id', 'name']);
        return response()->json($users);
    }

    public function destroy($id)
    {
        try {
            Order::findOrFail($id)->delete();
            return response()->json(['status' => true, 'message' => 'Order deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }

    public function getProductsByCategory($categoryId)
    {
        $products = OrderProduct::with('taxSlab')->where('category_id', $categoryId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'tax_slab_id'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'tax_slab_id' => $p->tax_slab_id,
                    'tax_slab_name' => $p->taxSlab->name ?? 'None',
                    'cgst' => (float) ($p->taxSlab->cgst ?? 0),
                    'sgst' => (float) ($p->taxSlab->sgst ?? 0),
                ];
            });
        return response()->json($products);
    }

    public function getUnitsByProduct($productId)
    {
        $units = OrderProductUnit::with('unit')
            ->where('order_product_id', $productId)
            ->where('status', 1)
            ->get()
            ->map(function ($pu) {
                return [
                    'id' => $pu->unit_id,
                    'name' => $pu->unit->name,
                    'default_price' => $pu->price
                ];
            });
        return response()->json($units);
    }

    public function getPriceByUnit(Request $request)
    {
        $productId = $request->product_id;
        $unitId = $request->unit_id;
        $storeId = $request->store_id;
        $gstInclusivePrice = $gstExclusivePrice = 0;
        $quantity = $request->quantity !== null ? (int) $request->quantity : null;

        if ($quantity !== null && $quantity < 1) {
            $quantity = 1;
        }

        $settings = \App\Models\Setting::select('id', 'cgst_percentage', 'sgst_percentage', 'company_store_discount')->first();
        // Use product's own tax slab instead of settings
        $product = OrderProduct::with('taxSlab')->find($productId);
        $productCgst = (float) ($product->taxSlab->cgst ?? 0);
        $productSgst = (float) ($product->taxSlab->sgst ?? 0);
        $finalGst = $productCgst + $productSgst;
        $isCompanyStore = false;

        $override = UnitPriceTier::where('product_id', $productId)
            ->where('product_unit_id', $unitId)
            ->whereHas('parent.store', function ($innerBuilder) use ($storeId) {
                $innerBuilder->where('id', $storeId);
            })
            ->first();

        if (
            Store::where('id', $storeId)->whereHas('storetype', function ($builder) {
                $builder->where('name', 'store');
            })->whereHas('modeltype', function ($builder) {
                $builder->whereIn('name', ['company']);
            })->exists()
        ) {
            $isCompanyStore = true;
        }

        if ($override) {
            $gstInclusivePrice = $override->amount ?? 0;

            if ($storeId && $quantity !== null && $quantity >= 1) {

                $discountRow = UnitDiscountTier::whereHas('tp.store', function ($innerBuilder) use ($storeId) {
                    $innerBuilder->where('id', $storeId);
                })
                    ->where('product_id', $productId)
                    ->where('product_unit_id', $unitId)
                    ->where('min_qty', '<=', $quantity)
                    ->where(function ($q) use ($quantity) {
                        $q->whereNull('max_qty')
                            ->orWhere('max_qty', '>=', $quantity);
                    })
                    ->orderBy('min_qty', 'desc')
                    ->first();

                if ($discountRow) {
                    $basePrice = (float) $gstInclusivePrice;
                    $discountAmount = 0.0;

                    if ((int) $discountRow->discount_type === UnitDiscountTier::TYPE_PERCENTAGE) {
                        $discountAmount = $basePrice * ((float) $discountRow->discount_amount / 100);
                    } else {
                        $discountAmount = (float) $discountRow->discount_amount;
                    }

                    $discountedPrice = max(0.0, $basePrice - $discountAmount);

                    if ($isCompanyStore && isset($settings->company_store_discount) && is_numeric($settings->company_store_discount) && $settings->company_store_discount > 0) {
                        $discountedPrice = $discountedPrice - (($discountedPrice * $settings->company_store_discount) / 100);
                    }

                    $gstInclusivePrice = $discountedPrice;
                    $gstExclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $finalGst) / 100);

                    return response()->json(['gi_price' => $gstInclusivePrice, 'ge_price' => $gstExclusivePrice, 'price' => $gstInclusivePrice, 'is_override' => false]);
                } else {
                    if ($isCompanyStore && isset($settings->company_store_discount) && is_numeric($settings->company_store_discount) && $settings->company_store_discount > 0) {
                        $gstInclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $settings->company_store_discount) / 100);
                    }

                    $gstExclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $finalGst) / 100);
                    return response()->json(['gi_price' => $gstInclusivePrice, 'ge_price' => $gstExclusivePrice, 'price' => $gstInclusivePrice, 'is_override' => false]);
                }
            }
        } else {
            $productUnit = OrderProductUnit::where('order_product_id', $productId)
                ->where('unit_id', $unitId)
                ->first();

            if ($isCompanyStore && isset($settings->company_store_discount) && is_numeric($settings->company_store_discount) && $settings->company_store_discount > 0) {
                $gstInclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $settings->company_store_discount) / 100);
            }

            $gstInclusivePrice = ($productUnit ? $productUnit->price : 0);
            $gstExclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $finalGst) / 100);
        }

        return response()->json(['gi_price' => $gstInclusivePrice, 'ge_price' => $gstExclusivePrice, 'price' => $gstInclusivePrice, 'is_override' => false]);
    }

    public function getInvoice(Request $request, $id)
    {
        $order = Order::with([
            'items.product',
            'items.unit',
            'senderStore',
            'receiverStore',
            'createdBy',
            'dealer',
            'bill2',
            'deliveryUser',
            'charges',
            'otherItems.otherItem.taxSlab'
        ])->findOrFail($id);

        $billTo = null;
        if ($order->bill_to_type == 'store' || $order->bill_to_type == 'factory') {
            $billTo = Store::find($order->bill_to_id);
        } elseif ($order->bill_to_type == 'user') {
            $billTo = User::find($order->bill_to_id);
        }

        return view('orders.invoice', compact('order', 'billTo'));
    }

    public function downloadInvoice($id)
    {
        $order = Order::with([
            'items.product',
            'items.unit',
            'senderStore',
            'receiverStore',
            'createdBy',
            'dealer',
            'bill2',
            'deliveryUser',
            'charges',
            'otherItems.otherItem.taxSlab'
        ])->findOrFail($id);

        $billTo = null;
        if ($order->bill_to_type == 'store' || $order->bill_to_type == 'factory') {
            $billTo = Store::find($order->bill_to_id);
        } elseif ($order->bill_to_type == 'user') {
            $billTo = User::find($order->bill_to_id);
        }

        if (is_dir(storage_path('app/public/invoices')) === false) {
            mkdir(storage_path('app/public/invoices'), 0755, true);
        }

        $setting = \App\Models\Setting::first();

        $html = view('orders.new-sales-order', compact(
            'order',
            'billTo',
            'setting'
        ))->render();
            
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'defaultFont'          => 'NotoSansGujarati',
                    'chroot'               => base_path(),
                ]);

            return $pdf->download('invoice-' . $order->order_number . '.pdf');
        } catch (\Exception $e) {
            return back()->with('error', 'Could not generate PDF: ' . $e->getMessage());
        }
    }

    public function getDeliveryChallan(Request $request, $id)
    {
        $order = Order::with(['items.product', 'items.unit', 'senderStore', 'receiverStore', 'createdBy', 'dealer', 'bill2', 'deliveryUser', 'utencils.utencil', 'utencilHistories'])->findOrFail($id);

        // Utencil summary 
        $utencilSummaries = [];
        $sentByUtencil = $order->utencils->groupBy('utencil_id');
        $historyByUtencil = $order->utencilHistories->groupBy('utencil_id');

        foreach ($sentByUtencil as $utencilId => $rows) {
            $sentQty = $rows->sum('quantity');
            $receivedQty = 0;
            if (isset($historyByUtencil[$utencilId])) {
                $receivedQty = $historyByUtencil[$utencilId]
                    ->where('type', \App\Models\OrderUtencilHistory::TYPE_RECEIVED)
                    ->sum('quantity');
            }
            $pendingQty = max(0, $sentQty - $receivedQty);

            $utencilSummaries[] = (object) [
                'utencil_id' => $utencilId,
                'utencil' => $rows->first()->utencil,
                'sent' => $sentQty,
                'received' => $receivedQty,
                'pending' => $pendingQty,
            ];
        }

        $qrCode = '';

        if ($order->bill_to_type == 'store' || $order->bill_to_type == 'factory') {
            $qrCode = Store::find($order->bill_to_id)->upi_handle ?? '';
        } else if ($order->bill_to_type == 'user') {
            $qrCode = User::find($order->bill_to_id)->upi_handle ?? '';
        }

        if (!empty($qrCode)) {
            $qrCode = "upi://pay?pa={$qrCode}";
        } else {
            $qrCode = null;
        }

        return view('orders.delivery-challan', compact('order', 'qrCode', 'utencilSummaries'));
    }

    public function downloadDeliveryChallan(Request $request, $id)
    {
        $order = Order::with(['items.product', 'items.unit', 'senderStore', 'receiverStore', 'createdBy', 'dealer', 'bill2', 'deliveryUser', 'utencils.utencil', 'utencilHistories'])->findOrFail($id);

        // Utencil summary 
        $utencilSummaries = [];
        $sentByUtencil = $order->utencils->groupBy('utencil_id');
        $historyByUtencil = $order->utencilHistories->groupBy('utencil_id');

        foreach ($sentByUtencil as $utencilId => $rows) {
            $sentQty = $rows->sum('quantity');
            $receivedQty = 0;
            if (isset($historyByUtencil[$utencilId])) {
                $receivedQty = $historyByUtencil[$utencilId]
                    ->where('type', \App\Models\OrderUtencilHistory::TYPE_RECEIVED)
                    ->sum('quantity');
            }
            $pendingQty = max(0, $sentQty - $receivedQty);

            $utencilSummaries[] = (object) [
                'utencil_id' => $utencilId,
                'utencil' => $rows->first()->utencil,
                'sent' => $sentQty,
                'received' => $receivedQty,
                'pending' => $pendingQty,
            ];
        }

        $qrCode = '';

        if ($order->bill_to_type == 'store' || $order->bill_to_type == 'factory') {
            $qrCode = Store::find($order->bill_to_id)->upi_handle ?? '';
        } else if ($order->bill_to_type == 'user') {
            $qrCode = User::find($order->bill_to_id)->upi_handle ?? '';
        }

        if (!empty($qrCode)) {
            $qrCode = "upi://pay?pa={$qrCode}";
        } else {
            $qrCode = null;
        }

        $shouldShowPrice = $request->type === 'wp';

        // Override: Never show info for Franchise or Dealer orders in Challan
        if ($order->order_type === 'franchise' || $order->order_type === 'dealer') {
            $shouldShowPrice = false;
        }

        $html = view('orders.delivery-challan', compact(
            'order',
            'qrCode',
            'shouldShowPrice',
            'utencilSummaries'
        ))->render();

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'defaultFont'          => 'NotoSansGujarati',
                    'chroot'               => base_path(),
                ]);

            return $pdf->download('challan-' . $order->order_number . '.pdf');

        } catch (\Exception $e) {
            return back()->with('error', 'Could not generate PDF: ' . $e->getMessage());
        }
    }

    public function getStoreDetails($id)
    {
        $store = Store::find($id);
        if (!$store) {
            return response()->json(['status' => false, 'message' => 'Store not found']);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'name' => $store->name,
                'address1' => trim($store->address1 . ', ' . $store->address2, ', '),
                'mobile' => $store->mobile,
                'gst_in' => $store->gst_in,
                'location_url' => $store->location_url,
            ]
        ]);
    }

    public function getAddress(Request $request)
    {
        $url = $request->input('url');

        if (!$url) {
            return response()->json(['error' => 'URL required'], 400);
        }

        $url = $this->expandUrl($url);

        $coords = $this->extractLatLng($url);

        if ($coords) {
            return $this->reverseGeocode($coords['lat'], $coords['lon']);
        }

        $placeName = $this->extractPlaceName($url);

        if ($placeName) {
            return $this->searchPlace($placeName);
        }

        return response()->json([
            'error' => 'Could not extract location from link'
        ], 400);
    }

    private function expandUrl($url)
    {
        try {
            $response = Http::withOptions([
                'allow_redirects' => true,
            ])->get($url);

            return $response->effectiveUri();
        } catch (\Exception $e) {
            return $url;
        }
    }

    private function extractLatLng($url)
    {
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return ['lat' => $matches[1], 'lon' => $matches[2]];
        }

        if (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return ['lat' => $matches[1], 'lon' => $matches[2]];
        }

        return null;
    }

    private function extractPlaceName($url)
    {
        if (preg_match('/\/place\/([^\/]+)/', $url, $matches)) {
            return urldecode(str_replace('+', ' ', $matches[1]));
        }

        // Apple Maps case
        if (preg_match('/[?&]q=([^&]+)/', $url, $matches)) {
            return urldecode($matches[1]);
        }

        return null;
    }

    private function reverseGeocode($lat, $lon)
    {
        $response = Http::withHeaders([
            'User-Agent' => 'LaravelAddressFetcher/1.0 (your@email.com)'
        ])->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'json'
                ]);

        if ($response->successful()) {
            return response()->json([
                'address' => $response->json()['display_name'] ?? null
            ]);
        }

        return response()->json(['error' => 'Reverse geocoding failed'], 500);
    }

    private function searchPlace($query)
    {
        $response = Http::withHeaders([
            'User-Agent' => 'LaravelAddressFetcher/1.0 (your@email.com)'
        ])->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1
                ]);

        if ($response->successful() && count($response->json()) > 0) {
            return response()->json([
                'address' => $response->json()[0]['display_name']
            ]);
        }

        return response()->json(['error' => 'Place not found'], 404);
    }

    public function oldaddmethod(Request $request)
    {
        // -------------------------------------------------------------------------
        // Validation Rules
        // -------------------------------------------------------------------------
        $rules = [
            // Order basics
            'order_type' => 'required|in:company,franchise,dealer',
            'sender_store_id' => 'required|exists:stores,id',
            'remarks' => 'nullable|string',

            // Order items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:order_products,id',
            'items.*.unit_id' => 'required|exists:order_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',

            // Pricing
            'discount_type' => 'required|in:0,1',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_type' => 'required|in:0,1',
            'tax_amount' => 'nullable|numeric|min:0',

            // Payment
            'payment_received' => 'nullable|boolean',
            'amount_collected' => 'required_if:payment_received,1|nullable|numeric|min:0',

            // Billing details
            'billing_name' => 'nullable|string|max:255',
            'billing_contact_number' => 'nullable|string|max:20',
            'billing_address_1' => 'nullable|string|max:500',
            'billing_address_2' => 'nullable|string|max:500',
            'billing_pincode' => 'nullable|string|max:10',
            'billing_gst_in' => 'nullable|string|max:50',
            'billing_latitude' => 'nullable|numeric',
            'billing_longitude' => 'nullable|numeric',
            'billing_google_map_link' => 'nullable|string',

            // Shipping details
            'shipping_name' => 'nullable|string|max:255',
            'shipping_contact_number' => 'nullable|string|max:20',
            'shipping_address_1' => 'nullable|string|max:500',
            'shipping_address_2' => 'nullable|string|max:500',
            'shipping_pincode' => 'nullable|string|max:10',
            'shipping_gst_in' => 'nullable|string|max:50',
            'shipping_latitude' => 'nullable|numeric',
            'shipping_longitude' => 'nullable|numeric',
            'shipping_google_map_link' => 'nullable|string',

            // Delivery
            'delivery_user' => 'required|exists:users,id',
            'delivery_date' => 'required|date',
            'time_slot' => 'required|string',
            'delivery_address' => 'required|string|max:1000',
            'delivery_link' => 'nullable|string|max:1000',
            'vehicle_id' => 'nullable|exists:vehicles,id',

            // Handling
            'handling_instructions' => 'nullable|array',
            'handling_instructions.*' => 'exists:handling_instructions,id',
            'handling_note' => 'nullable|string',

            // Other items
            'other_items' => 'nullable|array',
            'other_items.*.other_item_id' => 'required_with:other_items|exists:other_items,id',
            'other_items.*.quantity' => 'required_with:other_items|numeric|min:0.01',
            'other_items.*.unit_price' => 'required_with:other_items|numeric|min:0',
            'other_items.*.tax_slab_id' => 'nullable|exists:tax_slabs,id',

            // Utensils (sent)
            'utencils' => 'nullable|array',
            'utencils.*.utencil_id' => 'required_with:utencils|exists:utencils,id',
            'utencils.*.quantity' => 'required_with:utencils|numeric|min:0.01',
            'utencils.*.note' => 'nullable|string|max:255',

            // Utensil returns
            'utencil_returns' => 'nullable|array',
            'utencil_returns.*.quantity' => 'nullable|numeric|min:0.01',
            'utencil_returns.*.note' => 'nullable|string|max:255',
        ];

        // Conditional: receiver or dealer depending on order type
        if (in_array($request->order_type, ['company', 'franchise'])) {
            $rules['receiver_store_id'] = 'required|exists:stores,id|different:sender_store_id';
        }

        // Conditional: delivery fields required only when status is 2 or 3
        if ($request->has('status') && in_array($request->status, [2, 3])) {
            $rules['delivery_user'] = 'required|exists:users,id';
            $rules['delivery_date'] = 'required|date';
            $rules['time_slot'] = 'required|string';
        } else {
            $rules['delivery_user'] = 'nullable|exists:users,id';
            $rules['delivery_date'] = 'nullable|date';
            $rules['time_slot'] = 'nullable|string';
        }

        // Conditional: customer name required if order is for a customer
        if ($request->has('for_customer') && $request->for_customer) {
            $rules['customer_first_name'] = 'required|string|max:255';
        }

        $request->validate($rules);

        // -------------------------------------------------------------------------
        // Main Logic wrapped in a DB Transaction
        // -------------------------------------------------------------------------
        try {
            DB::beginTransaction();

            // --- Subtotals from main order items ---
            $subtotal = 0; // Used for discount base (ge_price based)
            $subtotal2 = 0; // Used for tax base (unit_price based)

            foreach ($request->items as $item) {
                $subtotal += floatval($item['ge_price']) * floatval($item['quantity']);
                $subtotal2 += floatval($item['unit_price']) * floatval($item['quantity']);
            }

            // --- Tax from settings (applies to main items) ---
            $settings = \App\Models\Setting::first();
            $cgstPercent = $settings->cgst_percentage ?? 0;
            $sgstPercent = $settings->sgst_percentage ?? 0;

            $cgstAmount = $subtotal2 * ($cgstPercent / 100);
            $sgstAmount = $subtotal2 * ($sgstPercent / 100);

            $processNonProductItem = function (array $item) use (&$subtotal, &$subtotal2, &$cgstAmount, &$sgstAmount) {
                $qty = floatval($item['quantity'] ?? 0);
                $enteredPrice = floatval($item['unit_price'] ?? 0);
                $priceIncludesTax = isset($item['price_includes_tax']) ? (int) $item['price_includes_tax'] : 0;

                $itemCgstPercent = 0;
                $itemSgstPercent = 0;

                if (!empty($item['tax_slab_id'])) {
                    $taxSlab = \App\Models\TaxSlab::find($item['tax_slab_id']);
                    if ($taxSlab) {
                        $itemCgstPercent = (float) $taxSlab->cgst;
                        $itemSgstPercent = (float) $taxSlab->sgst;
                    }
                }

                $totalTaxPercent = $itemCgstPercent + $itemSgstPercent;

                if ($priceIncludesTax === 1 && $totalTaxPercent > 0) {
                    $unitBasePrice = $enteredPrice / (1 + $totalTaxPercent / 100);
                    $unitTaxAmount = $enteredPrice - $unitBasePrice;
                } else {
                    $unitBasePrice = $enteredPrice;
                    $unitTaxAmount = $enteredPrice * ($totalTaxPercent / 100);
                }

                $lineBase = $qty * $unitBasePrice;
                $lineTax = $unitTaxAmount * $qty;

                if ($lineTax > 0 && $totalTaxPercent > 0) {
                    $cgstAmount += $lineTax * ($itemCgstPercent / $totalTaxPercent);
                    $sgstAmount += $lineTax * ($itemSgstPercent / $totalTaxPercent);
                }

                $subtotal += $lineBase;
                $subtotal2 += $lineBase;

                return $lineBase;
            };

            if ($request->has('other_items') && is_array($request->other_items)) {
                foreach ($request->other_items as $oi) {
                    if (!empty($oi['other_item_id'])) {
                        $processNonProductItem($oi);
                    }
                }
            }

            // --- Additional charges ---
            $additionalChargesTotal = 0;
            if (is_array($request->additional_charges)) {
                foreach ($request->additional_charges as $charge) {
                    if (!empty($charge['title']) && isset($charge['amount'])) {
                        $additionalChargesTotal += floatval($charge['amount']);
                    }
                }
            }

            // --- Discount calculation ---
            $discountValue = floatval($request->discount_amount ?? 0);
            if ($request->discount_type == 0 && $discountValue > 0) {
                $discountValue = $subtotal * ($discountValue / 100);
            }

            // --- Final net amount ---
            $afterDiscount = $subtotal - $discountValue + $additionalChargesTotal;
            $netAmount = $afterDiscount + ($cgstAmount + $sgstAmount);

            // --- Determine bill-to entity ---
            $billToType = 'factory';
            $billToId = 1;

            if ($request->order_type === 'franchise') {
                $billToType = 'store';
                $billToId = $request->receiver_store_id;
            } elseif ($request->order_type === 'dealer') {
                $billToType = 'dealer';
                $billToId = $request->dealer_id;
            }

            // --- Delivery schedule ---
            $deliveryScheduleFrom = null;
            $deliveryScheduleTo = null;

            $timeSlotParts = explode('-', $request->time_slot ?? '');
            if (count($timeSlotParts) === 2) {
                $deliveryDate = $request->delivery_date;
                $deliveryScheduleFrom = date('Y-m-d H:i:s', strtotime("$deliveryDate " . trim($timeSlotParts[0])));
                $deliveryScheduleTo = date('Y-m-d H:i:s', strtotime("$deliveryDate " . trim($timeSlotParts[1])));
            }

            // -----------------------------------------------------------------------
            // Create the Order
            // -----------------------------------------------------------------------
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'order_type' => $request->order_type,
                'dealer_id' => $request->dealer_id,
                'for_customer' => $request->has('for_customer') && $request->for_customer,
                'sender_store_id' => $request->sender_store_id,
                'receiver_store_id' => $request->order_type !== 'dealer' ? $request->receiver_store_id : null,
                'customer_first_name' => $request->customer_first_name,
                'customer_second_name' => $request->customer_second_name,
                'customer_phone_number' => $request->customer_phone_number,
                'bill_to_type' => $billToType,
                'bill_to_same_as_ship_to' => $request->bill_to_same_as_ship_to ? 1 : 0,
                'bill_to_id' => $billToId,
                'status' => Order::STATUS_PENDING,
                'is_approved' => false,
                'collect_on_delivery' => $request->has('collect_on_delivery'),
                'total_amount' => $subtotal,
                'tax_type' => $request->tax_type,
                'tax_amount' => 0, // $taxValue was always 0; kept intentionally
                'cgst_percentage' => $cgstPercent,
                'sgst_percentage' => $sgstPercent,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'discunt_type' => $request->discount_type,
                'discount_amount' => floatval($request->discount_amount ?? 0),
                'net_amount' => $netAmount,
                'amount_collected' => floatval($request->amount_collected ?? 0),
                'delivery_user' => $request->delivery_user,
                'delivery_schedule_from' => $deliveryScheduleFrom,
                'delivery_schedule_to' => $deliveryScheduleTo,
                'utencils_collected' => $request->has('utencils_collected'),
                'payment_received' => $request->has('payment_received'),
                'created_by' => Auth::id(),
                'remarks' => $request->remarks,
                'delivery_address' => $request->delivery_address,
                'delivery_link' => $request->delivery_link,
                'handling_instructions' => $request->handling_instructions ?? [],
                'handling_note' => $request->handling_note,
                'vehicle_id' => $request->vehicle_id,

                // Billing address
                'billing_name' => $request->billing_name,
                'billing_contact_number' => $request->billing_contact_number,
                'billing_address_1' => $request->billing_address_1,
                'billing_address_2' => $request->billing_address_2,
                'billing_pincode' => $request->billing_pincode,
                'billing_gst_in' => $request->billing_gst_in,
                'billing_latitude' => $request->billing_latitude,
                'billing_longitude' => $request->billing_longitude,
                'billing_google_map_link' => $request->billing_google_map_link,

                // Shipping address
                'shipping_name' => $request->shipping_name,
                'shipping_contact_number' => $request->shipping_contact_number,
                'shipping_address_1' => $request->shipping_address_1,
                'shipping_address_2' => $request->shipping_address_2,
                'shipping_pincode' => $request->shipping_pincode,
                'shipping_gst_in' => $request->shipping_gst_in,
                'shipping_latitude' => $request->shipping_latitude,
                'shipping_longitude' => $request->shipping_longitude,
                'shipping_google_map_link' => $request->shipping_google_map_link,
            ]);

            // -----------------------------------------------------------------------
            // Persist: Order Items
            // -----------------------------------------------------------------------
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => floatval($item['unit_price']) * floatval($item['quantity']),
                    'ge_price' => $item['ge_price'] ?? 0,
                    'gi_price' => $item['gi_price'] ?? 0,
                ]);
            }

            // -----------------------------------------------------------------------
            // Persist: Additional Charges
            // -----------------------------------------------------------------------
            if (is_array($request->additional_charges)) {
                foreach ($request->additional_charges as $charge) {
                    if (!empty($charge['title']) && isset($charge['amount']) && floatval($charge['amount']) > 0) {
                        \App\Models\OrderCharge::create([
                            'order_id' => $order->id,
                            'title' => $charge['title'],
                            'amount' => floatval($charge['amount']),
                        ]);
                    }
                }
            }

            // -----------------------------------------------------------------------
            // Persist: Utensils Sent + History
            // -----------------------------------------------------------------------
            if ($request->has('utencils') && is_array($request->utencils)) {
                foreach ($request->utencils as $row) {
                    if (empty($row['utencil_id']))
                        continue;

                    $qty = (float) ($row['quantity'] ?? 0);
                    if ($qty <= 0)
                        continue;

                    $orderUtencil = OrderUtencil::create([
                        'order_id' => $order->id,
                        'utencil_id' => (int) $row['utencil_id'],
                        'quantity' => $qty,
                        'note' => $row['note'] ?? null,
                    ]);

                    OrderUtencilHistory::create([
                        'order_id' => $order->id,
                        'utencil_id' => $orderUtencil->utencil_id,
                        'quantity' => $qty,
                        'type' => OrderUtencilHistory::TYPE_SENT,
                        'note' => $orderUtencil->note,
                    ]);
                }
            }

            // -----------------------------------------------------------------------
            // Persist: Other Items
            // -----------------------------------------------------------------------
            if ($request->has('other_items') && is_array($request->other_items)) {
                foreach ($request->other_items as $oi) {
                    if (empty($oi['other_item_id']))
                        continue;

                    \App\Models\OrderOtherItem::create([
                        'order_id' => $order->id,
                        'other_item_id' => $oi['other_item_id'],
                        'quantity' => $oi['quantity'],
                        'unit_price' => $oi['unit_price'],
                        'subtotal' => floatval($oi['unit_price']) * floatval($oi['quantity']),
                        'ge_price' => 0,
                        'gi_price' => 0,
                        'price_includes_tax' => isset($oi['price_includes_tax']) ? (int) $oi['price_includes_tax'] : 0,
                        'pricing_type' => $oi['pricing_type'] ?? 'fixed',
                        'tax_slab_id' => $oi['tax_slab_id'] ?? null,
                    ]);
                }
            }

            // -----------------------------------------------------------------------
            // Persist: Initial Payment Log (if payment was received)
            // -----------------------------------------------------------------------
            if ($request->has('payment_received') && $request->payment_received) {
                \App\Models\OrderPaymentLog::create([
                    'order_id' => $order->id,
                    'received_by_user_id' => Auth::id(),
                    'type' => 0, // 0 = Add
                    'amount' => floatval($request->amount_collected ?? 0),
                    'text' => 'Initial payment collected on creation.',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('orders.index')
                ->with('success', 'Order created successfully. Order #' . $order->order_number);

        } catch (\Exception $e) {
            DB::rollback();

            return back()
                ->with('error', 'Something went wrong: ' . $e->getMessage())
                ->withInput();
        }
    }
}
