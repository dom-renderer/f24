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
            @if(auth()->user()->can('grievance-reporting.edit'))
                <a href="{{ route('grievance-reporting.edit', $grievance->id) }}" class="btn btn-primary btn-sm me-2">
                    <i class="fas fa-pen me-1"></i> Edit
                </a>
            @endif
            <a href="{{ route('grievance-reporting.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>General Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="detail-label">Order Number</div>
                            <div class="detail-value fw-bold">#{{ $grievance->order->order_number ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="detail-label">Reported By</div>
                            <div class="detail-value">{{ $grievance->reportedBy->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                @php
                                    $labels = [
                                        0 => '<span class="badge bg-warning text-dark">Pending</span>',
                                        1 => '<span class="badge bg-success">Resolved</span>',
                                        2 => '<span class="badge bg-danger">Rejected</span>',
                                    ];
                                @endphp
                                {!! $labels[$grievance->status] ?? '<span class="badge bg-secondary">Unknown</span>' !!}
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="detail-label">Created At</div>
                            <div class="detail-value">{{ $grievance->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @if($grievance->remarks)
                        <div class="col-md-12">
                            <div class="detail-label">Remarks</div>
                            <div class="detail-value">{{ $grievance->remarks }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-boxes me-2 text-primary"></i>Reported Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table items-table mb-0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Unit</th>
                                <th class="text-center">Ordered Qty</th>
                                <th class="text-center">Claimed Qty</th>
                                <th>Issue Type</th>
                                <th>Note</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($grievance->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->orderItem->product->name ?? 'N/A' }}</td>
                                <td>{{ $item->orderItem->unit->name ?? 'N/A' }}</td>
                                <td class="text-center">{{ $item->orderItem->quantity ?? '0' }}</td>
                                <td class="text-center fw-bold text-danger">{{ $item->quantity }}</td>
                                <td>
                                    <span class="badge bg-soft-info text-info text-capitalize">
                                        {{ str_replace('_', ' ', $item->issue_type) }}
                                    </span>
                                </td>
                                <td>{{ $item->note ?: '-' }}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
