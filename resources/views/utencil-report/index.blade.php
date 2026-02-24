@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
@endpush

@section('content')
<div class="bg-light p-4 rounded">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1">{{ $page_title }}</h1>
            <p class="text-muted mb-0">{{ $page_description }}</p>
        </div>
        <div>
            @if(auth()->user()->can('utencil-report.export'))
                <button id="exportUtencilReport" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i> Export to Excel
                </button>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="utencilFilterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date Range (Movement)</label>
                    <input type="text" name="date_range" id="date_range" class="form-control" placeholder="dd/mm/yyyy - dd/mm/yyyy">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Utencil</label>
                    <select name="utencil_id" id="utencil_id" class="form-select">
                        <option value="">All Utencils</option>
                        @foreach($utencils as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Store (Sender / Receiver)</label>
                    <select name="store_id" id="store_id" class="form-select">
                        <option value="">All Stores</option>
                        @foreach($stores as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Dealer</label>
                    <select name="dealer_id" id="dealer_id" class="form-select">
                        <option value="">All Dealers</option>
                        @foreach($dealers as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Movement Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">Sent &amp; Received</option>
                        <option value="sent">Sent Only</option>
                        <option value="received">Received Only</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Order Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Models\Order::getStatuses() as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" id="applyFilters" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <button type="button" id="resetFilters" class="btn btn-outline-secondary">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped w-100" id="utencils-table">
            <thead>
            <tr>
                <th width="1%">#</th>
                <th>Order #</th>
                <th>Utencil</th>
                <th>Direction</th>
                <th>Qty</th>
                <th>Pending (Order)</th>
                <th>Sender Store</th>
                <th>Receiver Store</th>
                <th>Dealer</th>
                <th>Order Status</th>
                <th>Movement Date</th>
                <th>Note</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/other/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/other/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Simple date range with two dates separated by " - "
    $('#date_range').on('focus', function() {
        $(this).attr('placeholder', 'dd/mm/yyyy - dd/mm/yyyy');
    });

    var table = $('#utencils-table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        ajax: {
            url: "{{ route('utencil-report.index') }}",
            data: function (d) {
                d.date_range = $('#date_range').val();
                d.utencil_id = $('#utencil_id').val();
                d.store_id = $('#store_id').val();
                d.dealer_id = $('#dealer_id').val();
                d.type = $('#type').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', searchable: false },
            { data: 'order_number', name: 'order_number' },
            { data: 'utencil_name', name: 'utencil_name' },
            { data: 'direction', name: 'direction' },
            { data: 'quantity', name: 'quantity' },
            { data: 'pending_qty', name: 'pending_qty' },
            { data: 'sender_store', name: 'sender_store' },
            { data: 'receiver_store', name: 'receiver_store' },
            { data: 'dealer_name', name: 'dealer_name' },
            { data: 'order.status', name: 'order.status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'note', name: 'note' },
        ]
    });

    $('#applyFilters').on('click', function () {
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function () {
        $('#utencilFilterForm')[0].reset();
        table.ajax.reload();
    });

    $('#exportUtencilReport').on('click', function () {
        let params = $.param({
            date_range: $('#date_range').val(),
            utencil_id: $('#utencil_id').val(),
            store_id: $('#store_id').val(),
            dealer_id: $('#dealer_id').val(),
            type: $('#type').val(),
            status: $('#status').val()
        });
        window.location.href = "{{ route('utencil-report.export') }}?" + params;
    });
});
</script>
@endpush

