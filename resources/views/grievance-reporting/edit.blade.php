@extends('layouts.app-master')

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
    .select2-container { width: 100% !important; }
    .items-table th { background: #f1f5f9; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
    .items-table td { vertical-align: middle; }
    .btn-remove-row { color: #dc3545; cursor: pointer; }
    .btn-remove-row:hover { color: #a71d2a; }
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
            <a href="{{ route('grievance-reporting.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @include('layouts.partials.messages')

    <form id="grievanceForm" method="POST" action="{{ route('grievance-reporting.update', $grievance->id) }}">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>General Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Order <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="order_id" id="order_id" required>
                                    <option value="">Select Order</option>
                                    @foreach($orders as $order)
                                        <option value="{{ $order->id }}" {{ $grievance->order_id == $order->id ? 'selected' : '' }}>
                                            {{ $order->order_number }} ({{ date('d-m-Y', strtotime($order->created_at)) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="2" placeholder="Overall notes...">{{ $grievance->remarks }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-boxes me-2 text-primary"></i>Reported Items</h6>
                        <button type="button" class="btn btn-primary btn-sm" id="addItemRow">
                            <i class="fas fa-plus me-1"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table items-table mb-0">
                                <thead>
                                <tr>
                                    <th width="30%">Item</th>
                                    <th width="15%">Ordered Qty</th>
                                    <th width="15%">Claimed Qty</th>
                                    <th width="20%">Issue Type</th>
                                    <th width="15%">Note</th>
                                    <th width="5%"></th>
                                </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    @foreach($grievance->items as $index => $gItem)
                                        <tr class="item-row" data-row-index="{{ $index }}">
                                            <td>
                                                <select class="form-control form-control-sm order-item-select" name="items[{{ $index }}][order_item_id]" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($orderItems as $oItem)
                                                        <option value="{{ $oItem->id }}" data-qty="{{ $oItem->quantity }}" {{ $gItem->order_item_id == $oItem->id ? 'selected' : '' }}>
                                                            {{ $oItem->product->name ?? 'N/A' }} ({{ $oItem->unit->name ?? 'N/A' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm ordered-qty" value="{{ $gItem->orderItem->quantity ?? '' }}" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" name="items[{{ $index }}][quantity]" value="{{ $gItem->quantity }}" placeholder="Qty" required>
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm" name="items[{{ $index }}][issue_type]" required>
                                                    <option value="">Select Type</option>
                                                    <option value="not_received" {{ $gItem->issue_type == 'not_received' ? 'selected' : '' }}>Not Received</option>
                                                    <option value="partially_received" {{ $gItem->issue_type == 'partially_received' ? 'selected' : '' }}>Partially Received</option>
                                                    <option value="defective" {{ $gItem->issue_type == 'defective' ? 'selected' : '' }}>Defective</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="items[{{ $index }}][note]" value="{{ $gItem->note }}" placeholder="Note">
                                            </td>
                                            <td class="text-center"><span class="btn-remove-row" title="Remove"><i class="fas fa-times-circle fa-lg"></i></span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="noItemsMessage" class="text-center text-muted py-4" style="{{ count($grievance->items) > 0 ? 'display: none;' : '' }}">
                            <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i><br>
                            Click "Add Item" to report issues for specific products.
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-1"></i> Update Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="itemRowTemplate">
    <tr class="item-row" data-row-index="__INDEX__">
        <td>
            <select class="form-control form-control-sm order-item-select" name="items[__INDEX__][order_item_id]" required>
                <option value="">Select Item</option>
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm ordered-qty" readonly>
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" name="items[__INDEX__][quantity]" placeholder="Qty" required>
        </td>
        <td>
            <select class="form-control form-control-sm" name="items[__INDEX__][issue_type]" required>
                <option value="">Select Type</option>
                <option value="not_received">Not Received</option>
                <option value="partially_received">Partially Received</option>
                <option value="defective">Defective</option>
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="items[__INDEX__][note]" placeholder="Note">
        </td>
        <td class="text-center"><span class="btn-remove-row" title="Remove"><i class="fas fa-times-circle fa-lg"></i></span></td>
    </tr>
</template>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script>
$(document).ready(function() {
    let rowIndex = {{ count($grievance->items) }};
    let orderItemsData = {!! json_encode($orderItems->map(fn($i) => ['id' => $i->id, 'product_name' => $i->product->name ?? 'N/A', 'unit_name' => $i->unit->name ?? 'N/A', 'quantity' => $i->quantity])) !!};

    $('.select2').select2();
    $('.order-item-select').select2({ placeholder: 'Select Item' });

    // Order Change -> Load Items
    $('#order_id').on('change', function() {
        const orderId = $(this).val();
        $('#itemsTableBody').empty();
        $('#noItemsMessage').show();
        orderItemsData = [];

        if (orderId) {
            $.get("{{ route('grievance-reporting.ajax.order-items', '') }}/" + orderId, function(items) {
                orderItemsData = items;
            });
        }
    });

    // Add Item Row
    $('#addItemRow').on('click', function() {
        const orderId = $('#order_id').val();
        if (!orderId) {
            Swal.fire({ icon: 'warning', title: 'Select Order', text: 'Please select an Order before adding items.' });
            return;
        }

        const template = $('#itemRowTemplate').html().replace(/__INDEX__/g, rowIndex);
        $('#itemsTableBody').append(template);
        $('#noItemsMessage').hide();
        
        const $newRow = $(`tr[data-row-index="${rowIndex}"]`);
        const $itemSelect = $newRow.find('.order-item-select');
        
        let options = '<option value="">Select Item</option>';
        orderItemsData.forEach(item => {
            options += `<option value="${item.id}" data-qty="${item.quantity}">${item.product_name} (${item.unit_name})</option>`;
        });
        $itemSelect.html(options).select2({ placeholder: 'Select Item' });
        
        rowIndex++;
    });

    // Item Selection Change
    $(document).on('change', '.order-item-select', function() {
        const $row = $(this).closest('tr');
        const selectedOption = $(this).find('option:selected');
        const qty = selectedOption.data('qty');
        $row.find('.ordered-qty').val(qty || '');
        
        const currentItem = $(this).val();
        const currentIndex = $row.data('row-index');
        let isDuplicate = false;
        
        $('.order-item-select').each(function() {
            const rowIdx = $(this).closest('tr').data('row-index');
            if (rowIdx !== currentIndex && $(this).val() === currentItem && currentItem !== "") {
                isDuplicate = true;
                return false;
            }
        });

        if (isDuplicate) {
            Swal.fire({ icon: 'error', title: 'Duplicate Item', text: 'This item is already added to the report.' });
            $(this).val('').trigger('change.select2');
            $row.find('.ordered-qty').val('');
        }
    });

    // Remove Item Row
    $(document).on('click', '.btn-remove-row', function() {
        $(this).closest('tr').remove();
        if ($('#itemsTableBody tr').length === 0) $('#noItemsMessage').show();
    });

    // jQuery Validation
    $('#grievanceForm').validate({
        errorElement: 'span',
        errorClass: 'text-danger small',
        errorPlacement: function(error, element) {
            if (element.hasClass('select2-hidden-accessible')) {
                error.insertAfter(element.next('.select2-container'));
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function(form) {
            if ($('#itemsTableBody tr').length === 0) {
                Swal.fire({ icon: 'error', title: 'No Items', text: 'Please add at least one item to report.' });
                return false;
            }
            form.submit();
        }
    });
});
</script>
@endpush
