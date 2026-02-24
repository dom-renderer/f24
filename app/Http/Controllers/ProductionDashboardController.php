<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderProduct;
use App\Models\OrderCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $page_title = "Bulk Orders";
        $page_description = "Aggregated view for material";

        $status = $request->status;
        $date_range = $request->date_range;
        $range_type = $request->range_type ?? 'this_week';

        // Filter 1 (Recent Bulk Orders)
        if ($request->range_type) {
            if ($range_type == 'today') {
                $start = Carbon::today();
                $end = Carbon::today();
            } elseif ($range_type == 'this_week') {
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
            } elseif ($range_type == 'next_week') {
                $start = Carbon::now()->addWeek()->startOfweek();
                $end = Carbon::now()->addWeek()->endOfWeek();
            } else {
                $theDate = explode(' - ', $request->date_range);
                $start = Carbon::createFromFormat('d/m/Y',$theDate[0] ?? date('d/m/Y'));
                $end = Carbon::createFromFormat('d/m/Y',$theDate[1] ?? date('d/m/Y'));
            }
            $date_range = $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
        } elseif ($date_range) {
            $dates = explode(' - ', $date_range);
            $start = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
            $end = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
            $range_type = 'custom';
        } else {
            // Default to current week
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $date_range = $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
            $range_type = 'this_week';
        }

        $query = Order::query();

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($start && $end) {
            $query->where(function($q) use ($start, $end) {
                // Filter by delivery schedule overlap or inclusion in the range
                $q->whereBetween('delivery_schedule_from', [$start, $end])
                  ->orWhereBetween('delivery_schedule_to', [$start, $end])
                  ->orWhere(function($sub) use ($start, $end) {
                      $sub->where('delivery_schedule_from', '<=', $start)
                          ->where('delivery_schedule_to', '>=', $end);
                  });
            });
        }
        $orderIds = $query->pluck('id');

        // Stats Cards Data
        $newOrdersTodayRecord = Order::whereDate('created_at', Carbon::today())->count();
        $newOrdersYesterdayRecord = Order::whereDate('created_at', Carbon::yesterday())->count();
        $newOrdersPercentage = $newOrdersYesterdayRecord > 0 
            ? round((($newOrdersTodayRecord - $newOrdersYesterdayRecord) / $newOrdersYesterdayRecord) * 100) 
            : ($newOrdersTodayRecord > 0 ? 100 : 0);

        $ordersNext3DaysCount = Order::whereBetween('delivery_schedule_from', [Carbon::today(), Carbon::today()->addDays(3)])->count();
        $highestCategoryNext3Days = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('order_products', 'order_items.product_id', '=', 'order_products.id')
            ->join('order_categories', 'order_products.category_id', '=', 'order_categories.id')
            ->whereBetween('orders.delivery_schedule_from', [Carbon::today(), Carbon::today()->addDays(3)])
            ->select('order_categories.name', DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('order_categories.name')
            ->orderBy('total_qty', 'desc')
            ->first();

        $ordersNext7DaysCount = Order::whereBetween('delivery_schedule_from', [Carbon::today(), Carbon::today()->addDays(7)])->count();
        $dispatchedTodayCount = Order::where('status', Order::STATUS_DISPATCHED)
            ->whereDate('updated_at', Carbon::today())
            ->count();

        // Recent Bulk Orders (Filtered by original query)
        $recentOrders = $query->with(['receiverStore', 'items.product', 'items.unit'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Summary Cards Data (Top Products + Units - Unfiltered)
        $summaryData = OrderItem::with(['product', 'unit'])
            // ->whereIn('order_id', $orderIds) // Removing filter so summary data isn't affected
            ->select('product_id', 'unit_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id', 'unit_id')
            ->orderBy('total_qty', 'desc')
            ->take(4)
            ->get();

        // ---------------------------------------------------------
        // Filter 2 (Chart and Breakdown Table)
        // ---------------------------------------------------------
        $chart_status = $request->chart_status;
        $chart_date_range = $request->chart_date_range;
        $chart_range_type = $request->chart_range_type ?? 'this_week';

        if ($request->chart_range_type) {
            if ($chart_range_type == 'today') {
                $chartStart = Carbon::today();
                $chartEnd = Carbon::today();
            } elseif ($chart_range_type == 'this_week') {
                $chartStart = Carbon::now()->startOfWeek();
                $chartEnd = Carbon::now()->endOfWeek();
            } elseif ($chart_range_type == 'next_week') {
                $chartStart = Carbon::now()->addWeek()->startOfweek();
                $chartEnd = Carbon::now()->addWeek()->endOfWeek();
            } else {
                $theDateChart = explode(' - ', $request->chart_date_range);
                $chartStart = Carbon::createFromFormat('d/m/Y',$theDateChart[0] ?? date('d/m/Y'));
                $chartEnd = Carbon::createFromFormat('d/m/Y',$theDateChart[1] ?? date('d/m/Y'));
            }
            $chart_date_range = $chartStart->format('d/m/Y') . ' - ' . $chartEnd->format('d/m/Y');
        } elseif ($chart_date_range) {
            $chartDates = explode(' - ', $chart_date_range);
            $chartStart = Carbon::createFromFormat('d/m/Y', trim($chartDates[0]))->startOfDay();
            $chartEnd = Carbon::createFromFormat('d/m/Y', trim($chartDates[1]))->endOfDay();
            $chart_range_type = 'custom';
        } else {
            // Default to current week
            $chartStart = Carbon::now()->startOfWeek();
            $chartEnd = Carbon::now()->endOfWeek();
            $chart_date_range = $chartStart->format('d/m/Y') . ' - ' . $chartEnd->format('d/m/Y');
            $chart_range_type = 'this_week';
        }

        $queryChart = Order::whereIn('status', [Order::STATUS_APPROVED]);

        if ($chartStart && $chartEnd) {
            $queryChart->where(function($q) use ($chartStart, $chartEnd) {
                $q->whereBetween('delivery_schedule_from', [$chartStart, $chartEnd])
                  ->orWhereBetween('delivery_schedule_to', [$chartStart, $chartEnd])
                  ->orWhere(function($sub) use ($chartStart, $chartEnd) {
                      $sub->where('delivery_schedule_from', '<=', $chartStart)
                          ->where('delivery_schedule_to', '>=', $chartEnd);
                  });
            });
        }
        $orderIdsChart = $queryChart->pluck('id');

        // Weekly Production Plan - Robust Day Mapping
        $dates = [];
        $current = $chartStart->copy();
        while ($current <= $chartEnd) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $chartRaw = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('order_products', 'order_items.product_id', '=', 'order_products.id')
            ->join('order_units', 'order_items.unit_id', '=', 'order_units.id')
            ->whereIn('order_id', $orderIdsChart)
            ->select(
                DB::raw('DATE(orders.delivery_schedule_from) as date'),
                'order_products.name as product_name',
                'order_units.name as unit_name',
                DB::raw('SUM(order_items.quantity) as total_qty')
            )
            ->groupBy('date', 'product_name', 'unit_name')
            ->get()
            ->map(function($item) {
                $item->display_name = $item->product_name . ' (' . $item->unit_name . ')';
                return $item;
            });

        $chartFormatted = [];
        foreach ($dates as $date) {
            $chartFormatted[$date] = $chartRaw->where('date', $date);
        }

        // Order Requirement Breakdown Table
        $breakdown = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('order_products', 'order_items.product_id', '=', 'order_products.id')
            ->join('order_units', 'order_items.unit_id', '=', 'order_units.id')
            ->join('order_categories', 'order_products.category_id', '=', 'order_categories.id')
            ->whereIn('order_id', $orderIdsChart)
            ->select(
                'order_categories.name as category_name',
                'order_products.name as product_name',
                'order_units.name as unit_name',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(order_items.quantity) as total_qty'),
                'orders.status'
            )
            ->groupBy('category_name', 'product_name', 'unit_name', 'orders.status')
            ->get();

        $statuses = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Dispatched',
            3 => 'Delivered',
            4 => 'Cancelled'
        ];

        return view('orders.dashboard', [
            'page_title' => $page_title,
            'page_description' => $page_description,
            'summaryData' => $summaryData,
            'chartData' => $chartFormatted,
            'breakdown' => $breakdown,
            'statuses' => $statuses,
            'status' => $status,
            'date_range' => $date_range,
            'range_type' => $range_type,
            'chart_status' => $chart_status,
            'chart_date_range' => $chart_date_range,
            'chart_range_type' => $chart_range_type,
            'labels' => array_map(fn($d) => date('D', strtotime($d)), $dates),
            'stats' => [
                'new_orders_today' => $newOrdersTodayRecord,
                'new_orders_percentage' => $newOrdersPercentage,
                'orders_next_3_days' => $ordersNext3DaysCount,
                'highest_category_3_days' => $highestCategoryNext3Days ? $highestCategoryNext3Days->name : 'N/A',
                'orders_next_7_days' => $ordersNext7DaysCount,
                'dispatched_today' => $dispatchedTodayCount,
            ],
            'recentOrders' => $recentOrders
        ]);
    }

    public function export(Request $request)
    {
        $status = $request->chart_status;
        $date_range = $request->chart_date_range;
        $range_type = $request->chart_range_type ?? 'this_week';

        if ($request->chart_range_type) {
            if ($range_type == 'today') {
                $start = Carbon::today();
                $end = Carbon::today();
            } elseif ($range_type == 'this_week') {
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
            } elseif ($range_type == 'next_week') {
                $start = Carbon::now()->addWeek()->startOfweek();
                $end = Carbon::now()->addWeek()->endOfWeek();
            } else {
                $theDate = explode(' - ', $request->chart_date_range);
                $start = Carbon::createFromFormat('d/m/Y',$theDate[0] ?? date('d/m/Y'));
                $end = Carbon::createFromFormat('d/m/Y',$theDate[1] ?? date('d/m/Y'));
            }
        } elseif ($date_range) {
            $dates = explode(' - ', $date_range);
            $start = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
            $end = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
        } else {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
        }

        $query = Order::query();

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($start && $end) {
            $query->where(function($q) use ($start, $end) {
                // Filter by delivery schedule overlap or inclusion in the range
                $q->whereBetween('delivery_schedule_from', [$start, $end])
                  ->orWhereBetween('delivery_schedule_to', [$start, $end])
                  ->orWhere(function($sub) use ($start, $end) {
                      $sub->where('delivery_schedule_from', '<=', $start)
                          ->where('delivery_schedule_to', '>=', $end);
                  });
            });
        }

        $orderIds = $query->pluck('id');

        $breakdown = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('order_products', 'order_items.product_id', '=', 'order_products.id')
            ->join('order_units', 'order_items.unit_id', '=', 'order_units.id')
            ->join('order_categories', 'order_products.category_id', '=', 'order_categories.id')
            ->whereIn('order_id', $orderIds)
            ->select(
                'order_categories.name as category_name',
                'order_products.name as product_name',
                'order_units.name as unit_name',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(order_items.quantity) as total_qty')
            )
            ->groupBy('category_name', 'product_name', 'unit_name')
            ->get();

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\OrderRequirementExport($breakdown), 'production_breakdown_'.date('Y-m-d_H-i').'.xlsx');
    }
}
