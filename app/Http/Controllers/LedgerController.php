<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\LedgerTransaction;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LedgerController extends Controller
{
    protected $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Display a listing of the resource.
     * Shows all stores with their balances.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Store::query()
                ->withSum([
                    'ledgerTransactions as total_debit' => function ($q) {
                        $q->where('type', 'debit')->where('status', 'active');
                    }
                ], 'amount')
                ->withSum([
                    'ledgerTransactions as total_credit' => function ($q) {
                        $q->where('type', 'credit')->where('status', 'active');
                    }
                ], 'amount');

            return Datatables::of($query)
                ->addColumn('balance', function ($row) {
                    $balance = ($row->total_debit ?? 0) - ($row->total_credit ?? 0);
                    return number_format($balance, 2);
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('ledger.show', $row->id) . '" class="btn btn-primary btn-sm">View Statement</a>';
                })
                ->make(true);
        }
        return view('ledger.index');
    }

    /**
     * Display the specified resource.
     * Shows detailed statement for a store.
     */
    public function show($id, Request $request)
    {
        $store = Store::findOrFail($id);

        if ($request->ajax()) {
            $query = LedgerTransaction::where('store_id', $id)
                ->where('status', 'active')
                ->with(['order', 'payment']);

            if ($request->filled('date_range')) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) == 2) {
                    try {
                        $start = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
                        $end = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
                        $query->whereBetween('txn_date', [$start, $end]);
                    } catch (\Exception $e) {}
                }
            }

            $query->orderBy('txn_date', 'desc')
                ->orderBy('id', 'desc');

            return Datatables::of($query)
                ->editColumn('txn_date', fn($q) => $q->txn_date->format('d-m-Y'))
                ->editColumn('amount', fn($q) => number_format($q->amount, 2))
                ->editColumn('type', fn($q) => ucfirst($q->type))
                ->make(true);
        }

        $balance = $store->balance; // Uses the accessor we added

        return view('ledger.show', compact('store', 'balance'));
    }

    public function exportPdf($id, Request $request)
    {
        $store = Store::findOrFail($id);
        $query = LedgerTransaction::where('store_id', $id)
            ->where('status', 'active');
            
        $openingBalance = 0;
        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) == 2) {
                try {
                    $start = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
                    $end = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
                    $query->whereBetween('txn_date', [$start, $end]);
                    
                    $openingBalanceQuery = LedgerTransaction::where('store_id', $id)
                        ->where('status', 'active')
                        ->where('txn_date', '<', $start);
                    $debitSum = (clone $openingBalanceQuery)->where('type', 'debit')->sum('amount');
                    $creditSum = (clone $openingBalanceQuery)->where('type', 'credit')->sum('amount');
                    $openingBalance = $debitSum - $creditSum;
                } catch (\Exception $e) {}
            }
        }
        $transactions = $query->orderBy('txn_date', 'asc')->get();

        $pdf = \PDF::loadView('ledger.pdf', compact('store', 'transactions', 'openingBalance'));
        return $pdf->download('ledger_statement_' . $store->code . '.pdf');
    }

    public function exportExcel($id, Request $request)
    {
        // Simple CSV export using internal logic or simple library usage
        // Since prompt says maatwebsite/excel is available, best to use it if configured.
        // But for speed/reliability in "Agent" mode without creating Export classes,
        // use rap2hpoutre/fast-excel or manual CSV stream.
        // Let's use simple CSV stream response.

        $store = Store::findOrFail($id);
        $query = LedgerTransaction::where('store_id', $id)
            ->where('status', 'active')
            ->with(['order', 'payment']);

        $openingBalance = 0;
        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) == 2) {
                try {
                    $start = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
                    $end = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
                    $query->whereBetween('txn_date', [$start, $end]);
                    
                    $openingBalanceQuery = LedgerTransaction::where('store_id', $id)
                        ->where('status', 'active')
                        ->where('txn_date', '<', $start);
                    $debitSum = (clone $openingBalanceQuery)->where('type', 'debit')->sum('amount');
                    $creditSum = (clone $openingBalanceQuery)->where('type', 'credit')->sum('amount');
                    $openingBalance = $debitSum - $creditSum;
                } catch (\Exception $e) {}
            }
        }
        $transactions = $query->orderBy('txn_date', 'asc')->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=ledger_" . $store->code . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Date', 'Type', 'Amount', 'Reference', 'Description', 'Running Balance'];

        $callback = function () use ($transactions, $columns, $openingBalance) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            if ($openingBalance != 0) {
                fputcsv($file, [
                    '', 'Opening Balance', '', '', '', number_format($openingBalance, 2)
                ]);
            }

            $balance = $openingBalance;
            foreach ($transactions as $txn) {
                if ($txn->type == 'debit') {
                    $balance += $txn->amount;
                } else {
                    $balance -= $txn->amount;
                }

                fputcsv($file, [
                    $txn->txn_date->format('Y-m-d'),
                    ucfirst($txn->type),
                    $txn->amount,
                    $txn->reference_no,
                    $txn->notes,
                    $balance
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
