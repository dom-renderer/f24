@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/daterangepicker.css') }}" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
    .filter-bar {
        background: #fff;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .filter-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #6b7280;
        margin-bottom: 0.5rem;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .daterange-picker-btn {
        background: white;
        border: 1px solid #d1d5db;
        color: #374151;
        font-size: 0.875rem;
        padding: 0.45rem 0.75rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        width: 100%;
        height: 38px;
    }
</style>
@endpush

@section('content')
<div class="bg-light p-4 rounded">
    <h1> {{ $page_title }} </h1>
        <div class="lead">
            {{ $page_description }}
            @if(auth()->user()->can('orders.create'))
                <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm float-end">Add Order</a>
            @endif
        </div>

        <div class="mt-2">
            @include('layouts.partials.messages')
        </div>

        {{-- Filter Bar --}}
        <div class="filter-bar">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="filter-label">Order Date Range</label>
                    <div class="daterange-picker-btn" id="filter-date-range-btn">
                        <i class="far fa-calendar"></i>
                        <span id="date-range-display">Select Date Range</span>
                        <input type="hidden" id="filter-date-range">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Delivery Date Range</label>
                    <div class="daterange-picker-btn" id="filter-delivery-date-range-btn">
                        <i class="far fa-calendar"></i>
                        <span id="delivery-date-range-display">Select Date Range</span>
                        <input type="hidden" id="filter-delivery-date-range">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Dispatched From	</label>
                    <select id="filter-sender" class="form-select select2">
                        <option value="">All Stores</option>
                        @foreach($stores as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Order From</label>
                    <select id="filter-receiver" class="form-select select2">
                        <option value="">All Stores</option>
                        @foreach($stores as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Status</label>
                    <select id="filter-status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-end">
                    <button id="reset-filters" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo me-1"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped w-100" id="orders-table">
                <thead>
                <tr>
                    <th width="1%">#</th>
                    <th>Order #</th>
                    <th>Dispatched From</th>
                    <th>Order From</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Order Date</th>
                    <th>Delivery Date</th>
                    <th width="250px">Action</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/js/daterangepicker.min.js') }}"></script>
<script src="{{ asset('assets/js/other/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/other/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" }
    });

    let CURRENCY_SYMBOL = "{{ Helper::defaultCurrencySymbol() }}";
    
    var table = $('#orders-table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        ajax: {
            url: "{{ route('orders.index') }}",
            data: function (d) {
                d.status = $('#filter-status').val();
                d.date_range = $('#filter-date-range').val();
                d.delivery_date_range = $('#filter-delivery-date-range').val();
                d.sender_store_id = $('#filter-sender').val();
                d.receiver_store_id = $('#filter-receiver').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', searchable: false },
            { data: 'order_number', name: 'order_number' },
            { data: 'sender', name: 'sender', orderable: false },
            { data: 'receiver', name: 'receiver', orderable: false },
            { data: 'net_amount', name: 'net_amount', render: function(data) { return CURRENCY_SYMBOL + parseFloat(data).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}); } },
            { data: 'status_label', name: 'status' },
            { data: 'created_by_name', name: 'created_by_name', orderable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'delivery_schedule_from', name: 'delivery_schedule_from' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Date Range Picker
    $('#filter-date-range-btn').daterangepicker({
        locale: { format: 'DD/MM/YYYY', cancelLabel: 'Clear' },
        autoUpdateInput: false,
    }, function(start, end) {
        let range = start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY');
        $('#filter-date-range').val(range);
        $('#date-range-display').text(range);
        table.ajax.reload();
    });

    // Delivery Date Range Picker
    $('#filter-delivery-date-range-btn').daterangepicker({
        locale: { format: 'DD/MM/YYYY', cancelLabel: 'Clear' },
        autoUpdateInput: false,
    }, function(start, end) {
        let range = start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY');
        $('#filter-delivery-date-range').val(range);
        $('#delivery-date-range-display').text(range);
        table.ajax.reload();
    });

    $('#filter-date-range-btn').on('cancel.daterangepicker', function(ev, picker) {
        $(this).find('input').val('');
        $('#date-range-display').text('Select Date Range');
        table.ajax.reload();
    });

    $('#filter-delivery-date-range-btn').on('cancel.daterangepicker', function(ev, picker) {
        $(this).find('input').val('');
        $('#delivery-date-range-display').text('Select Date Range');
        table.ajax.reload();
    });

    // Trigger Reload on Filter Change
    $('#filter-status, #filter-sender, #filter-receiver').on('change', function() {
        table.ajax.reload();
    });

    // Reset Filters
    $('#reset-filters').on('click', function() {
        $('#filter-status, #filter-sender, #filter-receiver').val('').trigger('change');
        $('#filter-date-range').val('');
        $('#date-range-display').text('Select Date Range');
        $('#filter-delivery-date-range').val('');
        $('#delivery-date-range-display').text('Select Date Range');
        table.ajax.reload();
    });
    
    // Status Change
    $(document).on('change', '.status-select', function() {
        const $select = $(this);
        const orderId = $select.data('id');
        const newStatus = $select.val();
        const oldStatus = $select.attr('data-oldstatus');

        const updateStatus = (data) => {
            $.ajax({
                url: "{{ route('orders.update-status') }}",
                type: 'POST',
                data: { id: orderId, status: newStatus, ...data },
                success: function(response) {
                    if (response.status) {
                        Swal.fire('Success', response.message, 'success');
                        $select.prop('defaultValue', newStatus);
                    } else {
                        Swal.fire('Error', response.message, 'error');
                        $select.val(oldStatus);
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Internal Server Error', 'error');
                    $select.val(oldStatus);
                }
            });
        };

        if (newStatus == 4) { // Cancelled
            Swal.fire({
                title: 'Cancel Order?',
                text: 'Please provide a reason for cancellation:',
                input: 'textarea',
                inputAttributes: { required: 'required' },
                showCancelButton: true,
                confirmButtonText: 'Confirm Cancel',
                preConfirm: (note) => {
                    if (!note) { Swal.showValidationMessage('Cancellation note is required'); }
                    return note;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateStatus({ cancellation_note: result.value });
                } else {
                    $select.val(oldStatus);
                }
            });
        } else if (newStatus == 2) { // Dispatched
            $.get("{{ route('orders.ajax.delivery-persons') }}", function(users) {
                let userOptions = '<option value="">Select Driver</option>';
                users.forEach(u => { userOptions += `<option value="${u.id}">${u.name}</option>`; });
                
                Swal.fire({
                    title: 'Mark as Dispatched?',
                    html: `<select id="swal_delivery_user" class="form-select">${userOptions}</select>`,
                    showCancelButton: true,
                    confirmButtonText: 'Confirm',
                    preConfirm: () => {
                        const userId = $('#swal_delivery_user').val();
                        if (!userId) { Swal.showValidationMessage('Driver is required'); }
                        return userId;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateStatus({ delivery_user: result.value });
                    } else {
                        $select.val(oldStatus);
                    }
                });
            });
        } else {
            Swal.fire({
                title: 'Change Status?',
                text: "Are you sure you want to change the order status?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, change it'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateStatus({});
                } else {
                    $select.val(oldStatus);
                }
            });
        }
    });

    window.deleteOrder = function(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/orders/' + id,
                    type: 'DELETE',
                    success: function(response) {
                        if(response.status) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    }
                });
            }
        });
    };
});
</script>
@endpush
