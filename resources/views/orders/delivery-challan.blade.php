<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Challan</title>
    <style>
        @font-face {
            font-family: "NotoSansGujarati";
            src: url("{{ storage_path('fonts/NotoSansGujarati-Regular.ttf') }}") format("truetype");
            font-weight: normal;
            font-style: normal;
        }

        .gujarati {
            font-family: "NotoSansGujarati", "DejaVu Sans", sans-serif;
        }

        html {
            font-family: "NotoSansGujarati", "DejaVu Sans", sans-serif;
        }

        body {
            font-family: "NotoSansGujarati", "DejaVu Sans", sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 10px;
        }
        .container {
            width: 100%;
            border: 1px solid #cccccc;
            padding: 10px 12px;
        }
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
        .left { float: left; }
        .right { float: right; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .small { font-size: 9px; }
        .bold { font-weight: bold; }
        .mt-5 { margin-top: 5px; }
        .mt-10 { margin-top: 10px; }
        .mb-5 { margin-bottom: 5px; }
        .mb-10 { margin-bottom: 10px; }
        .border-top { border-top: 1px solid #e0e0e0; }
        .border-bottom { border-bottom: 1px solid #e0e0e0; }
        .box {
            border: 1px solid #e0e0e0;
            padding: 6px 8px;
            margin-bottom: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 4px 5px;
        }
        th {
            background-color: #f5f5f5;
            font-size: 10px;
        }
        .bordered td, .bordered th {
            border: 1px solid #e0e0e0;
        }
        .label {
            font-size: 9px;
            color: #666666;
            text-transform: uppercase;
        }
        .value {
            font-size: 11px;
            color: #111111;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="clearfix">
            <div class="left">
                <div class="bold" style="font-size:18px;color:#b91c1c;">Farki</div>
                <div class="small" style="color:#ef4444;">હરપલ બને ઉત્સવ...</div>
            </div>
            <div class="right text-right">
                <div class="bold" style="font-size:14px;">DELIVERY CHALLAN</div>
                <div class="bold" style="font-size:12px;color:#b91c1c;">
                    #CHALLAN-{{ strtoupper(date('d-M-Y', strtotime($order->delivery_schedule_from))) }}
                </div>
                <div class="small">Order: #{{ $order->order_number }}</div>
            </div>
        </div>

        {{-- Order details --}}
        <div class="box mt-10">
            <div class="clearfix border-bottom mb-5 pb-5">
                <div class="left label">Order Details</div>
                <div class="right small bold" style="border:1px solid #cccccc;padding:2px 4px;">
                    {{-- {{ $order->order_type }} --}}
                </div>
            </div>

            <table>
                <tr>
                    <td width="50%" class="label">Name</td>
                    <td width="50%" class="label">Order Number</td>
                </tr>
                <tr>
                    <td class="value">
                        @if($order->for_customer && !$order->delivery_to_store)
                            {{ $order->customer_first_name ?? 'N/A' }}
                        @else
                            @if(in_array($order->order_type, ['company', 'franchise']))
                                {{ isset($order->receiverStore->id) ? ($order->receiverStore->code . ' - ' . $order->receiverStore->name) : 'N/A' }}
                            @else
                                {{ isset($order->dealer->id) ? ($order->dealer->name . ' ' . $order->dealer->middle_name . ' ' . $order->dealer->last_name) : 'N/A' }}
                            @endif
                        @endif
                    </td>
                    <td class="value">#{{ $order->order_number }}</td>
                </tr>
                @if($order->for_customer && !$order->delivery_to_store && $order->alternate_name)
                <tr>
                    <td width="50%" class="label">Alternate Person</td>
                    <td width="50%" class="label">Alternate Phone Number</td>
                </tr>
                <tr>
                    <td class="value">
                        {{ $order->alternate_name ?? 'N/A' }}
                    </td>
                    <td class="value">{{ $order->alternate_phone_number ?? 'N/A' }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Delivery Date</td>
                    <td class="label">Driver</td>
                </tr>
                <tr>
                    <td class="value">
                        {{ date('d M Y H:i', strtotime($order->delivery_schedule_from)) }}
                        -
                        {{ date('H:i', strtotime($order->delivery_schedule_to)) }}
                    </td>
                    <td class="value">
                        {{ isset($order->deliveryUser->id) ? ($order->deliveryUser->name . ' ' . $order->deliveryUser->middle_name . ' ' . $order->deliveryUser->last_name) : 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Order Date</td>
                    <td class="label">Vehicle</td>
                </tr>
                <tr>
                    <td class="value">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="value">{{ $order->vehicle->number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Order Time</td>
                    <td class="label">Ordered By</td>
                </tr>
                <tr>
                    <td class="value">{{ $order->created_at->format('h:i A') }}</td>
                    <td class="value">
                        {{ isset($order->createdBy->id) ? ($order->createdBy->name . ' ' . $order->createdBy->middle_name . ' ' . $order->createdBy->last_name) : 'N/A' }}
                    </td>
                </tr>
            </table>
        </div>

        {{-- Bill To / Ship To --}}
        <table style="margin-top:8px;">
            <tr>
                <td width="50%" valign="top">
                    <div class="box">
                        <div class="label mb-5">Bill To</div>
                        <div class="bold mt-5">{{ $order->billing_name ?? '' }}</div>
                        <div class="small">
                            {{ $order->billing_address_1 ?? '' }}
                        </div>
                        @if($order->billing_contact_number)
                        <div class="small mt-5 border-top pt-5">
                            Phone:
                            <span class="bold">{{ $order->billing_contact_number ?? '-' }}</span>
                        </div>
                        @endif
                    </div>
                </td>
                <td width="50%" valign="top">
                    <div class="box">
                        <div class="label mb-5">Ship To</div>
                        <div class="bold mt-5">{{ $order->shipping_name ?? '' }}</div>
                        <div class="small">
                            {{ $order->shipping_address_1 ?? '' }}
                        </div>
                        @if($order->shipping_contact_number)
                        <div class="small mt-5 border-top pt-5">
                            Phone:
                            <span class="bold">{{ $order->shipping_contact_number ?? '-' }}</span>
                        </div>
                        @endif
                        @if($order->alternate_name && !$order->delivery_to_store)
                        <div class="bold mt-5">{{ $order->alternate_name ?? '' }}</div>
                        <div class="small mt-5 border-top pt-5">
                            Phone:
                            <span class="bold">{{ $order->alternate_phone_number ?? '-' }}</span>
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- Items --}}
        <table class="bordered mt-10">
            <thead>
                <tr>
                    <th width="6%" class="text-center">#</th>
                    <th width="54%">Description</th>
                    <th width="10%" class="text-right">Qty</th>
                    @if($shouldShowPrice)
                        <th width="15%" class="text-right">Unit Price</th>
                        <th width="15%" class="text-right">Total</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                {{-- Order Items --}}
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
                        <td colspan="{{ $shouldShowPrice ? 5 : 3 }}" class="bold small bg-light" style="background-color:#f3f4f6;">{{ $taxSlabName }}</td>
                    </tr>
                    @foreach($items as $index => $item)
                        <tr>
                            <td class="text-center small" style="color:#6b7280;">{{ $loop->iteration }}</td>
                            <td>
                                <div class="bold" style="color:#111827;"> {{ $item->product->name }} -
                                    {{ $item->product->category->name ?? '' }} </div>
                                <div class="small" style="color:#6b7280;">Unit: {{ $item->unit->name }}</div>
                            </td>
                            <td class="text-right bold">{{ number_format($item->quantity, 2) }}</td>
                            @if($shouldShowPrice)
                                <td class="text-right bold" style="color:#6b7280;">
                                    {{ Helper::defaultCurrencySymbol() }}{{ number_format($item->ge_price, 2) }}
                                </td>
                                <td class="text-right bold" style="color:#111827;">
                                    {{ Helper::defaultCurrencySymbol() }}{{ number_format($item->ge_price * $item->quantity, 2) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach

                    @if($shouldShowPrice)
                        <tr style="background-color:#fafafa;">
                            <td colspan="4" class="text-right bold small text-muted">{{ $taxSlabName }} Subtotal</td>
                            <td class="text-right bold small text-muted">
                                {{ Helper::defaultCurrencySymbol() }}{{ number_format($groupSubtotal, 2) }}</td>
                        </tr>
                        @if($cgstPercent > 0 || $sgstPercent > 0)
                            <tr>
                                <td colspan="4" class="text-right bold small text-muted">CGST ({{ $cgstPercent }}%)</td>
                                <td class="text-right bold small text-muted">
                                    {{ Helper::defaultCurrencySymbol() }}{{ number_format($groupCgst, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right bold small text-muted">SGST ({{ $sgstPercent }}%)</td>
                                <td class="text-right bold small text-muted">
                                    {{ Helper::defaultCurrencySymbol() }}{{ number_format($groupSgst, 2) }}</td>
                            </tr>
                        @endif
                    @endif
                @endforeach
                
                @if($shouldShowPrice)
                    <tr style="background-color:#fafafa;">
                        <td colspan="4" class="text-right bold small">Products Total</td>
                        <td class="text-right bold small" style="color:#111827;">
                            {{ Helper::defaultCurrencySymbol() }}{{ number_format($productsGrandTotal, 2) }}</td>
                    </tr>
                @endif

                {{-- Other Items --}}
                @if($order->otherItems->count() > 0)
                    <tr>
                        <td colspan="{{ $shouldShowPrice ? 5 : 3 }}" class="bold small bg-light"
                            style="background-color:#f3f4f6; border-top: 1px solid #e5e7eb;">Other Items</td>
                    </tr>
                    @php $otherTotal = 0; @endphp
                    @foreach($order->otherItems as $index => $oi)
                        @php
                            $o = $oi->otherItem;
                            $priceIncludesTax = (int) ($oi->price_includes_tax ?? ($o ? $o->price_includes_tax : 0));

                            $unitPrice = floatval($oi->unit_price);
                            $qty = floatval($oi->quantity);
                            $lineTotal = $unitPrice * $qty;

                            $cgstAmt = 0;
                            $sgstAmt = 0;
                            $cgstPct = 0;
                            $sgstPct = 0;

                            $taxSlab = $o ? $o->taxSlab : null;
                            if ($taxSlab) {
                                $cgstPct = $taxSlab->cgst;
                                $sgstPct = $taxSlab->sgst;
                            }
                            $totalTaxPct = $cgstPct + $sgstPct;

                            // Logic Change: Treat stored price as Tax Inclusive if tax exists
                            if ($totalTaxPct > 0) {
                                $baseTotal = $lineTotal / (1 + $totalTaxPct / 100);
                                $exclUnitPrice = $qty > 0 ? ($baseTotal / $qty) : 0;
                            } else {
                                $baseTotal = $lineTotal;
                                $exclUnitPrice = $unitPrice;
                            }

                            $taxAmt = $baseTotal * $totalTaxPct / 100;
                            if ($totalTaxPct > 0 && $taxAmt > 0) {
                                $cgstAmt = $taxAmt * ($cgstPct / $totalTaxPct);
                                $sgstAmt = $taxAmt * ($sgstPct / $totalTaxPct);
                            }

                            $otherTotal += $baseTotal;
                        @endphp
                        <tr>
                            <td class="text-center small" style="color:#6b7280;">{{ $index + 1 }}</td>
                            <td>
                                <div class="bold" style="color:#111827;">{{ $o ? $o->name : 'Item' }}</div>
                            </td>
                            <td class="text-right bold">{{ number_format($oi->quantity, 2) }}</td>
                            @if($shouldShowPrice)
                                <td class="text-right bold" style="color:#6b7280;">
                                    {{ Helper::defaultCurrencySymbol() }}{{ number_format($exclUnitPrice, 2) }}
                                </td>
                                <td class="text-right bold" style="color:#111827;">
                                    {{ Helper::defaultCurrencySymbol() }}{{ number_format($baseTotal, 2) }}
                                </td>
                            @endif
                        </tr>
                        @if($shouldShowPrice)
                            <tr>
                                <td colspan="4" class="text-right bold small text-muted">CGST ({{ $cgstPct + 0 }}%)</td>
                                <td class="text-right bold small text-muted">
                                    {{ Helper::defaultCurrencySymbol() }}{{ number_format($cgstAmt, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right bold small text-muted">SGST ({{ $sgstPct + 0 }}%)</td>
                                <td class="text-right bold small text-muted">
                                    {{ Helper::defaultCurrencySymbol() }}{{ number_format($sgstAmt, 2) }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    @if($shouldShowPrice)
                        <tr style="background-color:#fafafa;">
                            <td colspan="4" class="text-right bold small">Other Items Subtotal</td>
                            <td class="text-right bold small">
                                {{ Helper::defaultCurrencySymbol() }}{{ number_format($otherTotal + $cgstAmt + $sgstAmt, 2) }}</td>
                        </tr>
                    @endif
                @endif
            </tbody>
        </table>

        {{-- Utencils Summary --}}
        @if($order->utencils_collected && !empty($utencilSummaries))
        <table class="bordered mt-10">
            <thead>
                <tr>
                    <th colspan="4" class="text-left" style="background-color:#f3f4f6;">Utencils Summary</th>
                </tr>
                <tr>
                    <th width="55%" class="text-left">Utencil</th>
                    <th width="15%" class="text-right">Sent</th>
                    <th width="15%" class="text-right">Received</th>
                    <th width="15%" class="text-right">Pending</th>
                </tr>
            </thead>
            <tbody>
                @foreach($utencilSummaries as $summary)
                    <tr>
                        <td class="small" style="color:#111827;">{{ $summary->utencil->name ?? ('#' . $summary->utencil_id) }}</td>
                        <td class="text-right small" style="color:#6b7280;">{{ number_format($summary->sent, 2) }}</td>
                        <td class="text-right small" style="color:#6b7280;">{{ number_format($summary->received, 2) }}</td>
                        <td class="text-right small bold" style="color:#111827;">{{ number_format($summary->pending, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Notes & Totals --}}
        <table style="margin-top:10px;">
            <tr>
                <td width="50%" valign="top">
                    <div class="box">
                        <div class="label mb-5">@if($shouldShowPrice) Deposit / Notes @else Notes @endif</div>
                        <div class="small border-bottom mb-5 pb-5">
                            Utencils Collected: {{ $order->utencils_collected ? 'Yes' : 'No' }}
                        </div>
                        <div class="small" style="min-height:20px;">{{ $order->remarks }}</div>
                    </div>
                </td>
                @if($shouldShowPrice)
                    <td width="50%" valign="top">
                        <div class="box">
                            <table>
                                <tr>
                                    <td class="small">Subtotal:</td>
                                    <td class="text-right small bold">
                                        {{ Helper::defaultCurrencySymbol() }}{{ number_format($order->total_amount, 2) }}
                                    </td>
                                </tr>
                                @foreach($order->charges as $ac)
                                    <tr>
                                        <td class="small">{{ $ac->title }}:</td>
                                        <td class="text-right small bold">
                                            {{ Helper::defaultCurrencySymbol() }}{{ number_format($ac->amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                                @if($order->discount_amount > 0)
                                    <tr>
                                        <td class="small">Discount:</td>
                                        <td class="text-right small bold">
                                            -{{ Helper::defaultCurrencySymbol() }}{{ number_format($order->discount_amount, 2) }}
                                        </td>
                                    </tr>
                                @endif
                                @php $grandTotal = $order->net_amount; @endphp
                                <tr>
                                    <td class="small bold border-top pt-5">Grand Total:</td>
                                    <td class="text-right small bold border-top pt-5">
                                        {{ Helper::defaultCurrencySymbol() }}{{ number_format($grandTotal, 2) }}
                                    </td>
                                </tr>
                                @if(!in_array($order->order_type, ['franchise', 'dealer']))
                                    <tr>
                                        <td class="small bold" style="color:#15803d;">Amount Collected:</td>
                                        <td class="text-right small bold" style="color:#15803d;">
                                            {{ Helper::defaultCurrencySymbol() }}{{ number_format($order->amount_collected, 2) }}
                                        </td>
                                    </tr>
                                    @php $pending = $grandTotal - $order->amount_collected; @endphp
                                    <tr>
                                        <td class="small bold">
                                            {{ $pending > 0 ? 'Pending:' : 'Balance:' }}
                                        </td>
                                        <td class="text-right small bold">
                                            {{ Helper::defaultCurrencySymbol() }}{{ number_format(abs($pending), 2) }}
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </td>
                @endif
            </tr>
        </table>

        {{-- Signatures --}}
        <table style="margin-top:12px;">
            <tr>
                <td width="50%" class="text-center border-top pt-5">
                    <div class="small bold">Customer Sign</div>
                    @if($order->customer_signature && is_file(public_path('storage/order-signatures/' . $order->customer_signature)))
                        <img src="{{ public_path('storage/order-signatures/' . $order->customer_signature) }}"
                             alt="Customer Signature"
                             style="margin-top:4px;max-height:40px;">
                    @endif
                </td>
                <td width="50%" class="text-center border-top pt-5">
                    <div class="small bold">Driver Sign</div>
                    @if($order->driver_signature && is_file(public_path('storage/order-signatures/' . $order->driver_signature)))
                        <img src="{{ public_path('storage/order-signatures/' . $order->driver_signature) }}"
                             alt="Driver Signature"
                             style="margin-top:4px;max-height:40px;">
                    @endif
                </td>
            </tr>
        </table>

        {{-- Footer --}}
        <div class="text-right mt-10 border-top pt-5">
            <div class="bold small" style="color:#b91c1c;">Thank You! — Farki</div>
            {{-- <div class="small" style="color:#6b7280;">વસ્તુ ખરીદ્યા બાદ પરત લેવામાં આવશે નહીં.</div> --}}
        </div>
    </div>
</body>
</html>