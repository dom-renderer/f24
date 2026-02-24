@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
    .detail-label { font-weight: 600; color: #64748b; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 4px; }
    .detail-value { font-size: 1rem; color: #1e293b; }
    .items-table th { background: #f1f5f9; font-size: 0.75rem; text-transform: uppercase; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h4 class="mb-1">{{ $page_title }}</h4>
            <p class="text-muted small mb-0">{{ $page_description }}</p>
        </div>
        <div class="col-md-4 text-end">
            @if(auth()->user()->can('orders.edit'))
                <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-primary btn-sm me-2">
                    <i class="fas fa-pen me-1"></i> Edit
                </a>
            @endif
            <a href="{{ route('orders.download-invoice', $order->id) }}" class="btn btn-info btn-sm me-2">
                <i class="fas fa-file-pdf me-1"></i> Invoice
            </a>
            <a href="{{ route('orders.download-challan', $order->id) }}" class="btn btn-secondary btn-sm me-2">
                <i class="fas fa-print me-1"></i> Challan
            </a>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Order Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="detail-label">Order Number</div>
                            <div class="detail-value fw-bold">#{{ $order->order_number }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="detail-label">Order Type</div>
                            <div class="detail-value">{{ $order->order_type }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">{!! $order->status_label !!}</div>
                        </div>
                    </div>

                    {{-- Mirror new form layout: Order From / Dispatched From / Customer / Delivery / Handling / Driver / Utencils / Payment --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="detail-label"><i class="fas fa-store-alt me-1"></i>Order From</div>
                            <div class="detail-value">{{ $order->receiverStore->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="detail-label"><i class="fas fa-store me-1"></i>Dispatched From</div>
                            <div class="detail-value">{{ $order->senderStore->name ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="detail-label">Created By</div>
                            <div class="detail-value">{{ $order->createdBy->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="detail-label">Created At</div>
                            <div class="detail-value">{{ $order->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="detail-label"><i class="fas fa-truck me-1"></i>Driver</div>
                            <div class="detail-value">{{ isset($order->deliveryUser->id) ? ($order->deliveryUser->name . ' ' . $order->deliveryUser->middle_name . ' ' . $order->deliveryUser->last_name) : 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="detail-label"><i class="fas fa-car me-1"></i>Vehicle</div>
                            <div class="detail-value">{{ $order->vehicle->number ?? 'N/A' }}</div>
                        </div>
                    </div>

                    {{-- Ordering for Customer --}}
                    <div class="row">
                        <div class="col-12 mb-2">
                            <div class="detail-label">Ordering for Customer</div>
                            <div class="detail-value">
                                {{ $order->for_customer ? 'Yes' : 'No' }}
                            </div>
                        </div>
                    </div>

                    @if($order->for_customer)
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="detail-label">Customer Name</div>
                                <div class="detail-value">{{ $order->customer_first_name ?: 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="detail-label">Contact Number</div>
                                <div class="detail-value">{{ $order->customer_phone_number ?: 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="detail-label">Alternate Person Name</div>
                                <div class="detail-value">{{ $order->alternate_name ?: 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="detail-label">Alternate Phone Number</div>
                                <div class="detail-value">{{ $order->alternate_phone_number ?: 'N/A' }}</div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="detail-label">Delivery Remarks</div>
                                <div class="detail-value">{{ $order->customer_remark ?: 'N/A' }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- Delivery Date / Time Slot --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="detail-label"><i class="fas fa-calendar-alt me-1"></i>Delivery Date</div>
                            <div class="detail-value">
                                {{ $order->delivery_schedule_from ? date('d M Y', strtotime($order->delivery_schedule_from)) : 'N/A' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="detail-label">Time Slot</div>
                            <div class="detail-value">
                                @if($order->delivery_schedule_from && $order->delivery_schedule_to)
                                    {{ date('h:i A', strtotime($order->delivery_schedule_from)) }} - {{ date('h:i A', strtotime($order->delivery_schedule_to)) }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Delivery Address + Map Link --}}
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="detail-label">Delivery Address
                                @if($order->delivery_to_store)
                                    <span class="badge bg-info ms-2">Deliver to Store</span>
                                @endif
                            </div>
                            <div class="detail-value">{{ $order->delivery_address ?: 'N/A' }}</div>
                        </div>
                        @if(!empty($order->delivery_link))
                            <div class="col-md-12 mb-3">
                                <div class="detail-label">Delivery Address Map Link</div>
                                <div class="detail-value">
                                    <a href="{{ $order->delivery_link }}" target="_blank">Open in Maps</a>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Handling Instructions --}}
                    @php
                        $handlingNames = [];
                        if (is_array($order->handling_instructions) && count($order->handling_instructions)) {
                            $handlingNames = \App\Models\HandlingInstruction::whereIn('id', $order->handling_instructions)->pluck('name')->toArray();
                        }
                    @endphp
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="detail-label">Handling Instructions</div>
                            <div class="detail-value">
                                {{ count($handlingNames) ? implode(', ', $handlingNames) : 'N/A' }}
                            </div>
                        </div>
                        @if($order->handling_note)
                            <div class="col-md-12 mb-3">
                                <div class="detail-label">Handling Instructions Remarks</div>
                                <div class="detail-value">{{ $order->handling_note }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- Utencils summary within Order Details (read-only) --}}
                    @if(!empty($utencilSummaries))
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="detail-label mb-1"><i class="fas fa-utensils me-1"></i>Utencils Movement</div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="text-muted small">
                                        <tr>
                                            <th>Utencil</th>
                                            <th class="text-end">Sent</th>
                                            <th class="text-end">Received</th>
                                            <th class="text-end">Pending</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($utencilSummaries as $summary)
                                            <tr>
                                                <td>{{ $summary->utencil->name ?? ('#' . $summary->utencil_id) }}</td>
                                                <td class="text-end">{{ number_format($summary->sent, 2) }}</td>
                                                <td class="text-end">{{ number_format($summary->received, 2) }}</td>
                                                <td class="text-end">{{ number_format($summary->pending, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Collect Amount / Utencils flags + Remarks --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="detail-label">Collect Utencils on Delivery</div>
                            <div class="detail-value">{{ $order->utencils_collected ? 'Yes' : 'No' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="detail-label">Collect Amount on Delivery</div>
                            <div class="detail-value">{{ $order->collect_on_delivery ? 'Yes' : 'No' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="detail-label">Amount Collected</div>
                            <div class="detail-value">
                                {{ Helper::defaultCurrencySymbol() }}{{ number_format($order->amount_collected, 2) }}
                            </div>
                        </div>
                        @if($order->remarks)
                            <div class="col-md-12 mb-3">
                                <div class="detail-label">Remarks</div>
                                <div class="detail-value">{{ $order->remarks }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Addresses</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="mb-3 text-primary">Bill To</h6>
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <div class="detail-label">Name</div>
                                    <div class="detail-value">{{ $order->billing_name ?: 'N/A' }}</div>
                                </div>
                                <div class="col-md-12 mb-2">
                                    <div class="detail-label">Contact</div>
                                    <div class="detail-value">{{ $order->billing_contact_number ?: 'N/A' }}</div>
                                </div>
                                <div class="col-md-12 mb-2">
                                    <div class="detail-label">Full Address</div>
                                    <div class="detail-value">{{ $order->billing_address_1 ?: 'N/A' }}</div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="detail-label">GST IN</div>
                                    <div class="detail-value">{{ $order->billing_gst_in ?: 'N/A' }}</div>
                                </div>
                                @if(!empty($order->billing_google_map_link))
                                    <div class="col-md-12 mb-2">
                                        <div class="detail-label">Address Link</div>
                                        <div class="detail-value">
                                            <a href="{{ $order->billing_google_map_link }}" target="_blank">
                                                Open Link in Map
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3 text-primary">Ship To</h6>
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <div class="detail-label">Name</div>
                                    <div class="detail-value">{{ $order->shipping_name ?: 'N/A' }}</div>
                                </div>
                                <div class="col-md-12 mb-2">
                                    <div class="detail-label">Contact</div>
                                    <div class="detail-value">{{ $order->shipping_contact_number ?: 'N/A' }}</div>
                                </div>
                                <div class="col-md-12 mb-2">
                                    <div class="detail-label">Full Address</div>
                                    <div class="detail-value">{{ $order->shipping_address_1 ?: 'N/A' }}</div>
                                </div>
                                @if(!empty($order->shipping_google_map_link))
                                    <div class="col-md-12 mb-2">
                                        <div class="detail-label">Address Link</div>
                                        <div class="detail-value">
                                            <a href="{{ $order->shipping_google_map_link }}" target="_blank">
                                                Open Link in Map
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-signature me-2 text-primary"></i>Signatures</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center border-end">
                            <h6 class="mb-3 text-muted small uppercase">Customer Signature</h6>
                            @if($order->customer_signature && is_file(public_path('storage/order-signatures/' . $order->customer_signature)))
                                <div class="p-2 border rounded bg-light">
                                    <img src="{{ asset('storage/order-signatures/' . $order->customer_signature) }}" alt="Customer Signature" class="img-fluid" style="max-height: 150px;">
                                </div>
                            @else
                                <div class="py-4 text-muted italic">No signature available</div>
                            @endif
                        </div>
                        <div class="col-md-4 text-center border-end">
                            <h6 class="mb-3 text-muted small uppercase">Driver Signature</h6>
                            @if($order->delivery_guy_signature && is_file(public_path('storage/order-signatures/' . $order->delivery_guy_signature)))
                                <div class="p-2 border rounded bg-light">
                                    <img src="{{ asset('storage/order-signatures/' . $order->delivery_guy_signature) }}" alt="Driver Signature" class="img-fluid" style="max-height: 150px;">
                                </div>
                            @else
                                <div class="py-4 text-muted italic">No signature available</div>
                            @endif
                        </div>
                        <div class="col-md-4 text-center">
                            <h6 class="mb-3 text-muted small uppercase">Payment Proof</h6>
                            @if($order->payment_proof && is_file(public_path('storage/order-signatures/' . $order->payment_proof)))
                                <div class="p-2 border rounded bg-light">
                                    <img src="{{ asset('storage/order-signatures/' . $order->payment_proof) }}" alt="Payment Proof" class="img-fluid" style="max-height: 150px;">
                                </div>
                            @else
                                <div class="py-4 text-muted italic">No Payment Proof Attached</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-boxes me-2 text-primary"></i>Products</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table items-table mb-0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Unit</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $groupedItems = $order->items->groupBy(function($item) {
                                    return $item->product && $item->product->taxSlab ? $item->product->taxSlab->id : 'none';
                                });
                                $productsGrandTotal = 0;
                            @endphp
                            
                            @foreach($groupedItems as $slabId => $items)
                                @php
                                    $firstItem = $items->first();
                                    $taxSlabName = $firstItem->product && $firstItem->product->taxSlab ? $firstItem->product->taxSlab->name : 'None';
                                    $cgstPercent = $firstItem->product && $firstItem->product->taxSlab ? (float)$firstItem->product->taxSlab->cgst : 0;
                                    $sgstPercent = $firstItem->product && $firstItem->product->taxSlab ? (float)$firstItem->product->taxSlab->sgst : 0;
                                    
                                    // $groupSubtotal = $items->sum(function($item) {
                                    //     return clone($item)->ge_price * clone($item)->quantity;
                                    // });
                                    $groupSubtotal = $items->sum(function($item) {
                                        if (!$item) return 0;

                                        return (float) ($item->ge_price ?? 0) * (float) ($item->quantity ?? 0);
                                    });

                                    $groupCgst = $groupSubtotal * ($cgstPercent / 100);
                                    $groupSgst = $groupSubtotal * ($sgstPercent / 100);
                                    $productsGrandTotal += ($groupSubtotal + $groupCgst + $groupSgst);
                                @endphp

                                <tr>
                                    <td colspan="6" class="fw-bold text-uppercase small text-muted pt-3 pb-1 bg-light">{{ $taxSlabName }}</td>
                                </tr>

                                @foreach($items as $index => $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                                    <td>{{ $item->unit->name ?? 'N/A' }}</td>
                                    <td class="text-end">{{ Helper::defaultCurrencySymbol() }}{{ number_format($item->ge_price, 2) }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end fw-bold">{{ Helper::defaultCurrencySymbol() }}{{ number_format($item->ge_price * $item->quantity, 2) }}</td>
                                </tr>
                                @endforeach

                                <tr class="bg-light">
                                    <td colspan="5" class="text-end text-muted small py-1">{{ $taxSlabName }} Subtotal</td>
                                    <td class="text-end text-muted small py-1">{{ Helper::defaultCurrencySymbol() }}{{ number_format($groupSubtotal, 2) }}</td>
                                </tr>
                                @if($cgstPercent > 0 || $sgstPercent > 0)
                                    <tr class="bg-light">
                                        <td colspan="5" class="text-end text-muted small py-1">CGST ({{ $cgstPercent }}%)</td>
                                        <td class="text-end text-danger small py-1">+{{ Helper::defaultCurrencySymbol() }}{{ number_format($groupCgst, 2) }}</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="5" class="text-end text-muted small py-1">SGST ({{ $sgstPercent }}%)</td>
                                        <td class="text-end text-danger small py-1">+{{ Helper::defaultCurrencySymbol() }}{{ number_format($groupSgst, 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            </tbody>
                            <tfoot class="border-top">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Products Total</td>
                                    <td class="text-end fw-bold fs-5 text-primary">{{ Helper::defaultCurrencySymbol() }}{{ number_format($productsGrandTotal, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Other Items --}}
            @if($order->otherItems->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-cubes me-2 text-primary"></i>Other Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table items-table mb-0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th class="text-center">Tax Incl.</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($order->otherItems as $index => $oi)
                            @php
                                $srcItem = $oi->otherItem;
                                $priceIncTax = $oi->price_includes_tax;
                                $cgstPercent = $srcItem && $srcItem->taxSlab ? (float)$srcItem->taxSlab->cgst : 0;
                                $sgstPercent = $srcItem && $srcItem->taxSlab ? (float)$srcItem->taxSlab->sgst : 0;

                                $totalTaxPercent = $cgstPercent + $sgstPercent;
                                $uPrice = (float)$oi->unit_price;
                                $qty = (float)$oi->quantity;
                                $basePrice = $uPrice;

                                if ($priceIncTax && $totalTaxPercent > 0) {
                                    $basePrice = $uPrice / (1 + ($totalTaxPercent / 100));
                                }

                                $lineTotalBase = $basePrice * $qty;
                                $cgstAmt = $lineTotalBase * ($cgstPercent / 100);
                                $sgstAmt = $lineTotalBase * ($sgstPercent / 100);
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $oi->otherItem->name ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if($priceIncTax)
                                        <i class="fas fa-check-circle text-success"></i>
                                    @else
                                        <i class="fas fa-times-circle text-danger"></i>
                                    @endif
                                </td>
                                <td class="text-end">{{ Helper::defaultCurrencySymbol() }}{{ number_format($basePrice, 2) }}</td>
                                <td class="text-end">{{ $oi->quantity }}</td>
                                <td class="text-end fw-bold">{{ Helper::defaultCurrencySymbol() }}{{ number_format($lineTotalBase, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class="p-0 border-0">
                                    <table class="table table-sm table-borderless mb-0 bg-light">
                                        <tr>
                                            <td colspan="4" class="text-end text-muted small py-1" style="width: 80%;">CGST ({{ $cgstPercent }}%)</td>
                                            <td class="text-end text-danger small py-1" style="width: 20%;">+{{ Helper::defaultCurrencySymbol() }}{{ number_format($cgstAmt, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end text-muted small py-1">SGST ({{ $sgstPercent }}%)</td>
                                            <td class="text-end text-danger small py-1">+{{ Helper::defaultCurrencySymbol() }}{{ number_format($sgstAmt, 2) }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-wallet me-2 text-success"></i>Payment History</h6>
                </div>
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="small text-muted">Total Deposit:</span>
                        <span class="fw-bold text-success fs-5">{{ Helper::defaultCurrencySymbol() }}{{ number_format($order->amount_collected, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Pending Amount:</span>
                        <span class="fw-bold text-danger">{{ Helper::defaultCurrencySymbol() }}{{ number_format(($order->net_amount) - $order->amount_collected, 2) }}</span>
                    </div>
                    <hr class="my-2">
                    <div id="paymentLogsList" class="p-2" style="max-height: 180px; overflow-y: auto;">
                        @if($order->paymentLogs->count() > 0)
                            @foreach($order->paymentLogs as $log)
                                <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
                                    <div>
                                        <span class="badge {{ $log->type == 0 ? 'bg-success' : 'bg-danger' }} badge-sm">
                                            {{ $log->type == 0 ? 'Received' : 'Returned' }}
                                        </span>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                                            {{ $log->created_at->format('d M Y, h:i A') }}
                                        </small>
                                        @if($log->text)
                                            <small class="text-dark d-block fst-italic" style="font-size: 0.75rem;">{{ Str::limit($log->text, 30) }}</small>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold {{ $log->type == 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $log->type == 0 ? '+' : '-' }}{{ Helper::defaultCurrencySymbol() }}{{ number_format($log->amount, 2) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted small py-3">
                                <i class="fas fa-info-circle me-1"></i>No payment history yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-receipt me-2 text-primary"></i>Summary</h6>
                </div>
                <div class="card-body">
                    @if($order->charges && $order->charges->count())
                        <div class="mb-2">
                            <span class="text-muted d-block mb-1">Additional Charges:</span>
                            @foreach($order->charges as $charge)
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>{{ $charge->title }}</span>
                                    <span>{{ Helper::defaultCurrencySymbol() }}{{ number_format($charge->amount, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Discount:</span>
                        <span>-{{ Helper::defaultCurrencySymbol() }}{{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Grand Total:</span>
                        <span class="fs-4 fw-bold text-primary">{{ Helper::defaultCurrencySymbol() }}{{ number_format($order->net_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Activity Timeline</h6>
                </div>
                <div class="card-body p-0">
                    <div class="timeline p-3">
                        @foreach($order->activityLogs as $log)
                            <div class="timeline-item pb-3 position-relative">
                                <div class="d-flex align-items-start">
                                    <div class="timeline-icon me-3 mt-1">
                                        @if($log->action == 'created')
                                            <i class="fas fa-plus-circle text-success fs-5"></i>
                                        @elseif($log->action == 'updated')
                                            <i class="fas fa-pen-to-square text-warning fs-5"></i>
                                        @elseif(str_contains($log->action, 'status'))
                                            <i class="fas fa-eye text-info fs-5"></i>
                                        @else
                                            <i class="fas fa-info-circle text-secondary fs-5"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold fs-6 text-dark">{{ $log->user->name ?? 'System' }}</span>
                                            <small class="text-muted" style="font-size: 0.75rem;">{{ $log->created_at->format('d M, h:i A') }}</small>
                                        </div>
                                        <div class="text-dark bg-light p-2 rounded mb-2" style="font-size: 0.85rem;">
                                            {{ $log->description }}
                                        </div>
                                        @if($log->old_data && $log->new_data)
                                            <a class="text-primary text-decoration-none small" data-bs-toggle="collapse" href="#logDiff{{ $log->id }}" role="button" aria-expanded="false" aria-controls="logDiff{{ $log->id }}">
                                                <i class="fas fa-code-compare me-1"></i>View Changes
                                            </a>
                                            <div class="collapse mt-2" id="logDiff{{ $log->id }}">
                                                <div class="card card-body bg-light border-0 p-2 small font-monospace">
                                                    @foreach($log->new_data as $key => $newVal)
                                                        @php 
                                                            if(in_array($key, ['id', 'order_id', 'created_at', 'updated_at', 'deleted_at'])) continue;
                                                            $oldVal = $log->old_data[$key] ?? 'N/A';
                                                            $displayOld = Helper::formatLogValue($key, $oldVal);
                                                            $displayNew = Helper::formatLogValue($key, $newVal);
                                                        @endphp
                                                        @if($oldVal != $newVal)
                                                            <div class="mb-1">
                                                                 <span class="fw-bold">{{ Helper::formatLogKey($key) }}:</span><br>
                                                                 <span class="text-danger">- {{ $displayOld }}</span><br>
                                                                 <span class="text-success">+ {{ $displayNew }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
