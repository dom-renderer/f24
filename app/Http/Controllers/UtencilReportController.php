<?php

namespace App\Http\Controllers;

use App\Models\OrderUtencilHistory;
use App\Models\Utencil;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UtencilReportExport;

class UtencilReportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Utencil Movement Report";
        $page_description = "Track sent, received, and pending utencils with powerful filters.";

        $utencils = Utencil::orderBy('name')->pluck('name', 'id');
        $stores = Store::orderBy('name')->pluck('name', 'id');
        $dealers = User::role('Dealer')->orderBy('name')->pluck('name', 'id');

        return view('utencil-report.index', compact(
            'page_title',
            'page_description',
            'utencils',
            'stores',
            'dealers'
        ));
    }

    public function ajax(Request $request)
    {
        $query = OrderUtencilHistory::query()
            ->with(['order.senderStore', 'order.receiverStore', 'order.dealer', 'utencil']);

        // Filters
        if ($request->filled('utencil_id')) {
            $query->where('utencil_id', $request->utencil_id);
        }

        if ($request->filled('store_id')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('sender_store_id', $request->store_id)
                  ->orWhere('receiver_store_id', $request->store_id);
            });
        }

        if ($request->filled('dealer_id')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('dealer_id', $request->dealer_id);
            });
        }

        if ($request->filled('type')) {
            if ($request->type === 'sent') {
                $query->where('type', OrderUtencilHistory::TYPE_SENT);
            } elseif ($request->type === 'received') {
                $query->where('type', OrderUtencilHistory::TYPE_RECEIVED);
            }
        }

        if ($request->filled('status')) {
            $status = (int) $request->status;
            $query->whereHas('order', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $start = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $end = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            }
        }

        // Pre-compute pending per order+utencil for quick lookup
        $pendingMap = OrderUtencilHistory::select(
                'order_id',
                'utencil_id',
                DB::raw('SUM(CASE WHEN type = '.OrderUtencilHistory::TYPE_SENT.' THEN quantity ELSE 0 END) as sent_qty'),
                DB::raw('SUM(CASE WHEN type = '.OrderUtencilHistory::TYPE_RECEIVED.' THEN quantity ELSE 0 END) as received_qty')
            )
            ->groupBy('order_id', 'utencil_id')
            ->get()
            ->keyBy(function ($row) {
                return $row->order_id . '|' . $row->utencil_id;
            });

        return datatables()
            ->eloquent($query)
            ->addColumn('order_number', fn ($row) => $row->order->order_number ?? 'N/A')
            ->addColumn('utencil_name', fn ($row) => $row->utencil->name ?? 'N/A')
            ->addColumn('direction', function ($row) {
                if ($row->type === OrderUtencilHistory::TYPE_SENT) {
                    return '<span class="badge bg-primary">Sent</span>';
                }
                if ($row->type === OrderUtencilHistory::TYPE_RECEIVED) {
                    return '<span class="badge bg-success">Received</span>';
                }
                return '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('sender_store', fn ($row) => $row->order->senderStore->name ?? 'N/A')
            ->addColumn('receiver_store', fn ($row) => $row->order->receiverStore->name ?? 'N/A')
            ->addColumn('dealer_name', fn ($row) => $row->order->dealer->name ?? 'N/A')
            ->addColumn('pending_qty', function ($row) use ($pendingMap) {
                $key = $row->order_id . '|' . $row->utencil_id;
                $agg = $pendingMap->get($key);
                if (!$agg) {
                    return '0';
                }
                $pending = max(0, (float) $agg->sent_qty - (float) $agg->received_qty);
                return number_format($pending, 2);
            })
            ->editColumn('quantity', fn ($row) => number_format($row->quantity, 2))
            ->editColumn('created_at', fn ($row) => $row->created_at ? $row->created_at->format('d-m-Y H:i') : '')
            ->rawColumns(['direction'])
            ->addIndexColumn()
            ->make(true);
    }

    public function export(Request $request)
    {
        $query = OrderUtencilHistory::query()
            ->with(['order.senderStore', 'order.receiverStore', 'order.dealer', 'utencil']);

        // Reuse same filters as ajax()
        if ($request->filled('utencil_id')) {
            $query->where('utencil_id', $request->utencil_id);
        }

        if ($request->filled('store_id')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('sender_store_id', $request->store_id)
                  ->orWhere('receiver_store_id', $request->store_id);
            });
        }

        if ($request->filled('dealer_id')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('dealer_id', $request->dealer_id);
            });
        }

        if ($request->filled('type')) {
            if ($request->type === 'sent') {
                $query->where('type', OrderUtencilHistory::TYPE_SENT);
            } elseif ($request->type === 'received') {
                $query->where('type', OrderUtencilHistory::TYPE_RECEIVED);
            }
        }

        if ($request->filled('status')) {
            $status = (int) $request->status;
            $query->whereHas('order', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $start = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $end = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            }
        }

        $rows = $query->get();

        return Excel::download(new UtencilReportExport($rows), 'utencil_report_'.date('Y-m-d_H-i').'.xlsx');
    }
}

