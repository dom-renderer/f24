@php
    $logoPath = public_path('assets/pdf-logo.png');
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
    }

    $fmt = fn($n, $d=3) => number_format((float)$n, $d, '.', '');
    $fmt2 = fn($n) => number_format((float)$n, 2, '.', '');

    $allSlabs = \App\Models\TaxSlab::query()
    ->get()
    ->mapWithKeys(function ($slab) {
        return [
            $slab->id => [
                'name' => $slab->name,
                'sum' => $slab->cgst + $slab->sgst,
                'product_tax_total' => 0,
                'product_total' => 0,
                'data' => []
            ]
        ];
    })
    ->toArray();
@endphp

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice['invoice_no'] ?? '' }}</title>
    <style>
        /* ---------- PAGE ---------- */
        @page {
            size: A4;
            /* Adjust these to match your PDF margins exactly */
            margin: 10mm 10mm 14mm 10mm; /* bottom a bit bigger for footer */
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .clearfix:after { content: ""; display: table; clear: both; }
        .bold { font-weight: 700; }
        .muted { color: #333; }

        /* ---------- HEADER ---------- */
        .top-row { border-bottom: 1px solid #000; padding-bottom: 6px; margin-bottom: 8px; }
        .title-left { float: left; width: 22%; }
        .title-left .tax { font-weight: 700; font-size: 12px; letter-spacing: 0.2px; }
        .logo-mid { float: left; width: 38%; text-align: center; }
        .logo-mid img { height: 46px; }
        .seller-right { float: right; width: 40%; text-align: right; line-height: 1.25; }

        /* ---------- BILL/SHIP/INVOICE META ---------- */
        .meta-wrap { border: 1px solid #000; }
        .meta-row { width: 100%; border-collapse: collapse; }
        .meta-row td { vertical-align: top; padding: 6px; }
        .meta-row td + td { border-left: 1px solid #000; }
        .meta-h { font-weight: 700; margin-bottom: 4px; }

        .col-bill { width: 34%; }
        .col-ship { width: 34%; }
        .col-inv  { width: 32%; }

        /* ---------- TABLE ---------- */
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th, table.items td {
            border: 1px solid #000;
            padding: 4px 4px;
            vertical-align: top;
        }
        table.items th { font-weight: 700; text-align: center; }
        .t-center { text-align: center; }
        .t-right  { text-align: right; }
        .t-left   { text-align: left; }

        /* prevent ugly row splitting mid-row where possible */
        tr { page-break-inside: avoid; }

        /* ---------- BOTTOM SUMMARY AREA ---------- */
        .bottom-grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .bottom-grid td { border: 1px solid #000; vertical-align: top; padding: 6px; }

        .amount-words { font-size: 10.5px; }
        .mini-block { font-size: 10.5px; line-height: 1.25; }

        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 2px 0; border: none; }
        .grand { font-size: 16px; font-weight: 800; text-align: right; padding-top: 6px; }

        /* ---------- SIGNATURES & TERMS ---------- */
        .sign-row { width: 100%; border-collapse: collapse; }
        .sign-row td { border: 1px solid #000; padding: 10px 6px; height: 38px; }
        .terms { border: 1px solid #000; border-top: none; padding: 8px 6px; font-size: 10.5px; line-height: 1.35; }

        /* ---------- FOOTER PAGE NUMBER ---------- */
        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -8mm;   /* aligns in the @page bottom margin area */
            height: 10mm;
            text-align: right;
            font-size: 11px;
        }
    </style>
</head>

<body>

    {{-- Footer page numbers (DOMPDF supported) --}}
    <div class="footer">
        <script type="text/php">
            if (isset($pdf)) {
                $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                $pdf->page_text(520, 820, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 10, array(0,0,0));
            }
        </script>
    </div>

    {{-- TOP HEADER --}}
    <div class="top-row clearfix">
        <div class="title-left">
            <div class="tax">TAX INVOICE</div>
        </div>

        <div class="logo-mid">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        </div>

        <div class="seller-right">
            <div class="bold">{{ $setting->company_name ?? '' }}</div>
            <div>{!! $setting->address ?? '' !!}</div>
            <div>GST: {{ $setting->gstin ?? '' }}</div>
            <div>CIN: {{ $setting->cin ?? '' }}</div>
        </div>
    </div>

    {{-- BILL/SHIP/INVOICE META --}}
    <div class="meta-wrap">
        <table class="meta-row">
            <tr>
                <td class="col-bill">
                    <div class="meta-h">Bill To:</div>
                    <div class="bold">{{ $order->billing_name ?? '' }}</div>
                    <div>{{ $order->billing_address_1 ?? '' }}</div>
                    <div>GSTIN: {{ $order->billing_gst_in ?? '' }}</div>
                    <div>MOB: {{ $order->billing_contact_number ?? '' }}</div>
                    <div>CIN: </div>
                </td>

                <td class="col-ship">
                    <div class="meta-h">Ship To:</div>
                    <div class="bold">{{ $order->shipping_name ?? '' }}</div>
                    <div>{{ $order->shipping_address_1 ?? '' }}</div>
                    <div>GSTIN: {{ $order->shipping_gst_in ?? '' }}</div>
                    <div>MOB: {{ $order->shipping_contact_number ?? '' }}</div>
                    <div>CIN: </div>
                </td>

                <td class="col-inv">
                    <div><span class="bold">Invoice No:</span> {{ $order->order_number ?? '' }}</div>
                    <div><span class="bold">Bill Date:</span> {{ date('d-M-Y') }}</div>
                    <div><span class="bold">PO Referance Number:</span> {{ $order->po_reference_number ?? '' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ITEMS TABLE (NO THEAD => no repeating headers) --}}
    <table class="items">
        <tr>
            <th style="width: 4%;">No</th>
            <th style="width: 22%;">Item</th>
            <th style="width: 9%;">HSN</th>
            <th style="width: 6%;">Qty</th>
            <th style="width: 7%;">Unit</th>
            <th style="width: 8%;">Rate</th>
            <th style="width: 9%;">Total Amt</th>
            <th style="width: 6%;">GST%</th>
            <th style="width: 7%;">CGST</th>
            <th style="width: 7%;">SGST</th>
            <th style="width: 6%;">IGST</th>
            <th style="width: 6%;">CESS</th>
            <th style="width: 10%;">Total Amt(With Tax)</th>
            <th style="width: 3%;">Remark</th>
        </tr>

        @foreach($order->items as $i => $item)
            <tr>
                <td class="t-center">{{ $i + 1 }}</td>
                <td class="t-left">{{ $item->product->name ?? '' }}</td>
                <td class="t-center">{{ $item->product->hsn ?? '' }}</td>
                <td class="t-right">{{ $item->quantity ?? '' }}</td>
                <td class="t-center">{{ $item->unit->name ?? '' }}</td>
                <td class="t-right">{{ number_format($item->ge_price, 2) }}</td>
                <td class="t-right">{{ number_format($item->ge_price * $item->quantity, 2) }}</td>
                <td class="t-right">{{ ($item->cgst ?? 0) + $item->sgst ?? 0 }}</td>

                @php
                    $sgstAmt = $item->gi_price * $item->quantity * ($item->sgst ?? 0) / 100;
                    $cgstAmt = $item->gi_price * $item->quantity * ($item->cgst ?? 0) / 100;

                    if (isset($allSlabs[$item->tax_slab_id])) {
                        if (!isset($allSlabs[$item->tax_slab_id]['data']['product_tax_total'])) {
                            $allSlabs[$item->tax_slab_id]['data']['product_tax_total'] = ($cgstAmt + $sgstAmt);
                        } else {
                            $allSlabs[$item->tax_slab_id]['data']['product_tax_total'] += ($cgstAmt + $sgstAmt);
                        }

                        if (!isset($allSlabs[$item->tax_slab_id]['data']['product_total'])) {
                            $allSlabs[$item->tax_slab_id]['data']['product_total'] = $item->subtotal;
                        } else {
                            $allSlabs[$item->tax_slab_id]['data']['product_total'] += $item->subtotal;
                        }
                    }
                @endphp

                <td class="t-right">{{ number_format($cgstAmt, 2) }}</td>
                <td class="t-right">{{ number_format($sgstAmt, 2) }}</td>
                <td class="t-right">{{ 0 }}</td>
                <td class="t-right">{{ 0 }}</td>
                <td class="t-right">{{ number_format($item->subtotal, 2) }}</td>
                <td></td>
            </tr>
        @endforeach
    </table>

    {{-- OTHER ITEM TABLE (NO THEAD => no repeating headers) --}}
    <table class="items">
        <tr>
            <th style="width: 4%;">No</th>
            <th style="width: 22%;">Item</th>
            <th style="width: 9%;">HSN</th>
            <th style="width: 6%;">Qty</th>
            <th style="width: 7%;">Unit</th>
            <th style="width: 8%;">Rate</th>
            <th style="width: 9%;">Total Amt</th>
            <th style="width: 6%;">GST%</th>
            <th style="width: 7%;">CGST</th>
            <th style="width: 7%;">SGST</th>
            <th style="width: 6%;">IGST</th>
            <th style="width: 6%;">CESS</th>
            <th style="width: 10%;">Total Amt(With Tax)</th>
            <th style="width: 3%;">Remark</th>
        </tr>

        @foreach($order->otherItems as $i => $item)
            @php
                $o = $item->otherItem;
                $priceIncludesTax = (int) ($item->price_includes_tax ?? ($o ? $o->price_includes_tax : 0));
                $unitPrice = floatval($item->unit_price);
                $qty = floatval($item->quantity);
                $lineTotal = $unitPrice * $qty;
                $tSlab = \App\Models\TaxSlab::find($item->tax_slab_id);
                $priceBt = 0;

                if ($tSlab) {
                    if ($item->price_includes_tax) {
                        $totalTaxPct = $tSlab->cgst + $tSlab->sgst;
                        if ($totalTaxPct > 0) {
                            $priceBt = $unitPrice / (1 + $totalTaxPct / 100);
                        }
                    } else {
                        $priceBt = $unitPrice;
                    }
                }

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

                if (isset($allSlabs[$item->tax_slab_id])) {
                    if (!isset($allSlabs[$item->tax_slab_id]['data']['product_tax_total'])) {
                        $allSlabs[$item->tax_slab_id]['data']['product_tax_total'] = ($cgstAmt + $sgstAmt);
                    } else {
                        $allSlabs[$item->tax_slab_id]['data']['product_tax_total'] += ($cgstAmt + $sgstAmt);
                    }

                    if (!isset($allSlabs[$item->tax_slab_id]['data']['product_total'])) {
                        $allSlabs[$item->tax_slab_id]['data']['product_total'] = ($priceBt * $qty);
                    } else {
                        $allSlabs[$item->tax_slab_id]['data']['product_total'] += ($priceBt * $qty);
                    }
                }
            @endphp

            <tr>
                <td class="t-center">{{ $i + 1 }}</td>
                <td class="t-left">{{ $item->otherItem->name ?? '' }}</td>
                <td class="t-center">{{ $item->otherItem->hsn ?? '' }}</td>
                <td class="t-right">{{ $item->quantity ?? '' }}</td>
                <td class="t-center"></td>
                <td class="t-right">{{ number_format($priceBt, 2) }}</td>
                <td class="t-right">{{ number_format($exclUnitPrice * $item->quantity, 2) }}</td>
                <td class="t-right">{{ ($cgstPct ?? 0) + ($sgstPct ?? 0) }}</td>
                <td class="t-right">{{ number_format($cgstAmt, 2) }}</td>
                <td class="t-right">{{ number_format($sgstAmt, 2) }}</td>
                <td class="t-right">{{ 0 }}</td>
                <td class="t-right">{{ 0 }}</td>
                <td class="t-right">{{ number_format(($exclUnitPrice * $item->quantity) + $cgstAmt + $sgstAmt, 2) }}</td>
                <td></td>
            </tr>
        @endforeach
    </table>

    {{-- AMOUNT WORDS + TAX TOTALS + BANK + SUMMARY --}}
    <table class="bottom-grid">
        <tr>
            <td style="width: 60%;">
                <div class="amount-words"><span class="bold">Amount In Words:</span> {{ \App\Helpers\Helper::amountInWords(round($order->net_amount)) }}</div>

                <div style="height:6px;"></div>
                <div class="mini-block">
                    <div><span class="bold">CGST Total :</span> {{ number_format($order->cgst_amount, 2) }} &nbsp;&nbsp; <span class="bold">SGST Total :</span> {{ number_format($order->sgst_amount, 2) }}</div>
                    @foreach ($allSlabs as $slabId => $slabData)
                        <div><span class="bold">{{ $slabData['name'] }} Value :</span> {{ number_format($slabData['data']['product_total'] ?? 0, 3) }} &nbsp;&nbsp; </div>
                    @endforeach
                </div>

                <div style="height:8px;"></div>
                <div class="mini-block">
                    <div class="bold">BANK DETAILS</div>
                    <div>Account No : {{ $bank['account_no'] ?? '' }}</div>
                    <div>IFSC Code : {{ $bank['ifsc'] ?? '' }}</div>
                    <div>Bank Name : {{ $bank['name'] ?? '' }}</div>
                </div>
            </td>

            <td style="width: 40%;">
                <div class="bold" style="margin-bottom:6px;">Summary</div>
                <table class="summary-table">
                    <tr><td>Gross Total</td><td class="t-right">{{ number_format($order->total_amount, 3) }}</td></tr>
                    @foreach ($allSlabs as $slabId => $slabData)
                        <tr><td>{{ $slabData['name'] }}</td><td class="t-right">{{ number_format($slabData['data']['product_tax_total'] ?? 0, 3) }}</td></tr>
                    @endforeach
                    @foreach ($order->charges as $ac)
                    <tr><td>{{ $ac->title }}</td><td class="t-right">{{ number_format($ac->amount, 3) }}</td></tr>
                    @endforeach
                    <tr><td>Total TCS(0%)</td><td class="t-right">{{ '0.000' }}</td></tr>
                    <tr><td>Total TDS(0%)</td><td class="t-right">{{ '0.000' }}</td></tr>
                    <tr><td>Total Other Taxes</td><td class="t-right">{{ $totals['other_taxes'] ?? '0.000' }}</td></tr>
                    <tr><td>Discounts</td><td class="t-right">{{ number_format($order->discount_amount ?? 0, 3) }}</td></tr>
                    <tr><td>Amount Recieved</td><td class="t-right">{{ number_format($order->amount_collected, 3) }}</td></tr>
                    <tr>
                        <td><h2>Grand Total</h2></td>
                        <td class="t-right"><h2>{{ number_format($order->net_amount, 3) }}</h2></td>
                    </tr>
                </table>

                <div class="grand">
                    {{ $totals['grand_total'] ?? '' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- SIGNATURES --}}
    <table class="sign-row">
        <tr>
            <td style="width:50%; text-align:center;">
                <div class="bold" style="margin-top:18px;">Customer's Seal And Signature</div>
            </td>
            <td style="width:50%; text-align:center;">
                <div class="bold" style="margin-top:18px;">Authorised Signature</div>
            </td>
        </tr>
    </table>

    {{-- CUSTOM CHARGES (EMPTY GRID LIKE PDF) --}}
    <table class="items" style="margin-top:0; border-top:none;">
        <tr>
            <th colspan="3" class="t-left">Custom Charges and Tax Summary :</th>
        </tr>
        <tr>
            <th style="width:50%;">Description</th>
            <th style="width:30%;">Name</th>
            <th style="width:20%;">Amount &nbsp;&nbsp; Tax(%)</th>
        </tr>
        {{-- Add rows if needed --}}
        <tr>
            <td style="height:28px;"></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    {{-- TERMS --}}
    <div class="terms">
        <div class="bold">Terms &amp; Conditions</div>
        <div class="bold">SUBJECT TO AHMEDABAD JURISDICTION.</div>
        <div class="bold">NOTE:THIS IS COMPUTER GENERATED INVOICE.</div>
        <div class="bold">REMARKS:</div>
    </div>

</body>
</html>