<!DOCTYPE html>
<html>

<head>
    <title>Ledger Statement - {{ $store->name }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .header {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Ledger Statement</h2>
        <p><strong>Store:</strong> {{ $store->name }} ({{ $store->code }})</p>
        <p><strong>Date:</strong> {{ date('d-m-Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $balance = $openingBalance ?? 0; @endphp
            @if(isset($openingBalance) && $openingBalance != 0)
            <tr>
                <td colspan="6" class="text-right"><strong>Opening Balance</strong></td>
                <td class="text-right"><strong>{{ number_format($openingBalance, 2) }}</strong></td>
            </tr>
            @endif
            @foreach($transactions as $txn)
                @php
                    if ($txn->type == 'debit')
                        $balance += $txn->amount;
                    else
                        $balance -= $txn->amount;
                @endphp
                <tr>
                    <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                    <td>{{ ucfirst($txn->type) }}</td>
                    <td>{{ $txn->reference_no }}</td>
                    <td>{{ $txn->notes }}</td>
                    <td class="text-right">{{ $txn->type == 'debit' ? number_format($txn->amount, 2) : '-' }}</td>
                    <td class="text-right">{{ $txn->type == 'credit' ? number_format($txn->amount, 2) : '-' }}</td>
                    <td class="text-right">{{ number_format($balance, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6" class="text-right"><strong>Closing Balance</strong></td>
                <td class="text-right"><strong>{{ number_format($balance, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>

</html>