@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/daterangepicker.css') }}" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary-red: #ef4444;
        --primary-orange: #f97316;
        --primary-purple: #8b5cf6;
        --primary-blue: #3b82f6;
        --bg-gray: #f9fafb;
        --text-dark: #111827;
        --text-muted: #6b7280;
        --border-color: #e5e7eb;
    }

    body, .content-wrapper {
        font-family: 'Inter', sans-serif !important;
        background-color: var(--bg-gray) !important;
    }

    .dashboard-header {
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 0.25rem;
    }

    .page-subtitle {
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .btn-group-filter .btn {
        background: white;
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
    }

    .btn-group-filter .btn.active {
        color: var(--primary-red);
        background: #fffafa;
        border-color: var(--primary-red);
        z-index: 1;
    }

    .daterange-picker-btn {
        background: white;
        border: 1px solid var(--border-color);
        color: var(--text-dark);
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .btn-export {
        background: #374151;
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-select-custom {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 0.5rem 2rem 0.5rem 1rem;
        font-size: 0.875rem;
        color: var(--text-dark);
        font-weight: 500;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.5rem center;
        background-size: 1.5em 1.5em;
        min-width: 160px;
    }

    .form-select-custom:focus {
        border-color: var(--primary-red);
        outline: none;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    /* Stat Cards */
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        height: 100%;
        position: relative;
    }

    .stat-card .icon-container {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.25rem;
    }

    .stat-card .icon-container i {
        font-size: 1.25rem;
    }

    .stat-card .watermark-icon {
        position: absolute;
        right: 1.5rem;
        top: 1.5rem;
        font-size: 3rem;
        opacity: 0.05;
        color: black;
    }

    .stat-card .label {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }

    .stat-card .value-row {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .stat-card .value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-dark);
    }

    .stat-card .unit {
        font-size: 0.875rem;
        color: var(--text-muted);
        font-weight: 400;
    }

    .stat-card .progress {
        height: 4px;
        background-color: #f3f4f6;
        border-radius: 2px;
        margin-bottom: 0.5rem;
    }

    .stat-card .capacity-info {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    /* Chart Container */
    .chart-box {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-top: 1.5rem;
    }

    .chart-box .title-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .chart-box h3 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
    }

    .chart-legend {
        display: flex;
        gap: 1rem;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .chart-canvas-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* Table Container */
    .table-box {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-top: 1.5rem;
    }

    .table-box h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 1.5rem;
    }

    .table-box .search-filter-row {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .search-input-group {
        position: relative;
        width: 280px;
    }

    .search-input-group i {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 0.875rem;
    }

    .search-input-group input {
        padding-left: 2.25rem;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        font-size: 0.875rem;
        height: 38px;
    }

    .btn-filter-icon {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .table thead th {
        background: white;
        text-transform: uppercase;
        font-size: 0.65rem;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border-color);
        padding: 0.75rem 1rem;
    }

    .table tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        font-size: 0.875rem;
    }

    .product-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .product-icon-small {
        width: 32px;
        height: 32px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-icon-small i {
        font-size: 0.875rem;
    }

    .product-name-bold {
        font-weight: 600;
        color: var(--text-dark);
    }

    .product-sub {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .qty-bold {
        font-weight: 700;
        color: var(--text-dark);
    }

    .status-badge-custom {
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-block;
    }

    .status-in-production { background: #eff6ff; color: #1d4ed8; }
    .status-pending-material { background: #fffbeb; color: #b45309; }
    .status-completed { background: #ecfdf5; color: #047857; }

    .view-full-list {
        text-align: center;
        padding-top: 1.5rem;
        color: var(--text-muted);
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
    }

    .view-full-list i {
        display: block;
        margin-top: 0.5rem;
        font-size: 1rem;
    }
    /* New Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card-v2 {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 140px;
        position: relative;
    }

    .stat-card-v2 .card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }

    .stat-card-v2 .card-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .stat-card-v2 .card-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .stat-card-v2 .card-icon-box.red { background: #fee2e2; color: #ef4444; }
    .stat-card-v2 .card-icon-box.blue { background: #dbeafe; color: #3b82f6; }
    .stat-card-v2 .card-icon-box.orange { background: #ffedd5; color: #f97316; }
    .stat-card-v2 .card-icon-box.green { background: #dcfce7; color: #22c55e; }

    .stat-card-v2 .card-value {
        font-size: 2.25rem;
        font-weight: 700;
        color: #111827;
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .stat-card-v2 .card-footer {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .stat-card-v2 .trend-up {
        color: #10b981;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .stat-card-v2 .info-badge {
        background: #eff6ff;
        color: #3b82f6;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.75rem;
    }

    /* Recent Bulk Orders Section */
    .recent-orders-card {
        background: white;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
    }

    .recent-orders-header {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f3f4f6;
    }

    .recent-orders-header h2 {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    .view-all-link {
        color: #ef4444;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .view-all-link:hover {
        color: #dc2626;
    }

    .recent-orders-table {
        width: 100%;
    }

    .recent-orders-table th {
        background: #f9fafb;
        padding: 0.75rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        text-align: left;
    }

    .recent-orders-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
    }

    .order-id {
        font-weight: 600;
        color: #374151;
    }

    .outlet-info {
        display: flex;
        flex-direction: column;
    }

    .outlet-name {
        font-weight: 700;
        color: #111827;
        font-size: 0.875rem;
    }

    .outlet-location {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .items-summary {
        display: flex;
        flex-direction: column;
    }

    .main-item {
        font-weight: 600;
        color: #4b5563;
        font-size: 0.875rem;
    }

    .other-items {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .delivery-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #4b5563;
        font-size: 0.875rem;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-pill::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .status-new { background: #eff6ff; color: #2563eb; }
    .status-new::before { background: #2563eb; }

    .status-process { background: #fff7ed; color: #ea580c; }
    .status-process::before { background: #ea580c; }

    .status-dispatched { background: #f0fdf4; color: #16a34a; }
    .status-dispatched::before { background: #16a34a; }

    .btn-action {
        border: 1px solid #e5e7eb;
        background: white;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #4b5563;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-action:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .btn-action.process {
        color: #ef4444;
        border-color: #fecaca;
    }

    .btn-action.process:hover {
        background: #fef2f2;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    

    <div>
        <h1 class="page-title mb-2">Orders</h1>
        {{-- <p class="page-subtitle">{{ $page_description }}</p> --}}
    </div>

    {{-- Stats Cards --}}
    <div class="stats-grid">
        {{-- New Orders Today --}}
        <div class="stat-card-v2">
            <div class="card-top">
                <span class="card-label">New Orders Today</span>
                <div class="card-icon-box red">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>
            <div class="card-value">{{ number_format($stats['new_orders_today']) }}</div>
            <div class="card-footer">
                @if($stats['new_orders_percentage'] >= 0)
                    <span class="trend-up">
                        <i class="fas fa-arrow-up"></i> +{{ $stats['new_orders_percentage'] }}% from yesterday
                    </span>
                @else
                    <span class="trend-down text-danger">
                        <i class="fas fa-arrow-down"></i> {{ $stats['new_orders_percentage'] }}% from yesterday
                    </span>
                @endif
            </div>
        </div>

        {{-- Orders (Next 3 Days) --}}
        <div class="stat-card-v2">
            <div class="card-top">
                <span class="card-label">Orders (Next 3 Days)</span>
                <div class="card-icon-box blue">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
            <div class="card-value">{{ number_format($stats['orders_next_3_days']) }}</div>
            <div class="card-footer">
                <span class="info-badge">{{ $stats['highest_category_3_days'] }} Highest</span>
            </div>
        </div>

        {{-- Orders (Next 7 Days) --}}
        <div class="stat-card-v2">
            <div class="card-top">
                <span class="card-label">Orders (Next 7 Days)</span>
                <div class="card-icon-box orange">
                    <i class="fas fa-cogs"></i>
                </div>
            </div>
            <div class="card-value">{{ number_format($stats['orders_next_7_days']) }}</div>
        </div>

        {{-- Dispatched Today --}}
        <div class="stat-card-v2">
            <div class="card-top">
                <span class="card-label">Dispatched Today</span>
                <div class="card-icon-box green">
                    <i class="fas fa-truck"></i>
                </div>
            </div>
            <div class="card-value">{{ number_format($stats['dispatched_today']) }}</div>
        </div>
    </div>

    <div class="dashboard-header d-flex justify-content-between align-items-end">
        <div class="header-actions">
            <form id="filterForm" action="{{ route('orders.dashboard') }}" method="GET" class="d-flex align-items-center gap-3">
                <input type="hidden" name="chart_status" value="{{ $chart_status }}">
                <input type="hidden" name="chart_date_range" value="{{ $chart_date_range }}">
                <input type="hidden" name="chart_range_type" value="{{ $chart_range_type }}">

                <input type="hidden" name="range_type" id="range_type" value="{{ $range_type }}">
                <div class="btn-group btn-group-filter">
                    <button type="button" class="btn {{ $range_type == 'today' ? 'active' : '' }}" onclick="updateRangeType('today')">Today</button>
                    <button type="button" class="btn {{ $range_type == 'this_week' ? 'active' : '' }}" onclick="updateRangeType('this_week')">This Week</button>
                    <button type="button" class="btn {{ $range_type == 'next_week' ? 'active' : '' }}" onclick="updateRangeType('next_week')">Next Week</button>
                </div>
                <div class="daterange-picker-btn">
                    <i class="far fa-calendar"></i>
                    <input type="hidden" name="date_range" id="date_range" value="{{ $date_range }}">
                    <span id="date_range_display">{{ $date_range }}</span>
                </div>
                <div class="status-select-container">
                    <select name="status" class="form-select-custom" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $val => $label)
                            <option value="{{ $val }}" {{ $status !== null && $status == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>


    {{-- Recent Bulk Orders --}}
    <div class="recent-orders-card">
        <div class="recent-orders-header">
            <h2>Recent Bulk Orders</h2>
            <a href="{{ route('orders.index') }}" class="view-all-link">
                View All Orders <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="recent-orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Outlet / Customer</th>
                        <th>Items Summary</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                    <tr>
                        <td><span class="order-id">#{{ $order->order_number }}</span></td>
                        <td>
                            <div class="outlet-info">
                                <span class="outlet-name">{{ $order->receiverStore->name ?? 'N/A' }}</span>
                                <span class="outlet-location">{{ $order->receiverStore->city ?? '' }}</span>
                            </div>
                        </td>
                        <td>
                            @php
                                $firstItem = $order->items->first();
                                $otherCount = $order->items->count() - 1;
                            @endphp
                            <div class="items-summary">
                                <span class="main-item">{{ $firstItem->product->name ?? 'N/A' }} ({{ $firstItem->quantity ?? 0 }}x {{ $firstItem->unit->name ?? '' }})</span>
                                @if($otherCount > 0)
                                    <span class="other-items">+ {{ $otherCount }} other items</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="delivery-info">
                                <i class="far fa-calendar"></i>
                                <span>{{ $order->delivery_schedule_from ? Carbon\Carbon::parse($order->delivery_schedule_from)->format('d M, h:i A') : 'N/A' }}</span>
                            </div>
                        </td>
                        <td>
                            @php
                                $statusClass = 'status-new';
                                $statusLabel = 'Pending';
                                if($order->status == 1) { $statusClass = 'status-process'; $statusLabel = 'Approved'; }
                                elseif($order->status == 2) { $statusClass = 'status-dispatched'; $statusLabel = 'Dispatched'; }
                                elseif($order->status == 3) { $statusClass = 'status-dispatched'; $statusLabel = 'Delivered'; }
                                elseif($order->status == 4) { $statusClass = 'status-new'; $statusLabel = 'Cancelled'; }
                            @endphp
                            <span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td>
                            <a href="{{ route('orders.edit', $order->id) }}" class="btn-action process">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="header-actions">
        <form id="chartFilterForm" action="{{ route('orders.dashboard') }}" method="GET" class="d-flex align-items-center gap-3">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="date_range" value="{{ $date_range }}">
            <input type="hidden" name="range_type" value="{{ $range_type }}">

            <input type="hidden" name="chart_range_type" id="chart_range_type" value="{{ $chart_range_type }}">
            <div class="btn-group btn-group-filter">
                <button type="button" class="btn {{ $chart_range_type == 'today' ? 'active' : '' }}" onclick="updateChartRangeType('today')">Today</button>
                <button type="button" class="btn {{ $chart_range_type == 'this_week' ? 'active' : '' }}" onclick="updateChartRangeType('this_week')">This Week</button>
                <button type="button" class="btn {{ $chart_range_type == 'next_week' ? 'active' : '' }}" onclick="updateChartRangeType('next_week')">Next Week</button>
            </div>
            <div class="daterange-picker-btn chart-daterange-picker-btn">
                <i class="far fa-calendar"></i>
                <input type="hidden" name="chart_date_range" id="chart_date_range" value="{{ $chart_date_range }}">
                <span id="chart_date_range_display">{{ $chart_date_range }}</span>
            </div>
            <div class="status-select-container d-none">
                <select name="chart_status" class="form-select-custom" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $val => $label)
                        <option value="{{ $val }}" {{ $chart_status !== null && $chart_status == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    {{-- Weekly Production Plan Chart --}}
    <div class="chart-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">Weekly Production Plan</h1>
                {{-- <p class="page-subtitle">{{ $page_description }}</p> --}}
            </div>
        </div>

        <div class="title-row">
            <div id="dynamic-chart-legend" class="chart-legend">
                {{-- Legend will be populated by JS --}}
            </div>
        </div>
        <div class="chart-canvas-container">
            <canvas id="productionChart"></canvas>
        </div>
    </div>

    {{-- Order Requirement Breakdown Table --}}
    <div class="table-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">Order Requirement Breakdown</h3>
            <a href="{{ route('orders.dashboard.export', array_merge(request()->all(), ['chart_status' => $chart_status, 'chart_date_range' => $chart_date_range, 'chart_range_type' => $chart_range_type])) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-borderless">
                <thead>
                    <tr>
                        <th>Product Category</th>
                        <th>Total Orders</th>
                        <th>Required Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($breakdown as $item)
                    @php
                        $catName = strtolower($item->category_name);
                        $icon = 'fa-tag'; $iconColor = '#6b7280'; $iconBg = '#f3f4f6';
                        if (str_contains($catName, 'basundi')) { $icon = 'fa-bottle-water'; $iconColor = '#ef4444'; $iconBg = '#fef2f2'; }
                        elseif (str_contains($catName, 'mango')) { $icon = 'fa-glass-water'; $iconColor = '#f59e0b'; $iconBg = '#fffbeb'; }
                        elseif (str_contains($catName, 'rajwadi')) { $icon = 'fa-jar'; $iconColor = '#8b5cf6'; $iconBg = '#f5f3ff'; }
                        elseif (str_contains($catName, 'ice cream')) { $icon = 'fa-ice-cream'; $iconColor = '#3b82f6'; $iconBg = '#eff6ff'; }

                        $statusLabel = $statuses[$item->status] ?? 'Unknown';
                        $statusClass = 'status-pending-material';
                        if($item->status == 1) $statusClass = 'status-in-production';
                        if($item->status == 3) $statusClass = 'status-completed';
                    @endphp
                    <tr>
                        <td>
                            <div class="product-info">
                                <div class="product-icon-small" style="background-color: {{ $iconBg }}">
                                    <i class="fas {{ $icon }}" style="color: {{ $iconColor }}"></i>
                                </div>
                                <div>
                                    <div class="product-name-bold">{{ $item->product_name }}</div>
                                    <div class="product-sub">{{ $item->category_name }} ({{ $item->unit_name }})</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $item->total_orders }} Orders</td>
                        <td><span class="qty-bold">{{ number_format($item->total_qty) }}x {{ $item->unit_name }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/js/daterangepicker.min.js') }}"></script>
<script src="{{ asset('assets/js/chart.js') }}"></script>
<script>
$(function() {
    window.updateRangeType = function(type) {
        $('#range_type').val(type);
        $('#filterForm').submit();
    };

    window.updateChartRangeType = function(type) {
        $('#chart_range_type').val(type);
        $('#chartFilterForm').submit();
    };

    $('.daterange-picker-btn:not(.chart-daterange-picker-btn)').daterangepicker({
        locale: { format: 'DD/MM/YYYY' },
        autoUpdateInput: false,
        startDate: moment('{{ explode(" - ", $date_range)[0] }}', 'DD/MM/YYYY'),
        endDate: moment('{{ explode(" - ", $date_range)[1] }}', 'DD/MM/YYYY'),
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end) {
        $('#date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        $('#range_type').val('custom');
        $('#filterForm').submit();
    });

    $('.chart-daterange-picker-btn').daterangepicker({
        locale: { format: 'DD/MM/YYYY' },
        autoUpdateInput: false,
        startDate: moment('{{ explode(" - ", $chart_date_range)[0] }}', 'DD/MM/YYYY'),
        endDate: moment('{{ explode(" - ", $chart_date_range)[1] }}', 'DD/MM/YYYY'),
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end) {
        $('#chart_date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        $('#chart_range_type').val('custom');
        $('#chartFilterForm').submit();
    });

    const ctx = document.getElementById('productionChart').getContext('2d');
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6b7280';

    @php
        $variants = [];
        foreach($chartData as $items) {
            foreach($items as $i) { 
                if(!in_array($i->display_name, $variants)) $variants[] = $i->display_name; 
            }
        }
        $datasets = [];
        $chartColors = ['#ef4444', '#f97316', '#8b5cf6', '#3b82f6', '#ec4899', '#10b981', '#06b6d4'];
        foreach($variants as $idx => $v) {
            $dataAry = [];
            foreach($chartData as $dateKey => $dayItems) {
                $val = 0;
                foreach($dayItems as $item) { if($item->display_name == $v) $val = $item->total_qty; }
                $dataAry[] = $val;
            }
            $datasets[] = [
                'label' => $v,
                'data' => $dataAry,
                'backgroundColor' => $chartColors[$idx % count($chartColors)],
                'borderRadius' => 4,
                'barThickness' => 32
            ];
        }
    @endphp

    const productionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($labels) !!},
            datasets: {!! json_encode($datasets) !!}
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { font: { weight: '500' } } },
                y: { stacked: true, beginAtZero: true, grid: { color: '#f3f4f6', borderDash: [4, 4] } }
            },
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#1f2937', padding: 12, cornerRadius: 8 }
            }
        }
    });

    // Populate Legend
    const legendContainer = document.getElementById('dynamic-chart-legend');
    productionChart.data.datasets.forEach((dataset, i) => {
        const item = document.createElement('div');
        item.className = 'legend-item';
        item.innerHTML = `<div class="legend-dot" style="background: ${dataset.backgroundColor}"></div> ${dataset.label}`;
        legendContainer.appendChild(item);
    });

    $('.search-input-group input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>
@endpush
