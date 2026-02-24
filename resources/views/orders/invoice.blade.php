<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Invoice - #{{ $order->order_number }}</title>
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
            border: 1px solid #e0e0e0;
            padding: 12px 14px;
        }

        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        .left {
            float: left;
        }

        .right {
            float: right;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .small {
            font-size: 9px;
        }

        .bold {
            font-weight: bold;
        }

        .mt-5 {
            margin-top: 5px;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mb-5 {
            margin-bottom: 5px;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        .border-top {
            border-top: 1px solid #e0e0e0;
        }

        .border-bottom {
            border-bottom: 1px solid #e0e0e0;
        }

        .box {
            border: 1px solid #e0e0e0;
            padding: 6px 8px;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 4px 5px;
        }

        th {
            background-color: #f5f5f5;
            font-size: 10px;
        }

        .bordered td,
        .bordered th {
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
        <div class="clearfix border-bottom pb-5 mb-5">
            <div class="left">
                <div class="bold" style="font-size:20px;color:#b91c1c;">Farki</div>
                <div class="small" style="color:#ef4444;">હરપલ બને ઉત્સવ...</div>
                {{-- <div class="mt-10 small" style="color:#4b5563;">
                    <div class="label mb-5">From:</div>
                    <div class="bold" style="color:#374151;">
                        {{ $order->senderStore->name ?? 'Farki Central Store' }}
                    </div>
                    <div>{{ $order->senderStore->address1 ?? '' }}</div>
                    @if($order->senderStore->address2)
                        <div>{{ $order->senderStore->address2 }}</div>
                    @endif
                    <div>Phone: {{ $order->senderStore->mobile ?? '-' }}</div>
                </div> --}}
            </div>
            <div class="right text-right">
                <div class="bold" style="font-size:20px;color:#111827;">
                    @if(in_array($order->order_type, ['franchise', 'dealer']))
                        PURCHASE ORDER
                    @else
                        INVOICE
                    @endif
                </div>
                <div class="mt-10 small">
                    <div>
                        @if(in_array($order->order_type, ['franchise', 'dealer']))
                            PO No:
                        @else
                            Invoice No:
                        @endif
                        <span class="bold" style="color:#111827;">#{{ $order->order_number }}</span>
                    </div>
                    <div>Date:
                        <span class="bold" style="color:#111827;">
                            {{ $order->created_at->format('d M Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Billing and Shipping --}}
        <table>
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

        {{-- Items Table --}}
        <table class="bordered mt-10">
            <thead>
                <tr>
                    <th width="6%" class="text-center">#</th>
                    <th width="54%">Description</th>
                    <th width="10%" class="text-right">Qty</th>
                    <th width="15%" class="text-right">Unit Price</th>
                    <th width="15%" class="text-right">Total</th>
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
                        <td colspan="5" class="bold small bg-light" style="background-color:#f3f4f6;">{{ $taxSlabName }}</td>
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
                            <td class="text-right bold" style="color:#6b7280;">
                                {{ Helper::defaultCurrencySymbol() }}{{ number_format($item->ge_price, 2) }}
                            </td>
                            <td class="text-right bold" style="color:#111827;">
                                {{ Helper::default  CurrencySymbol() }}{{ number_format($item->ge_price * $item->quantity, 2) }}
                            </td>
                        </tr>
                    @endforeach

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
                @endforeach

                <tr style="background-color:#fafafa;">
                    <td colspan="4" class="text-right bold small">Products Total</td>
                    <td class="text-right bold small" style="color:#111827;">
                        {{ Helper::defaultCurrencySymbol() }}{{ number_format($productsGrandTotal, 2) }}</td>
                </tr>
                
                {{-- Other Items --}}
                @if($order->otherItems->count() > 0)
                    <tr>
                        <td colspan="5" class="bold small bg-light"
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
                            <td class="text-right bold" style="color:#6b7280;">
                                {{ Helper::defaultCurrencySymbol() }}{{ number_format($exclUnitPrice, 2) }}
                            </td>
                            <td class="text-right bold" style="color:#111827;">
                                {{ Helper::defaultCurrencySymbol() }}{{ number_format($baseTotal, 2) }}
                            </td>
                        </tr>
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
                    @endforeach
                    <tr style="background-color:#fafafa;">
                        <td colspan="4" class="text-right bold small">Other Items Subtotal</td>
                        <td class="text-right bold small">
                            {{ Helper::defaultCurrencySymbol() }}{{ number_format($otherTotal + $cgstAmt + $sgstAmt, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Totals --}}
        <table style="margin-top:10px;">
            <tr>
                <td width="50%" valign="top">
                    <div class="box">
                        <div class="label mb-5">Deposit / Notes</div>
                        <div class="small border-bottom pb-5 mb-5">
                            Utencils Collected: {{ $order->utencils_collected ? 'Yes' : 'No' }}
                        </div>
                        <div class="small" style="min-height:20px;">{{ $order->remarks }}</div>
                    </div>
                </td>
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
            </tr>
        </table>

        {{-- Footer --}}
        <div class="mt-10 pt-10 border-top text-center">
            <div class="small bold" style="color:#b91c1c;">Thank You for Your Business!</div>
            <div class="small" style="color:#9ca3af;">This is a system-generated invoice.</div>
        </div>
    </div>
</body>

</html>