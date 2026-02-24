@extends('layouts.app-master')

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --brand-primary: #012440;
        }

        .select2-container {
            width: 100% !important;
        }

        .items-table th {
            background: #f1f5f9;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .items-table td {
            vertical-align: middle;
        }

        .btn-remove-row {
            color: #dc3545;
            cursor: pointer;
        }

        .grand-total {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .as-per-actual-label {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .preview-grandtotal-row {
            background: #f1f5f9;
            font-weight: 600;
        }

        .preview-cgst-row td,
        .preview-sgst-row td {
            font-size: 0.72rem;
            color: #6c757d;
            padding-top: 0 !important;
            padding-bottom: 2px !important;
        }

        .preview-subtotal-row td {
            font-weight: 700;
            font-size: 0.8rem;
            background: #f1f5f9;
        }

        .form-switch .form-check-input {
            width: 30px;
            height: 15px;
        }

        .customer-section {
            display: none;
            background: #fffbeb;
            border: 1px dashed #fbbf24;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Time Slot Compact Design */
        .time-slot-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
        }

        .time-slot-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            max-height: 120px;
            overflow-y: auto;
        }

        .slot-btn {
            border: 1px solid #e2e8f0;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.15s ease;
            background: #fff;
            color: #64748b;
            font-weight: 500;
            white-space: nowrap;
        }

        .slot-btn:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
            color: #334155;
        }

        .slot-btn.active {
            background: var(--brand-primary);
            color: #fff;
            border-color: var(--brand-primary);
            box-shadow: 0 2px 4px rgba(1, 36, 64, 0.2);
        }

        /* Items preview */
        .preview-row {
            border-bottom: 1px solid #f1f5f9;
        }

        .preview-row:last-child {
            border-bottom: none;
        }

        .preview-badge {
            width: 24px;
            height: 32px;
            border-radius: 6px;
            background: #f1f5f9;
        }

        .preview-item-title {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .preview-item-meta {
            font-size: 0.7rem;
        }

        .preview-qty,
        .preview-price,
        .preview-total {
            font-size: 0.8rem;
        }
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
                <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('layouts.partials.messages')

        <form id="orderForm" method="POST" action="{{ route('orders.store') }}">
            @csrf

            <div class="row">
                <div class="col-lg-8">
                    {{-- Order Details Card (mirrors create layout) --}}
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Order Details</h6>
                        </div>
                        <div class="card-body">
                            {{-- Row 1: Order From / Dispatched From --}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Order From <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control select2" id="order_from_store" required>
                                        <option value="">Select Store</option>
                                        @php
                                            $groupedStores = $storesWithType->groupBy(function ($s) {
                                                $tmp = optional($s->storetype)->name ?: 'Other';
                                                $tmp2 = optional($s->modeltype)->name ?: 'company';

                                                if ($tmp == 'store') {
                                                    if ($tmp2 == 'company') {
                                                        return 'company';
                                                    } else {
                                                        return 'franchise';
                                                    }
                                                } elseif ($tmp == 'dealer-location') {
                                                    return 'dealer';
                                                } else {
                                                    return 'company';
                                                }
                                            });
                                            $selectedReceiver = $order->receiver_store_id;
                                        @endphp
                                        @foreach ($groupedStores as $typeName => $storeGroup)
                                            <optgroup label="{{ $typeName }}">
                                                @foreach ($storeGroup as $store)
                                                    <option value="{{ $store->id }}"
                                                        data-order-type="{{ \Illuminate\Support\Str::contains(strtolower($typeName), 'franchise') ? 'franchise' : 'company' }}"
                                                        {{ $selectedReceiver == $store->id ? 'selected' : '' }}>
                                                        {{ $store->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="receiver_store_id" id="receiver_store_id"
                                        value="{{ $order->receiver_store_id }}">
                                    <input type="hidden" name="order_type" id="order_type"
                                        value="{{ $order->order_type }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Dispatched From<span
                                            class="text-danger">*</span></label>
                                    <select class="form-control select2" name="sender_store_id" id="sender_store_id"
                                        required>
                                        <option value="">Select Store</option>
                                        @php
                                            $groupedStores = $storesWithType->groupBy(function ($s) {
                                                return optional($s->storetype)->name ?: 'Other';
                                            });
                                            $selectedSender = $order->sender_store_id;
                                        @endphp
                                        @foreach ($groupedStores as $typeName => $storeGroup)
                                            <optgroup label="{{ $typeName }}">
                                                @foreach ($storeGroup as $store)
                                                    <option value="{{ $store->id }}"
                                                        data-order-type="{{ \Illuminate\Support\Str::contains(strtolower($typeName), 'franchise') ? 'franchise' : 'company' }}"
                                                        {{ $selectedSender == $store->id ? 'selected' : '' }}>
                                                        {{ $store->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Row 2: Ordering for Customer --}}
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="for_customer"
                                            id="for_customer" value="1" {{ $order->for_customer ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="for_customer">
                                            Ordering for Customer
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 3 + 4: Customer Name + Contact + Delivery Remarks (shown only when for_customer) --}}
                            <div id="customerSection" class="customer-section"
                                style="{{ $order->for_customer ? 'display:block' : 'display:none' }}">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Customer Name</label>
                                        <input type="text" class="form-control" name="customer_first_name"
                                            id="customer_first_name" value="{{ $order->customer_first_name }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="customer_phone_number"
                                            id="customer_phone_number" value="{{ $order->customer_phone_number }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Delivery Remarks</label>
                                        <textarea class="form-control" name="customer_remark" id="customer_remark" rows="2">{{ $order->customer_remark }}</textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 5: Delivery Date + Time Slot --}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Delivery Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt text-muted"></i></span>
                                        <input type="text" class="form-control datepicker" name="delivery_date"
                                            id="delivery_date" placeholder="Select Date" autocomplete="off"
                                            value="{{ $order->delivery_schedule_from ? date('Y-m-d', strtotime($order->delivery_schedule_from)) : '' }}">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Time Slot</label>
                                    @php
                                        $currentTimeSlot = $order->delivery_schedule_from
                                            ? date('H:i', strtotime($order->delivery_schedule_from)) .
                                                '-' .
                                                date('H:i', strtotime($order->delivery_schedule_to))
                                            : '';
                                    @endphp
                                    <select class="form-control select2" name="time_slot" id="time_slot_select">
                                        <option value="">Select Time Slot</option>
                                    </select>
                                    <div id="time_slot_error"></div>
                                </div>
                            </div>

                            {{-- Row 6: Delivery Address --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Delivery Address <span
                                            class="text-danger">*</span> </label>
                                    <textarea class="form-control" name="delivery_address" id="delivery_address" rows="2" required>{{ $order->delivery_address }}</textarea>
                                </div>
                            </div>

                            {{-- Row 7: Delivery Address Map Link --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Delivery Address Map Link</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="delivery_link"
                                            id="delivery_link" placeholder="Paste Google Map link"
                                            value="{{ $order->delivery_link }}">
                                        <button class="btn btn-outline-secondary" type="button"
                                            id="toggle_delivery_link_edit">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary fetch-address" type="button"
                                            title="Get Full Address from Link" data-toplace="delivery_address"
                                            data-toget="delivery_link">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 8: Handling Instructions --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Handling Instructions</label>
                                    @php
                                        $selectedHandling = is_array($order->handling_instructions)
                                            ? $order->handling_instructions
                                            : [];
                                    @endphp
                                    <select class="form-control select2" name="handling_instructions[]"
                                        id="handling_instructions" multiple>
                                        @foreach ($handlingInstructions as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ in_array($id, $selectedHandling) ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Row 9: Handling Instructions Remarks --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Handling Instructions Remarks</label>
                                    <textarea class="form-control" name="handling_note" id="handling_note" rows="2">{{ $order->handling_note }}</textarea>
                                </div>
                            </div>

                            {{-- Row 10: Driver & Vehicle --}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Driver Selection</label>
                                    <select class="form-control select2" name="delivery_user" id="delivery_user">
                                        <option value="">Select Driver</option>
                                        @foreach ($drivers as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ $order->delivery_user == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Vehicle Selection</label>
                                    <select class="form-control select2" name="vehicle_id" id="vehicle_id">
                                        <option value="">Select Vehicle</option>
                                        @foreach ($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}"
                                                {{ $order->vehicle_id == $vehicle->id ? 'selected' : '' }}>
                                                {{ $vehicle->name }} - {{ $vehicle->make }} - {{ $vehicle->number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Row 11: Collect Utencils on Delivery + Utencils table + modal trigger --}}
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="utencils_collected"
                                            id="utencils_collected" value="1"
                                            {{ $order->utencils_collected ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="utencils_collected">
                                            Collect Utencils on Delivery
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0 fw-semibold">Utencils Movement</label>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#utencilModal">
                                            <i class="fas fa-exchange-alt me-1"></i>Manage Utencils
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead class="text-muted small">
                                                <tr>
                                                    <th>Utencil</th>
                                                    <th width="80">Sent</th>
                                                    <th width="80">Received</th>
                                                    <th width="80">Balance</th>
                                                </tr>
                                            </thead>
                                            <tbody id="utencilSummaryBody">
                                                <tr class="text-muted small" id="utencilSummaryEmptyRow">
                                                    <td colspan="4" class="text-center">No utencils movement planned
                                                        yet.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 12: Collect Amount on Delivery --}}
                            <div class="row border-top pt-3 mt-2">
                                <div class="col-12 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="collect_on_delivery"
                                            id="collect_on_delivery" value="1"
                                            {{ $order->collect_on_delivery ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="collect_on_delivery">
                                            Collect Amount on Delivery
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 mb-3" id="collect_amount_wrapper"
                                    style="{{ $order->collect_on_delivery ? '' : 'display:none;' }}">
                                    <label class="form-label fw-semibold">Amount Received</label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        name="amount_collected" id="amount_collected" placeholder="0.00">
                                </div>
                            </div>

                            {{-- Hidden payment_received (not used for edit calculations, kept for consistency) --}}
                            <input type="hidden" name="payment_received" id="payment_received_hidden"
                                value="{{ $order->payment_received ? 1 : 0 }}">

                            {{-- Remarks --}}
                            <div class="row mt-3">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Remarks</label>
                                    <textarea class="form-control" name="remarks" rows="2">{{ $order->remarks }}</textarea>
                                </div>
                            </div>


                        </div>
                    </div>

                    {{-- Addresses Section --}}
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Addresses</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="bill_to_same_as_ship_to"
                                    @if ($order->bill_to_same_as_ship_to) checked @endif id="same_as_bill_to_switch">
                                <label class="form-check-label small fw-bold" for="same_as_bill_to_switch">Same as Bill
                                    To</label>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                {{-- Bill To Column --}}
                                <div class="col-md-6 border-end">
                                    <h6 class="mb-3 text-primary">Bill To</h6>
                                    <div class="row">
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Name</label>
                                            <input type="text" class="form-control form-control-sm"
                                                name="billing_name" id="billing_name"
                                                value="{{ $order->billing_name }}">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small fw-bold">Contact Number</label>
                                            <input type="text" class="form-control form-control-sm"
                                                name="billing_contact_number" id="billing_contact_number"
                                                value="{{ $order->billing_contact_number }}">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small fw-bold">GST IN</label>
                                            <input type="text" class="form-control form-control-sm"
                                                name="billing_gst_in" id="billing_gst_in"
                                                value="{{ $order->billing_gst_in }}">
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Full Address</label>
                                            <textarea class="form-control form-control-sm" name="billing_address_1" id="billing_address_1"
                                                style="field-sizing: content;">{{ $order->billing_address_1 }}</textarea>
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Google Map Link</label>
                                            <div class="input-group input-group-sm">
                                                <input type="url" class="form-control" name="billing_google_map_link"
                                                    id="billing_google_map_link"
                                                    value="{{ $order->billing_google_map_link }}"
                                                    placeholder="Paste Google Map link or use picker">
                                                <button class="btn btn-outline-primary fetch-address" type="button"
                                                    title="Get Full Address from Link" data-toplace="billing_address_1"
                                                    data-toget="billing_google_map_link">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                                <button class="btn btn-outline-primary open-map-picker-btn" type="button"
                                                    data-address-context="billing" data-bs-toggle="modal"
                                                    data-bs-target="#googleMapModal"
                                                    title="Find Address in Map & Get Link">
                                                    <i class="fas fa-map-marked-alt"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="billing_latitude" id="billing_latitude"
                                                value="{{ $order->billing_latitude }}">
                                            <input type="hidden" name="billing_longitude" id="billing_longitude"
                                                value="{{ $order->billing_longitude }}">
                                        </div>
                                    </div>
                                </div>
                                {{-- Ship To Column --}}
                                <div class="col-md-6">
                                    <h6 class="mb-3 text-primary">Ship To</h6>
                                    <div class="row">
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Name</label>
                                            <input type="text" class="form-control form-control-sm"
                                                name="shipping_name" id="shipping_name"
                                                value="{{ $order->shipping_name }}">
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Contact Number</label>
                                            <input type="text" class="form-control form-control-sm"
                                                name="shipping_contact_number" id="shipping_contact_number"
                                                value="{{ $order->shipping_contact_number }}">
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Full Address</label>
                                            <textarea class="form-control form-control-sm" name="shipping_address_1" id="shipping_address_1" style="field-sizing: content;">{{ $order->shipping_address_1 }}</textarea>
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Google Map Link</label>
                                            <div class="input-group input-group-sm">
                                                <input type="url" class="form-control"
                                                    name="shipping_google_map_link" id="shipping_google_map_link"
                                                    value="{{ $order->shipping_google_map_link }}"
                                                    placeholder="Paste Google Map link or use picker">
                                                <button class="btn btn-outline-primary fetch-address" type="button"
                                                    title="Get Full Address from Link" data-toplace="shipping_address_1"
                                                    data-toget="shipping_google_map_link">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                                <button class="btn btn-outline-primary open-map-picker-btn" type="button"
                                                    data-address-context="shipping" data-bs-toggle="modal"
                                                    data-bs-target="#googleMapModal"
                                                    title="Find Address in Map & Get Link">
                                                    <i class="fas fa-map-marked-alt"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="shipping_latitude" id="shipping_latitude"
                                                value="{{ $order->shipping_latitude }}">
                                            <input type="hidden" name="shipping_longitude" id="shipping_longitude"
                                                value="{{ $order->shipping_longitude }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-boxes me-2 text-primary"></i>Products</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="addItemRow">
                                <i class="fas fa-plus me-1"></i> Add Item
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table items-table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="20%">Category</th>
                                            <th width="22%">Product</th>
                                            <th width="15%">Unit</th>
                                            <th width="12%">Price</th>
                                            <th width="10%">Qty</th>
                                            <th width="12%">Total</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTableBody"></tbody>
                                </table>
                            </div>
                            <div id="noItemsMessage" class="text-center text-muted py-4" style="display: none;">
                                <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i><br>
                                Click "Add Item" to add products.
                            </div>
                        </div>
                    </div>

                    {{-- Other Items Card --}}
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-cube me-2 text-primary"></i>Other Items</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="addOtherItemRow">
                                <i class="fas fa-plus me-1"></i> Add Item
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table items-table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="25%">Item</th>
                                            <th width="10%">Tax Incl.</th>
                                            <th width="15%">Tax Slab</th>
                                            <th width="12%">Price</th>
                                            <th width="8%">Qty</th>
                                            <th width="15%">Total</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="otherItemsTableBody">
                                        @foreach ($order->otherItems as $index => $oi)
                                            @php
                                                $srcOI = $otherItems->firstWhere('id', $oi->other_item_id);
                                                $oiPricingType = $oi->pricing_type ?? ($srcOI->pricing_type ?? 'fixed');
                                                $oiPriceIncTax =
                                                    (int) ($oi->price_includes_tax ??
                                                        ($srcOI->price_includes_tax ?? 0));
                                                $oiCgst = $srcOI && $srcOI->taxSlab ? (float) $srcOI->taxSlab->cgst : 0;
                                                $oiSgst = $srcOI && $srcOI->taxSlab ? (float) $srcOI->taxSlab->sgst : 0;
                                            @endphp
                                            <tr data-row-index="{{ $index }}"
                                                data-pricing-type="{{ $oiPricingType }}"
                                                data-price-includes-tax="{{ $oiPriceIncTax }}"
                                                data-cgst-percent="{{ $oiCgst }}"
                                                data-sgst-percent="{{ $oiSgst }}">
                                                <td>
                                                    <select class="form-control select2 other-item-select"
                                                        name="other_items[{{ $index }}][other_item_id]" required>
                                                        <option value="">Select Item</option>
                                                        @foreach ($otherItems as $o)
                                                            <option value="{{ $o->id }}"
                                                                {{ $oi->other_item_id == $o->id ? 'selected' : '' }}>
                                                                {{ $o->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" class="pricing-type-input"
                                                        name="other_items[{{ $index }}][pricing_type]"
                                                        value="{{ $oiPricingType }}">
                                                </td>
                                                <td class="text-center align-middle">
                                                    <input type="checkbox"
                                                        class="form-check-input price-includes-tax-checkbox"
                                                        name="other_items[{{ $index }}][price_includes_tax]"
                                                        value="1" {{ $oiPriceIncTax ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <select class="form-control form-select-sm tax-slab-select"
                                                        name="other_items[{{ $index }}][tax_slab_id]">
                                                        <option value="">None</option>
                                                        @foreach ($taxSlabs as $slab)
                                                            <option value="{{ $slab->id }}"
                                                                {{ ($oi->tax_slab_id ?? '') == $slab->id ? 'selected' : '' }}
                                                                data-cgst="{{ $slab->cgst }}"
                                                                data-sgst="{{ $slab->sgst }}">{{ $slab->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm price-input"
                                                        name="other_items[{{ $index }}][unit_price]"
                                                        value="{{ $oi->unit_price }}" min="0" step="0.01"
                                                        required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm qty-input"
                                                        name="other_items[{{ $index }}][quantity]"
                                                        value="{{ $oi->quantity }}" min="0.01" step="0.01"
                                                        required>
                                                </td>
                                                <td class="row-total fw-bold text-end pe-3">
                                                    {{ Helper::defaultCurrencySymbol() }}{{ number_format(floatval($oi->unit_price) * floatval($oi->quantity), 2) }}
                                                </td>
                                                <td class="text-center">
                                                    <i class="fas fa-times-circle btn-remove-row" title="Remove"></i>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div id="noOtherItemsMessage" class="text-center text-muted py-4"
                                style="{{ $order->otherItems->count() > 0 ? 'display: none;' : '' }}">
                                <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i><br>
                                Click "Add Item" to add other items.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">

                    {{-- Order Summary Card --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0"><i class="fas fa-receipt me-2 text-primary"></i>Order Summary</h6>
                        </div>
                        <div class="card-body p-0">
                            <div id="orderPreviewEmpty" class="text-center text-muted small py-3">
                                No items added yet. Edit items to see a quick preview here.
                            </div>
                            <div id="orderPreviewContainer" class="d-none" style="overflow-y: auto;">
                                <table class="table table-borderless table-sm mb-0" id="orderPreviewTable">
                                    <tbody id="orderPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="summary-row d-none">
                                <span class="text-muted">Items:</span>
                                <span id="totalItemsCount">0</span>
                            </div>
                            <div class="summary-row d-none">
                                <span class="text-muted">Subtotal:</span>
                                <span>{{ Helper::defaultCurrencySymbol() }}<span id="subtotalDisplay">0.00</span></span>
                            </div>

                            <div class="summary-row flex-column align-items-start">
                                <div class="d-flex w-100 justify-content-between mb-1">
                                    <label class="text-muted small mb-0">Discount</label>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0"
                                        id="toggle_discount_edit">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                </div>
                                <div class="d-flex w-100 gap-2">
                                    <select class="form-select form-select-sm discount-input" name="discount_type"
                                        id="discount_type"
                                        style="width: 80px; pointer-events: none; background-color: #e9ecef;">
                                        <option value="0" {{ $order->discunt_type == 0 ? 'selected' : '' }}>%
                                        </option>
                                        <option value="1" {{ $order->discunt_type == 1 ? 'selected' : '' }}>Fix
                                        </option>
                                    </select>
                                    <input type="number" step="0.01" min="0"
                                        class="form-control form-control-sm discount-input" name="discount_amount"
                                        id="discount_amount" value="{{ $order->discount_amount }}" readonly
                                        style="background-color: #e9ecef;">
                                </div>
                                <small class="text-success"
                                    id="discountValueDisplay">-{{ Helper::defaultCurrencySymbol() }}0.00</small>
                            </div>

                            {{-- Additional Charges --}}
                            <div class="summary-row flex-column align-items-start">
                                <div class="d-flex w-100 justify-content-between mb-1">
                                    <label class="text-muted small mb-1 mb-0">Additional Charges</label>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addChargeRow">
                                        <i class="fas fa-plus me-1"></i>Add Charge
                                    </button>
                                </div>
                                <div id="additionalChargesContainer" class="w-100">
                                    @foreach ($order->charges as $idx => $charge)
                                        <div class="d-flex align-items-center mb-1 charge-row"
                                            data-index="{{ $idx }}">
                                            <input type="text" name="additional_charges[{{ $idx }}][title]"
                                                class="form-control form-control-sm me-2 charge-title-input"
                                                placeholder="Title" value="{{ $charge->title }}" required />
                                            <input type="number" step="0.01" min="0"
                                                name="additional_charges[{{ $idx }}][amount]"
                                                class="form-control form-control-sm me-2 charge-amount-input"
                                                placeholder="0.00" value="{{ $charge->amount }}" required />
                                            <button type="button" class="btn btn-link text-danger p-0 btn-remove-charge"
                                                title="Remove">
                                                <i class="fas fa-times-circle fa-lg"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="d-flex justify-content-between w-100 mt-1">
                                    <span class="text-muted small">Total Additional Charges:</span>
                                    <span class="text-primary small">{{ Helper::defaultCurrencySymbol() }}<span
                                            id="additionalChargesTotalDisplay">0.00</span></span>
                                </div>
                            </div>

                            {{-- CGST & SGST display removed — tax is now per-product and shown in preview --}}

                            {{-- <div class="summary-row flex-column align-items-start">
                                <label class="text-muted small mb-1">Tax Type</label>
                                <div class="d-flex w-100 gap-2">
                                    <select class="form-select form-select-sm" name="tax_type" id="tax_type"
                                        style="width: 80px;">
                                        <option value="0" {{ $order->tax_type == 0 ? 'selected' : '' }}>%</option>
                                        <option value="1" {{ $order->tax_type == 1 ? 'selected' : '' }}>Fix</option>
                                    </select>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                        name="tax_amount" id="tax_amount" value="{{ $order->tax_amount }}" {{
                                        $order->tax_type == 0 ? 'readonly' : '' }}>
                                </div>
                                <small class="text-danger" id="taxValueDisplay"
                                    style="{{ $order->tax_type == 0 ? 'display:none' : '' }}">+{{
                                    Helper::defaultCurrencySymbol() }}0.00</small>
                            </div> --}}

                            <input type="hidden" id="tax_amount" value="{{ $order->tax_amount }}">

                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold">Grand Total:</span>
                                <span class="grand-total text-primary">{{ Helper::defaultCurrencySymbol() }}<span
                                        id="grandTotalDisplay">0.00</span></span>
                            </div>
                        </div>
                    </div>

                    {{-- Utencils Card --}}
                    {{-- <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-utensils me-2 text-primary"></i>Utencils</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addUtencilRow">
                                <i class="fas fa-plus me-1"></i>Add Utencil
                            </button>
                        </div>
                        <div class="card-body p-0">

                            <div class="p-3">
                                <h6 class="text-muted text-uppercase small mb-2">Add More Utencils</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" id="utencilsTable">
                                        <thead class="text-muted small">
                                            <tr>
                                                <th>Utencil</th>
                                                <th width="90">Qty</strong></th>
                                                <th>Note</th>
                                                <th width="40"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="utencilsTableBody"></tbody>
                                    </table>
                                </div>
                                <div id="utencilsEmpty" class="text-center text-muted small py-3">
                                    No additional utencils added.
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    {{-- Bill To --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-footer bg-white border-top">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-check me-1"></i> Place Reorder
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <template id="itemRowTemplate">
        <tr class="item-row" data-row-index="__INDEX__" data-cgst-percent="0" data-sgst-percent="0" data-tax-slab-name="None">
            <td>
                <select class="form-control form-control-sm category-select" name="items[__INDEX__][category_id]"
                    required>
                    <option value="">Category</option>
                    @foreach ($categories as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </td>
            <td><select class="form-control form-control-sm product-select" name="items[__INDEX__][product_id]" required
                    disabled>
                    <option value="">Select Product</option>
                </select>
                <input type="hidden" class="tax-slab-id-input" name="items[__INDEX__][tax_slab_id]" value="">
            </td>
            <td><select class="form-control form-control-sm unit-select" name="items[__INDEX__][unit_id]" required
                    disabled>
                    <option value="">Unit</option>
                </select></td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm price-input"
                    name="items[__INDEX__][unit_price]" required>
                <input type="hidden" class="price-ge" name="items[__INDEX__][ge_price]" value="0">
                <input type="hidden" class="price-gi" name="items[__INDEX__][gi_price]" value="0">
            </td>
            <td><input type="number" step="0.01" min="0.01" class="form-control form-control-sm qty-input"
                    name="items[__INDEX__][quantity]" required></td>
            <td><strong class="row-total">{{ Helper::defaultCurrencySymbol() }}0.00</strong></td>
            <td class="text-center"><span class="btn-remove-row"><i class="fas fa-times-circle fa-lg"></i></span></td>
        </tr>
    </template>
    <template id="utencilRowTemplate">
        <tr class="utencil-row" data-index="__INDEX__">
            <td>
                <select class="form-control form-control-sm utencil-select" name="utencils[__INDEX__][utencil_id]"
                    required>
                    <option value="">Select Utencil</option>
                    @foreach ($utencils as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm utencil-qty"
                    name="utencils[__INDEX__][quantity]" required>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="utencils[__INDEX__][note]"
                    placeholder="Note (optional)">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-link text-danger p-0 btn-remove-utencil" title="Remove">
                    <i class="fas fa-times-circle fa-lg"></i>
                </button>
            </td>
        </tr>
    </template>
    <template id="chargeRowTemplate">
        <div class="d-flex align-items-center mb-1 charge-row" data-index="__INDEX__">
            <input type="text" name="additional_charges[__INDEX__][title]"
                class="form-control form-control-sm me-2 charge-title-input"
                placeholder="Title (e.g. Transportation Charges)" required />
            <input type="number" step="0.01" min="0" name="additional_charges[__INDEX__][amount]"
                class="form-control form-control-sm me-2 charge-amount-input" placeholder="0.00" required />
            <button type="button" class="btn btn-link text-danger p-0 btn-remove-charge" title="Remove">
                <i class="fas fa-times-circle fa-lg"></i>
            </button>
        </div>
    </template>

    {{-- Google Map Modal --}}
    <div class="modal fade" id="googleMapModal" tabindex="-1" aria-labelledby="googleMapModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="googleMapModalLabel"><i class="fas fa-map-marker-alt me-2"></i>Select
                        Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="map_search_input" class="form-label small fw-bold">Search Location</label>
                        <input type="text" id="map_search_input" class="form-control"
                            placeholder="Search by place, address, etc.">
                    </div>
                    <div id="mapContainer" style="width: 100%; height: 500px;"></div>
                </div>
                <div class="modal-footer">
                    <div class="me-auto small">
                        <span class="text-muted">Lat:</span> <span id="selected_lat_display">-</span>,
                        <span class="text-muted">Lng:</span> <span id="selected_lng_display">-</span>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="applyMapSelectionBtn">
                        <i class="fas fa-check me-1"></i>Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Utencil Movement Modal (same structure as create view) --}}
    <div class="modal fade" id="utencilModal" tabindex="-1" aria-labelledby="utencilModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="utencilModalLabel">Manage Utencil Movement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Utencil</label>
                        <select class="form-control" id="modal_utencil_id">
                            <option value="">Select Utencil</option>
                            @foreach ($utencils as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger small d-none" id="modal_utencil_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity</label>
                        <input type="number" step="0.01" min="0.01" class="form-control"
                            id="modal_utencil_qty">
                        <span class="text-danger small d-none" id="modal_qty_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type</label>
                        <select class="form-control" id="modal_utencil_type">
                            <option value="">Select Type</option>
                            <option value="add" selected>Add (વાસણ મોકલ્યા)</option>
                            <option value="deduct">Deduct (વાસણ પરત)</option>
                        </select>
                        <span class="text-danger small d-none" id="modal_type_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Note</label>
                        <textarea class="form-control" id="modal_utencil_note" rows="2" placeholder="Optional note"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveUtencilMovementBtn">
                        <i class="fas fa-check me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Modal --}}

@endsection

@push('js')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_KEY') }}&libraries=places" async defer>
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            let rowIndex = 0;
            const existingItems = @json($order->items->load('product.category'));
            let CURRENCY_SYMBOL = "{{ Helper::defaultCurrencySymbol() }}";
            let chargeIndex = {{ $order->charges->count() }};
            let utencilIndex = 0;
            let utencilMovements = [];
            let activeAddressContext = null; // 'billing' or 'shipping'
            let mapInstance = null;
            let mapMarker = null;
            let mapSearchBox = null;
            let selectedLatLng = null;
            let currentDeposit = 0;

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            });
            $('.select2').select2();

            $('.datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                changeMonth: true,
                changeYear: true
            });

            // Generate Time Slots for Select2
            function generateTimeSlots() {
                const startTime = 0; // 6 AM
                const endTime = 23; // 10 PM
                let optionsHtml = '<option value="">Select Time Slot</option>';
                const selectedSlot = "{{ $currentTimeSlot }}";

                for (let hour = startTime; hour < endTime; hour++) {
                    for (let min = 0; min < 60; min += 30) {
                        let startH = hour.toString().padStart(2, '0');
                        let startM = min.toString().padStart(2, '0');

                        let nextHour = hour;
                        let nextMin = min + 30;
                        if (nextMin >= 60) {
                            nextHour++;
                            nextMin = 0;
                        }

                        let endH = nextHour.toString().padStart(2, '0');
                        let endM = nextMin.toString().padStart(2, '0');

                        let slot = `${startH}:${startM}-${endH}:${endM}`;
                        let label = formatTimeLabel(startH, startM) + ' - ' + formatTimeLabel(endH, endM);
                        let selected = (selectedSlot === slot) ? 'selected' : '';
                        optionsHtml += `<option value="${slot}" ${selected}>${label}</option>`;
                    }
                }
                $('#time_slot_select').html(optionsHtml);
            }

            function formatTimeLabel(hour, min) {
                let h = parseInt(hour);
                let suffix = h >= 12 ? 'PM' : 'AM';
                let h12 = h % 12 || 12;
                return `${h12}:${min} ${suffix}`;
            }

            generateTimeSlots();
            $('#time_slot_select').select2({
                placeholder: 'Select Time Slot',
                allowClear: true
            });

            // Sync Order From selection into hidden receiver_store_id + order_type
            $('#order_from_store').on('change', function() {
                const $opt = $(this).find('option:selected');
                const storeId = $opt.val();
                const orderType = $opt.data('order-type') || $('#order_type').val() || 'company';
                $('#receiver_store_id').val(storeId);
                $('#order_type').val(orderType);
            }).trigger('change');

            // For Customer toggle (show/hide customer section + required fields)
            $('#for_customer').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#customerSection').slideDown();
                    $('#customer_first_name').prop('required', true);
                    $('#customer_phone_number').prop('required', true);
                } else {
                    $('#customerSection').slideUp();
                    $('#customer_first_name').prop('required', false);
                    $('#customer_phone_number').prop('required', false);
                }
            }).trigger('change');

            // Toggle delivery link editability
            $('#toggle_delivery_link_edit').on('click', function() {
                const $input = $('#delivery_link');
                const isReadonly = $input.prop('readonly');
                $input.prop('readonly', !isReadonly).css('background', !isReadonly ? '#dddddd' : '#fff');
                $(this).find('i').toggleClass('fa-lock-open fa-lock');
            });

            // Toggle discount editability
            $('#toggle_discount_edit').on('click', function() {
                const $amount = $('#discount_amount');
                const $type = $('#discount_type');
                const isReadonly = $amount.prop('readonly');

                if (isReadonly) {
                    $amount.prop('readonly', false).css('background-color', '#fff');
                    $type.css({
                        'pointer-events': 'auto',
                        'background-color': '#fff'
                    });
                    $(this).find('i').removeClass('fa-lock').addClass('fa-lock-open');
                } else {
                    $amount.prop('readonly', true).css('background-color', '#e9ecef');
                    $type.css({
                        'pointer-events': 'none',
                        'background-color': '#e9ecef'
                    });
                    $(this).find('i').removeClass('fa-lock-open').addClass('fa-lock');
                }
            });

            // Collect Amount on Delivery toggle (edit screen: amount is read-only, driven by payment history)
            $('#collect_on_delivery').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#collect_amount_wrapper').slideDown();
                    $('#payment_received_hidden').val(1);
                } else {
                    $('#collect_amount_wrapper').slideUp();
                    $('#payment_received_hidden').val(0);
                }
            }).trigger('change');

            // Utencil movement modal save (add only; receives handled via existing Utencils card)
            $('#modal_utencil_id').select2({
                dropdownParent: $('#utencilModal')
            });

            $('#saveUtencilMovementBtn').on('click', function() {
                const utencilId = $('#modal_utencil_id').val();
                const utencilName = $('#modal_utencil_id option:selected').text();
                const qty = parseFloat($('#modal_utencil_qty').val());
                const type = $('#modal_utencil_type').val();
                const note = $('#modal_utencil_note').val();

                let hasError = false;
                $('#modal_utencil_error, #modal_qty_error, #modal_type_error').addClass('d-none').text('');

                if (!utencilId) {
                    $('#modal_utencil_error').removeClass('d-none').text('Utencil is required.');
                    hasError = true;
                }
                if (!qty || qty <= 0) {
                    $('#modal_qty_error').removeClass('d-none').text('Quantity must be greater than 0.');
                    hasError = true;
                }
                if (!type) {
                    $('#modal_type_error').removeClass('d-none').text('Type is required.');
                    hasError = true;
                } else if (type === 'deduct') {
                    // Deduct is handled through the existing "Receive Now" fields + update() logic
                    Swal.fire('Not Allowed',
                        'Deduct (receive) operations are managed from the Utencils card below.',
                        'warning');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }

                // Merge into utencilMovements summary (append to existing)
                let existing = utencilMovements.find(m => m.id == utencilId);
                if (!existing) {
                    existing = {
                        id: utencilId,
                        name: utencilName,
                        sent: 0,
                        received: 0
                    };
                    utencilMovements.push(existing);
                }
                if (type === 'add') {
                    existing.sent = (existing.sent || 0) + qty;
                }

                // Add hidden fields for backend (new sends on edit)
                const idx = utencilIndex++;
                $('<input>', {
                    type: 'hidden',
                    name: `utencils[${idx}][utencil_id]`,
                    value: utencilId
                }).appendTo('#orderForm');
                $('<input>', {
                    type: 'hidden',
                    name: `utencils[${idx}][quantity]`,
                    value: qty
                }).appendTo('#orderForm');
                $('<input>', {
                    type: 'hidden',
                    name: `utencils[${idx}][note]`,
                    value: note
                }).appendTo('#orderForm');

                renderUtencilSummary();
                $('#utencilModal').modal('hide');
                $('#modal_utencil_id').val(null).trigger('change');
                $('#modal_utencil_qty').val('');
                $('#modal_utencil_type').val('add');
                $('#modal_utencil_note').val('');
            });

            function renderUtencilSummary() {
                const $body = $('#utencilSummaryBody');
                const $empty = $('#utencilSummaryEmptyRow');
                if (!$body.length) return;

                $body.find('tr').not($empty).remove();

                if (!utencilMovements.length) {
                    $empty.removeClass('d-none').show();
                    return;
                }

                $empty.addClass('d-none').hide();
                utencilMovements.forEach(m => {
                    const pending = Math.max(0, (m.sent || 0) - (m.received || 0));
                    const row = `
                        <tr>
                            <td>${m.name}</td>
                            <td>${(m.sent || 0).toFixed(2)}</td>
                            <td>${(m.received || 0).toFixed(2)}</td>
                            <td>${pending.toFixed(2)}</td>
                        </tr>
                    `;
                    $body.append(row);
                });
            }

            // Initial render of utencils summary from existing data
            renderUtencilSummary();

            existingItems.forEach(function(item) {
                addItemRowWithData(item);
            });

            function addItemRowWithData(item) {
                const template = $('#itemRowTemplate').html().replace(/__INDEX__/g, rowIndex);
                $('#itemsTableBody').append(template);
                const $row = $(`#itemsTableBody tr[data-row-index="${rowIndex}"]`);

                const categoryId = item.product?.category_id || '';
                $row.find('.category-select').val(categoryId);

                if (categoryId) {
                    $.get('/orders/ajax/products-by-category/' + categoryId, function(products) {
                        let options = '<option value="">Select Product</option>';
                        products.forEach(p => {
                            options +=
                                `<option value="${p.id}" data-tax-slab-id="${p.tax_slab_id || ''}" data-tax-slab-name="${p.tax_slab_name || 'None'}" data-cgst="${p.cgst || 0}" data-sgst="${p.sgst || 0}" ${p.id == item.product_id ? 'selected' : ''}>${p.name} (${p.sku})</option>`;
                        });
                        $row.find('.product-select').html(options).prop('disabled', false);

                        // Set tax slab data from selected product
                        const $selectedOpt = $row.find('.product-select option:selected');
                        $row.attr('data-cgst-percent', $selectedOpt.data('cgst') || 0);
                        $row.attr('data-sgst-percent', $selectedOpt.data('sgst') || 0);
                        $row.attr('data-tax-slab-name', $selectedOpt.data('tax-slab-name') || 'None');
                        $row.find('.tax-slab-id-input').val($selectedOpt.data('tax-slab-id') || '');

                        $.get('/orders/ajax/units-by-product/' + item.product_id, function(units) {
                            let unitOptions = '<option value="">Select Unit</option>';
                            units.forEach(u => {
                                unitOptions +=
                                    `<option value="${u.id}" ${u.id == item.unit_id ? 'selected' : ''}>${u.name}</option>`;
                            });
                            $row.find('.unit-select').html(unitOptions).prop('disabled', false);
                            $row.find('.price-input').val(parseFloat(item.unit_price).toFixed(2));
                            $row.find('.price-ge').val(parseFloat(item.ge_price).toFixed(2));
                            $row.find('.price-gi').val(parseFloat(item.gi_price).toFixed(2));
                            $row.find('.price-input').data('ge', parseFloat(item.ge_price).toFixed(
                                2));
                            $row.find('.price-input').data('gi', parseFloat(item.gi_price).toFixed(
                                2));
                            $row.find('.qty-input').val(item.quantity);
                            calculateRowTotal($row);
                        });
                    });
                }

                $row.find('.category-select').select2({
                    placeholder: 'Category',
                    width: '100%'
                });
                $row.find('.product-select').select2({
                    placeholder: 'Product',
                    width: '100%'
                });
                $row.find('.unit-select').select2({
                    placeholder: 'Unit',
                    width: '100%'
                });
                rowIndex++;
            }

            // Order Type Change
            $('#order_type').on('change', function() {
                const type = $(this).val();
                if (type === 'dealer') {
                    $('#receiver_store_wrapper').hide().find('select').prop('required', false);
                    $('#dealer_wrapper').show().find('select').prop('required', true);
                } else {
                    $('#receiver_store_wrapper').show().find('select').prop('required', true);
                    $('#dealer_wrapper').hide().find('select').prop('required', false);
                }
            }).trigger('change');

            // For Customer Toggle
            $('#for_customer').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#customerSection').slideDown();
                    $('input[name="customer_first_name"]').prop('required', true);
                } else {
                    $('#customerSection').slideUp();
                    $('input[name="customer_first_name"]').prop('required', false);
                }
            });

            $('.fetch-address').on('click', function() {
                let toGet = $(this).data('toget');
                let toPlace = $(this).data('toplace');

                if (toGet && toPlace) {
                    fetchAddress(toGet, toPlace);
                }
            });

            // Add Item Row
            $('#addItemRow').on('click', function() {
                const orderFromStore = $('#order_from_store').val();
                if (!orderFromStore) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Select "Order From"',
                        text: 'Please select "Order From" before adding items.'
                    });
                    return;
                }

                const template = $('#itemRowTemplate').html().replace(/__INDEX__/g, rowIndex);
                $('#itemsTableBody').append(template);
                $('#noItemsMessage').hide();
                const $newRow = $(`tr[data-row-index="${rowIndex}"]`);
                $newRow.find('.category-select').select2({
                    placeholder: 'Category',
                    width: '100%'
                });
                $newRow.find('.product-select').select2({
                    placeholder: 'Product',
                    width: '100%'
                });
                $newRow.find('.unit-select').select2({
                    placeholder: 'Unit',
                    width: '100%'
                });
                rowIndex++;
                updateSummary();
            });

            // Remove with confirmation
            $(document).on('click', '.btn-remove-row', function() {
                const $row = $(this).closest('tr');
                Swal.fire({
                    title: 'Remove Item?',
                    text: "Are you sure?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, remove!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $row.remove();
                        if ($('#itemsTableBody tr').length === 0) $('#noItemsMessage').show();
                        updateSummary();
                    }
                });
            });

            $(document).on('change', '.category-select', function() {
                const $row = $(this).closest('tr');
                const categoryId = $(this).val();
                const $productSelect = $row.find('.product-select');
                const $unitSelect = $row.find('.unit-select');
                $productSelect.html('<option value="">Loading...</option>').prop('disabled', true);
                $unitSelect.html('<option value="">Unit</option>').prop('disabled', true);
                $row.find('.price-input').val('');
                $row.find('.price-ge').val('');
                $row.find('.price-gi').val('');
                $row.find('.row-total').text(CURRENCY_SYMBOL + '0.00');
                if (categoryId) {
                    $.get('/orders/ajax/products-by-category/' + categoryId, function(products) {
                        let options = '<option value="">Select Product</option>';
                        products.forEach(p => {
                            options +=
                                `<option value="${p.id}" data-tax-slab-id="${p.tax_slab_id || ''}" data-tax-slab-name="${p.tax_slab_name || 'None'}" data-cgst="${p.cgst || 0}" data-sgst="${p.sgst || 0}">${p.name} (${p.sku})</option>`;
                        });
                        $productSelect.html(options).prop('disabled', false).trigger(
                            'change.select2');
                    });
                }
            });

            $(document).on('change', '.product-select', function() {
                const $row = $(this).closest('tr');
                const productId = $(this).val();
                const $unitSelect = $row.find('.unit-select');
                const $selectedOpt = $(this).find('option:selected');

                // Set per-product tax slab data on the row
                $row.attr('data-cgst-percent', $selectedOpt.data('cgst') || 0);
                $row.attr('data-sgst-percent', $selectedOpt.data('sgst') || 0);
                $row.attr('data-tax-slab-name', $selectedOpt.data('tax-slab-name') || 'None');
                $row.find('.tax-slab-id-input').val($selectedOpt.data('tax-slab-id') || '');

                $unitSelect.html('<option value="">Loading...</option>').prop('disabled', true);
                $row.find('.price-input').val('');
                $row.find('.price-ge').val('');
                $row.find('.price-gi').val('');
                $row.find('.row-total').text(CURRENCY_SYMBOL + '0.00');
                if (productId) {
                    $.get('/orders/ajax/units-by-product/' + productId, function(units) {
                        let options = '<option value="">Select Unit</option>';
                        units.forEach(u => {
                            options += `<option value="${u.id}">${u.name}</option>`;
                        });
                        $unitSelect.html(options).prop('disabled', false).trigger('change.select2');
                    });
                }
            });

            $(document).on('change', '.unit-select', function() {
                const $row = $(this).closest('tr');
                const unitId = $(this).val();
                const productId = $row.find('.product-select').val();
                const storeId = $('#order_from_store').val();
                const currentRowIndex = $row.data('row-index');
                const qty = parseFloat($row.find('.qty-input').val()) || 1;

                if (!unitId || !productId) return;

                // Check for duplicates
                let hasDuplicate = false;
                $('#itemsTableBody tr').each(function() {
                    const rowIdx = $(this).data('row-index');
                    if (rowIdx !== currentRowIndex) {
                        const rowProduct = $(this).find('.product-select').val();
                        const rowUnit = $(this).find('.unit-select').val();
                        if (rowProduct === productId && rowUnit === unitId) {
                            hasDuplicate = true;
                            return false;
                        }
                    }
                });

                if (hasDuplicate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Duplicate Item',
                        text: 'This product and unit combination already exists.'
                    });
                    $row.find('.unit-select').val('').trigger('change.select2');
                    return;
                }

                $.post('/orders/ajax/get-price', {
                    product_id: productId,
                    unit_id: unitId,
                    store_id: storeId,
                    quantity: qty
                }, function(response) {
                    $row.find('.price-input').val(parseFloat(response.price).toFixed(2));
                    $row.find('.price-input').data('ge', parseFloat(response.ge_price).toFixed(2));
                    $row.find('.price-input').data('gi', parseFloat(response.gi_price).toFixed(2));
                    $row.find('.price-ge').val(parseFloat(response.ge_price).toFixed(2));
                    $row.find('.price-gi').val(parseFloat(response.gi_price).toFixed(2));
                    calculateRowTotal($row);
                });
            });

            $(document).on('input', '.qty-input', function() {
                const $row = $(this).closest('tr');
                const unitId = $row.find('.unit-select').val();
                const productId = $row.find('.product-select').val();
                const storeId = $('#order_from_store').val();
                const qty = parseFloat($row.find('.qty-input').val()) || 1;

                if (unitId && productId && storeId) {
                    $.post('/orders/ajax/get-price', {
                        product_id: productId,
                        unit_id: unitId,
                        store_id: storeId,
                        quantity: qty
                    }, function(response) {
                        $row.find('.price-input').val(parseFloat(response.price).toFixed(2));
                        $row.find('.price-ge').val(parseFloat(response.ge_price).toFixed(2));
                        $row.find('.price-gi').val(parseFloat(response.gi_price).toFixed(2));
                        $row.find('.price-input').data('gi', parseFloat(response.gi_price).toFixed(
                            2));
                        $row.find('.price-input').data('ge', parseFloat(response.ge_price).toFixed(
                            2));
                        calculateRowTotal($row);
                    });
                } else {
                    calculateRowTotal($row);
                }
            });

            $('#discount_type, #discount_amount, #tax_type, #tax_amount').on('change input', function() {
                updateSummary();
            });

            function calculateRowTotal($row) {
                const priceGiDi = parseFloat($row.find('.price-input').val()) || 0;
                const price = parseFloat($row.find('.price-input').data('ge')) || 0;
                const qty = parseFloat($row.find('.qty-input').val()) || 0;
                $row.find('.row-total').text(CURRENCY_SYMBOL + (priceGiDi * qty).toFixed(2));
                updateSummary();
            }

            function updatePreview() {
                const $body = $('#orderPreviewBody');
                const $empty = $('#orderPreviewEmpty');
                const $container = $('#orderPreviewContainer');

                if (!$body.length) {
                    return;
                }

                $body.empty();
                let count = 0;

                function buildPreviewRowData($row, typeLabel) {
                    let nameText = '';
                    let metaText = '';

                    if (typeLabel === 'Product') {
                        const categoryText = $row.find('.category-select option:selected').text() || '';
                        const productText = $row.find('.product-select option:selected').text() || '';
                        const unitText = $row.find('.unit-select option:selected').text() || '';
                        nameText = productText;
                        metaText = `${categoryText} &bull; ${unitText}`;
                        if (!productText && !categoryText) return null;
                    } else {
                        const selectClass = '.other-item-select';
                        nameText = $row.find(selectClass + ' option:selected').text() || '';
                        metaText = typeLabel;
                        if (!nameText || nameText === 'Select ' + typeLabel.replace('Packaging Material',
                                'Material').replace('Other Item', 'Item')) return null;
                    }

                    const qtyVal = parseFloat($row.find('.qty-input').val());
                    let priceVal = 0;
                    let taxablePriceVal = 0;
                    if (typeLabel === 'Product') {
                        priceVal = parseFloat($row.find('.price-input').data('ge'));
                        taxablePriceVal = parseFloat($row.find('.price-input').val()) || 0;
                    } else {
                        priceVal = parseFloat($row.find('.price-input').val()) || 0;
                        taxablePriceVal = priceVal;
                    }

                    const hasContent = nameText || !isNaN(qtyVal) || !isNaN(priceVal);
                    if (!hasContent) return null;

                    const qty = isNaN(qtyVal) ? 0 : qtyVal;
                    const price = isNaN(priceVal) ? 0 : priceVal;
                    const total = qty * price;
                    const taxableTotal = qty * taxablePriceVal;
                    const displayProduct = nameText || '-';

                    let itemCgstPercent = parseFloat($row.attr('data-cgst-percent')) || 0;
                    let itemSgstPercent = parseFloat($row.attr('data-sgst-percent')) || 0;
                    const totalTaxPercent = itemCgstPercent + itemSgstPercent;

                    let basePrice = price;
                    if (typeLabel !== 'Product') {
                        const $checkbox = $row.find('.price-includes-tax-checkbox');
                        const priceIncludesTax = $checkbox.length ? ($checkbox.is(':checked') ? 1 : 0) : (parseInt(
                            $row.attr('data-price-includes-tax')) || 0);
                        if (priceIncludesTax === 1 && totalTaxPercent > 0) {
                            basePrice = price / (1 + totalTaxPercent / 100);
                        }
                    }

                    const baseTotal = qty * basePrice;
                    const displayPrice = CURRENCY_SYMBOL + basePrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    const totalDisplay = baseTotal ? (CURRENCY_SYMBOL + baseTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})) : '-';

                    const rowHtml = `
                        <tr class="preview-row">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <div class="preview-item-title">${displayProduct}</div>
                                        <div class="preview-item-meta text-muted">${metaText}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end align-middle">
                                <div class="preview-total fw-semibold">${totalDisplay}</div>
                                <div class="preview-qty text-muted">Qty: ${qty ? qty : '-'} × ${displayPrice}</div>
                            </td>
                        </tr>
                    `;

                    return {
                        html: rowHtml,
                        total: total,
                        baseTotal: baseTotal,
                        taxableTotal: taxableTotal,
                        cgstPercent: itemCgstPercent,
                        sgstPercent: itemSgstPercent,
                        taxSlabName: $row.attr('data-tax-slab-name') || 'None'
                    };
                }

                // --- Products Section: Group by Tax Slab ---
                let productSlabGroups = {};
                let hasProducts = false;
                let productGrandSubtotal = 0;
                let productGrandCgst = 0;
                let productGrandSgst = 0;

                $('#itemsTableBody tr').each(function() {
                    const result = buildPreviewRowData($(this), 'Product');
                    if (result) {
                        hasProducts = true;
                        const slabKey = result.taxSlabName + '_' + result.cgstPercent + '_' + result.sgstPercent;
                        if (!productSlabGroups[slabKey]) {
                            productSlabGroups[slabKey] = {
                                name: result.taxSlabName,
                                cgst: result.cgstPercent,
                                sgst: result.sgstPercent,
                                rows: '',
                                subtotal: 0,
                                taxableTotal: 0
                            };
                        }
                        productSlabGroups[slabKey].rows += result.html;
                        productSlabGroups[slabKey].subtotal += result.total;
                        productSlabGroups[slabKey].taxableTotal += result.taxableTotal;
                    }
                });

                if (hasProducts) {
                    $body.append(`
                        <tr>
                            <td colspan="2" class="fw-bold text-uppercase small text-muted pt-2 pb-1">Products</td>
                        </tr>
                    `);

                    Object.keys(productSlabGroups).forEach(function(slabKey) {
                        const group = productSlabGroups[slabKey];
                        const cgstVal = group.taxableTotal * group.cgst / 100;
                        const sgstVal = group.taxableTotal * group.sgst / 100;

                        $body.append(group.rows);

                        $body.append(`
                            <tr class="preview-subtotal-row">
                                <td class="pe-3">${group.name} Subtotal</td>
                                <td class="text-end">${CURRENCY_SYMBOL + group.subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        `);

                        if (group.cgst > 0 || group.sgst > 0) {
                            $body.append(`
                                <tr class="preview-cgst-row">
                                    <td class="ps-5">CGST (${group.cgst}%)</td>
                                    <td class="text-end">${CURRENCY_SYMBOL + cgstVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                </tr>
                                <tr class="preview-sgst-row">
                                    <td class="ps-5">SGST (${group.sgst}%)</td>
                                    <td class="text-end">${CURRENCY_SYMBOL + sgstVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                </tr>
                            `);
                        }

                        productGrandSubtotal += group.subtotal;
                        productGrandCgst += cgstVal;
                        productGrandSgst += sgstVal;
                    });

                    const productsTotal = productGrandSubtotal + productGrandCgst + productGrandSgst;
                    $body.append(`
                        <tr class="preview-grandtotal-row">
                            <td class="pe-3">Products Total</td>
                            <td class="text-end">${CURRENCY_SYMBOL + productsTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        </tr>
                    `);
                    count++;
                }

                // --- Other Items Section ---
                let otherSectionTotal = 0;
                let otherSectionRows = '';
                let hasOtherItems = false;

                $('#otherItemsTableBody tr').each(function() {
                    const result = buildPreviewRowData($(this), 'Other Item');
                    if (result) {
                        otherSectionRows += result.html;
                        const baseTotal = result.baseTotal;
                        const cgstAmt = baseTotal * result.cgstPercent / 100;
                        const sgstAmt = baseTotal * result.sgstPercent / 100;
                        otherSectionRows += `
                            <tr class="preview-cgst-row">
                                <td class="ps-5">CGST (${result.cgstPercent}%)</td>
                                <td class="text-end">${CURRENCY_SYMBOL + cgstAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                            <tr class="preview-sgst-row">
                                <td class="ps-5">SGST (${result.sgstPercent}%)</td>
                                <td class="text-end">${CURRENCY_SYMBOL + sgstAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        `;
                        otherSectionTotal += result.total;
                        hasOtherItems = true;
                    }
                });

                if (hasOtherItems) {
                    if (count > 0) $body.append('<tr><td colspan="2"><hr class="my-1"></td></tr>');
                    $body.append(`
                        <tr>
                            <td colspan="2" class="fw-bold text-uppercase small text-muted pt-2 pb-1">Other Items</td>
                        </tr>
                    `);
                    $body.append(otherSectionRows);
                    $body.append(`
                        <tr class="preview-subtotal-row">
                            <td class="pe-3">Other Items Subtotal</td>
                            <td class="text-end">${CURRENCY_SYMBOL + otherSectionTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        </tr>
                    `);
                    count++;
                }

                if (count === 0) {
                    $container.addClass('d-none');
                    $empty.show();
                } else {
                    $container.removeClass('d-none');
                    $empty.hide();
                }
            }

            function updateSummary() {
                let totalBasePrice = 0;
                let totalCgst = 0;
                let totalSgst = 0;

                // Per-product tax using each row's data attributes
                $('#itemsTableBody tr').each(function() {
                    const price = parseFloat($(this).find('.price-input').data('ge')) || 0;
                    const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                    const taxablePrice = parseFloat($(this).find('.price-input').val()) || 0;
                    const taxableTotal = taxablePrice * qty;

                    const baseTotal = price * qty;

                    // Per-product tax from row data attributes
                    const rowCgst = parseFloat($(this).attr('data-cgst-percent')) || 0;
                    const rowSgst = parseFloat($(this).attr('data-sgst-percent')) || 0;
                    const cgstAmt = taxableTotal * rowCgst / 100;
                    const sgstAmt = taxableTotal * rowSgst / 100;

                    totalBasePrice += baseTotal;
                    totalCgst += cgstAmt;
                    totalSgst += sgstAmt;
                });

                function processNonProductRow($row) {
                    const qty = parseFloat($row.find('.qty-input').val()) || 0;
                    const enteredPrice = parseFloat($row.find('.price-input').val()) || 0;

                    // Get tax details
                    const priceIncludesTax = $row.find('.price-includes-tax-checkbox').is(':checked') ? 1 : (
                        parseInt($row.attr('data-price-includes-tax')) || 0);
                    const cgstPercent = parseFloat($row.attr('data-cgst-percent')) || 0;
                    const sgstPercent = parseFloat($row.attr('data-sgst-percent')) || 0;
                    const totalTaxPercent = cgstPercent + sgstPercent;

                    // Calculate Base and Tax
                    let unitBasePrice = 0;
                    let unitTaxAmount = 0;

                    if (priceIncludesTax === 1 && totalTaxPercent > 0) {
                        unitBasePrice = enteredPrice / (1 + totalTaxPercent / 100);
                        unitTaxAmount = enteredPrice - unitBasePrice;
                    } else {
                        unitBasePrice = enteredPrice;
                        unitTaxAmount = enteredPrice * totalTaxPercent / 100;
                        unitBasePrice = unitBasePrice - unitTaxAmount;
                    }

                    const totalBase = unitBasePrice * qty;
                    const totalTax = unitTaxAmount * qty;

                    totalBasePrice += (totalBase + totalTax);
                }

                // Other Items
                $('#otherItemsTableBody tr').each(function() {
                    processNonProductRow($(this));
                });

                // Additional Charges
                let additionalChargesTotal = 0;
                $('.charge-amount-input').each(function() {
                    additionalChargesTotal += parseFloat($(this).val()) || 0;
                });

                // Discount (applied to subtotal)
                const discountType = $('#discount_type').val();
                const discountInput = parseFloat($('#discount_amount').val()) || 0;
                let discountValue = discountType === '0' ? (totalBasePrice * discountInput / 100) : discountInput;

                // Grand Total
                const grandTotal = totalBasePrice - discountValue + additionalChargesTotal + totalCgst + totalSgst;

                const depositText = $('#depositDisplay').text() || '0';
                const deposit = parseFloat(depositText.replace(/,/g, '')) || parseFloat($('#amount_collected')
                    .val()) || 0;
                const balanceDue = grandTotal - deposit;

                $('#tax_amount').val((totalCgst + totalSgst).toFixed(2));

                let totalItems = 0;
                totalItems += $('#itemsTableBody tr').length;
                totalItems += $('#otherItemsTableBody tr').length;

                $('#totalItemsCount').text(totalItems);
                $('#subtotalDisplay').text(totalBasePrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#discountValueDisplay').text('-' + CURRENCY_SYMBOL + discountValue.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                $('#additionalChargesTotalDisplay').text(additionalChargesTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#grandTotalDisplay').text(grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                $('#pendingAmountDisplay').text(CURRENCY_SYMBOL + balanceDue.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#balanceDueDisplay').text(CURRENCY_SYMBOL + balanceDue.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                if (balanceDue > 0) {
                    $('#balanceDueDisplay').removeClass('text-success').addClass('text-danger');
                } else {
                    $('#balanceDueDisplay').removeClass('text-danger').addClass('text-success');
                }

                updatePreview();
            }

            $(document).on('input', '.price-input', function() {
                calculateRowTotal($(this).closest('tr'));
            });

            // add/remove additional charges
            $('#addChargeRow').on('click', function() {
                const tpl = $('#chargeRowTemplate').html().replace(/__INDEX__/g, chargeIndex);
                $('#additionalChargesContainer').append(tpl);
                chargeIndex++;
            });

            $(document).on('click', '.btn-remove-charge', function() {
                const $row = $(this).closest('.charge-row');
                Swal.fire({
                    title: 'Remove Charge?',
                    text: "Are you sure you want to remove this charge?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $row.remove();
                        updateSummary();
                    }
                });
            });

            $(document).on('input', '.charge-amount-input', function() {
                updateSummary();
            });

            // Utencils handling (add more)
            $('#addUtencilRow').on('click', function() {
                const tpl = $('#utencilRowTemplate').html().replace(/__INDEX__/g, utencilIndex);
                $('#utencilsTableBody').append(tpl);
                const $row = $('#utencilsTableBody').find('tr.utencil-row').last();
                $row.find('.utencil-select').select2({
                    width: '100%'
                });
                $('#utencilsEmpty').hide();
                utencilIndex++;
            });

            $(document).on('click', '.btn-remove-utencil', function() {
                const $row = $(this).closest('tr.utencil-row');
                $row.remove();
                if (!$('#utencilsTableBody tr.utencil-row').length) {
                    $('#utencilsEmpty').show();
                }
            });

            // Prevent duplicate utencils in additional sends
            $(document).on('change', '.utencil-select', function() {
                const currentVal = $(this).val();
                if (!currentVal) return;

                let duplicate = false;
                $('.utencil-select').not(this).each(function() {
                    if ($(this).val() === currentVal) {
                        duplicate = true;
                        return false;
                    }
                });

                if (duplicate) {
                    Swal.fire('Duplicate',
                        'This utencil is already added. Please adjust its quantity instead.', 'warning');
                    $(this).val(null).trigger('change');
                }
            });

            $('#order_from_store').on('change', function() {
                $('#itemsTableBody tr').each(function() {
                    const $row = $(this);
                    const unitId = $row.find('.unit-select').val();
                    const productId = $row.find('.product-select').val();
                    const storeId = $('#order_from_store').val();
                    if (unitId && productId && storeId) {
                        $.post('/orders/ajax/get-price', {
                            product_id: productId,
                            unit_id: unitId,
                            store_id: storeId
                        }, function(response) {
                            $row.find('.price-ge').val(parseFloat(response.ge_price)
                                .toFixed(2));
                            $row.find('.price-gi').val(parseFloat(response.gi_price)
                                .toFixed(2));
                            $row.find('.price-input').data('gi', parseFloat(response
                                .gi_price).toFixed(2));
                            $row.find('.price-input').data('ge', parseFloat(response
                                .ge_price).toFixed(2));
                            calculateRowTotal($row);
                        });
                    }
                });
            });

            $('#payment_received').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#payment_details_wrapper').slideDown();
                    $('#amount_collected').prop('required', true);
                } else {
                    $('#payment_details_wrapper').slideUp();
                    $('#amount_collected').prop('required', false).val('');
                }
            });

            /**
             * Same as Bill To - copy all Bill To fields (including map data) to Ship To
             */
            $('#same_as_bill_to_switch').on('change', function() {
                const isChecked = $(this).is(':checked');
                if (isChecked) {
                    $('#shipping_name').val($('#billing_name').val());
                    $('#shipping_contact_number').val($('#billing_contact_number').val());
                    $('#shipping_address_1').val($('#billing_address_1').val());
                    $('#shipping_google_map_link').val($('#billing_google_map_link').val());
                    $('#shipping_latitude').val($('#billing_latitude').val());
                    $('#shipping_longitude').val($('#billing_longitude').val());
                }
            });

            /**
             * Clear hidden lat/long when Google Map Link is cleared manually
             */
            $('#billing_google_map_link').on('input', function() {
                if (!$(this).val()) {
                    $('#billing_latitude').val('');
                    $('#billing_longitude').val('');
                }
            });

            $('#shipping_google_map_link').on('input', function() {
                if (!$(this).val()) {
                    $('#shipping_latitude').val('');
                    $('#shipping_longitude').val('');
                }
            });

            /**
             * Google Map modal + picker logic
             * NOTE: Uses only Google Maps JS API, no other third-party libraries.
             */
            function initializeMapIfNeeded() {
                if (mapInstance) {
                    google.maps.event.trigger(mapInstance, 'resize');
                    return;
                }

                const defaultCenter = {
                    lat: 22.9734,
                    lng: 78.6569
                }; // India center fallback
                mapInstance = new google.maps.Map(document.getElementById('mapContainer'), {
                    center: defaultCenter,
                    zoom: 5,
                });

                mapMarker = new google.maps.Marker({
                    map: mapInstance,
                    draggable: true,
                });

                // Click on map sets marker
                mapInstance.addListener('click', function(event) {
                    setMarkerPosition(event.latLng);
                });

                // Dragging marker updates selection
                mapMarker.addListener('dragend', function(event) {
                    setMarkerPosition(event.latLng);
                });

                const input = document.getElementById('map_search_input');
                mapSearchBox = new google.maps.places.SearchBox(input);

                mapSearchBox.addListener('places_changed', function() {
                    const places = mapSearchBox.getPlaces();
                    if (!places || !places.length) {
                        return;
                    }
                    const place = places[0];
                    if (!place.geometry || !place.geometry.location) {
                        return;
                    }
                    mapInstance.panTo(place.geometry.location);
                    mapInstance.setZoom(17);
                    setMarkerPosition(place.geometry.location);
                });
            }

            function setMarkerPosition(latLng) {
                selectedLatLng = latLng;
                mapMarker.setPosition(latLng);
                $('#selected_lat_display').text(latLng.lat().toFixed(6));
                $('#selected_lng_display').text(latLng.lng().toFixed(6));
            }

            $('.open-map-picker-btn').on('click', function() {
                activeAddressContext = $(this).data('address-context'); // 'billing' or 'shipping'

                // Prefill map from existing lat/lng if available
                let latField = activeAddressContext === 'billing' ? '#billing_latitude' :
                    '#shipping_latitude';
                let lngField = activeAddressContext === 'billing' ? '#billing_longitude' :
                    '#shipping_longitude';
                const latVal = parseFloat($(latField).val());
                const lngVal = parseFloat($(lngField).val());

                $('#selected_lat_display').text('-');
                $('#selected_lng_display').text('-');
                $('#map_search_input').val('');

                $('#googleMapModal').one('shown.bs.modal', function() {
                    initializeMapIfNeeded();

                    if (!isNaN(latVal) && !isNaN(lngVal)) {
                        const existingLatLng = new google.maps.LatLng(latVal, lngVal);
                        mapInstance.setCenter(existingLatLng);
                        mapInstance.setZoom(17);
                        setMarkerPosition(existingLatLng);
                    } else {
                        mapInstance.setZoom(5);
                    }
                });
            });

            $('#applyMapSelectionBtn').on('click', function() {
                if (!activeAddressContext || !selectedLatLng) {
                    $('#googleMapModal').modal('hide');
                    return;
                }

                const lat = selectedLatLng.lat();
                const lng = selectedLatLng.lng();
                const mapLink = 'https://www.google.com/maps?q=' + lat + ',' + lng;

                if (activeAddressContext === 'billing') {
                    $('#billing_latitude').val(lat);
                    $('#billing_longitude').val(lng);
                    $('#billing_google_map_link').val(mapLink);
                } else if (activeAddressContext === 'shipping') {
                    $('#shipping_latitude').val(lat);
                    $('#shipping_longitude').val(lng);
                    $('#shipping_google_map_link').val(mapLink);
                }

                $('#googleMapModal').modal('hide');
            });

            // Reset context when modal hidden
            $('#googleMapModal').on('hidden.bs.modal', function() {
                activeAddressContext = null;
                selectedLatLng = null;
                $('#selected_lat_display').text('-');
                $('#selected_lng_display').text('-');
            });

            $('input[name="bill_to_type"]').on('change', function() {
                const type = $(this).val();
                $('#bill_to_store_wrapper, #bill_to_user_wrapper, #bill_to_factory_wrapper').hide().find(
                    'select').prop('disabled', true).prop('required', false);

                if (type === 'store') {
                    $('#bill_to_store_wrapper').show().find('select').prop('disabled', false).prop(
                        'required', true);
                } else if (type === 'user') {
                    $('#bill_to_user_wrapper').show().find('select').prop('disabled', false).prop(
                        'required', true);
                } else if (type === 'factory') {
                    $('#bill_to_factory_wrapper').show().find('select').prop('disabled', false).prop(
                        'required', true);
                }
            });

            $('#orderForm').validate({
                ignore: [],
                errorElement: 'span',
                errorClass: 'text-danger small',
                errorPlacement: function(error, element) {
                    if (element.hasClass("select2-hidden-accessible")) {
                        error.insertAfter(element.next(".select2"));
                    } else if (element.attr("name") == "time_slot") {
                        error.appendTo("#time_slot_error");
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    if ($('#itemsTableBody tr').length === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'No Items',
                            text: 'Please add at least one item.'
                        });
                        return false;
                    }
                    form.submit();
                }
            });

            // Pre-fill addresses based on Receiver Store
            $('#order_from_store').on('change', function() {
                const storeId = $(this).val();
                const orderType = $('#order_type').val();

                if (storeId && (orderType === 'company' || orderType === 'franchise' || orderType ===
                        'dealer')) {
                    $.get('{{ route('orders.ajax.store-details', '') }}/' + storeId, function(response) {
                        if (response.status) {
                            const data = response.data;
                            Swal.fire({
                                title: 'Fill Address Details?',
                                text: "Do you want to fill billing and shipping addresses from the selected store's data?",
                                icon: 'question',
                                showCancelButton: true,
                                showDenyButton: true,
                                confirmButtonText: 'Fill/Replace',
                                denyButtonText: 'Append',
                                cancelButtonText: 'Skip'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Replace
                                    fillAddresses(data, true);
                                } else if (result.isDenied) {
                                    // Append
                                    fillAddresses(data, false);
                                }
                            });
                        }
                    });
                }
            });

            function fetchAddress(toGet, toPlace) {
                let url = $(`#${toGet}`).val();

                if (!url) {
                    Swal.fire('Failed', 'Please enter map link to fetch address', 'failed');
                    return;
                }

                $.ajax({
                    url: "{{ route('get-address') }}",
                    type: 'POST',
                    data: {
                        url: url,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        $(`#${toPlace}`).text('');
                        $('body').find('.LoaderSec').removeClass('d-none');
                    },
                    success: function(response) {
                        $(`#${toPlace}`).text(response.address);
                    },
                    error: function(xhr) {
                        let errorMsg = 'Something went wrong';

                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }

                        Swal.fire('Failed', errorMsg, 'failed');
                    },
                    complete: function() {
                        $('body').find('.LoaderSec').addClass('d-none');
                    }
                });
            }

            function fillAddresses(data, replace) {
                const fields = ['name', 'contact_number', 'address_1', 'gst_in'];
                const dataMap = {
                    'name': data.name,
                    'contact_number': data.mobile,
                    'address_1': data.address1,
                    'gst_in': ''
                };

                ['billing', 'shipping'].forEach(prefix => {
                    fields.forEach(field => {
                        const $input = $(`#${prefix}_${field}`);
                        const value = dataMap[field] || '';
                        if (replace) {
                            $input.val(value);
                        } else if (value) {
                            const currentVal = $input.val();
                            $input.val(currentVal ? currentVal + ', ' + value : value);
                        }
                    });
                });
            }

            updateSummary();

            // --- New Sections JS ---

            let otherIndex = {{ $order->otherItems->count() + 1 }};

            const otherItemRowTemplate = `
                <tr data-row-index="__INDEX__" data-pricing-type="fixed">
                    <td>
                        <select class="form-control select2 other-item-select" name="other_items[__INDEX__][other_item_id]" required>
                            <option value="">Select Item</option>
                            @foreach ($otherItems as $o)
                                <option value="{{ $o->id }}">{{ $o->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" class="pricing-type-input" name="other_items[__INDEX__][pricing_type]" value="fixed">
                    </td>
                    <td class="text-center align-middle">
                        <input type="checkbox" class="form-check-input price-includes-tax-checkbox"
                            name="other_items[__INDEX__][price_includes_tax]" value="1">
                    </td>
                    <td>
                        <select class="form-control form-select-sm tax-slab-select" name="other_items[__INDEX__][tax_slab_id]">
                            <option value="">None</option>
                            @foreach ($taxSlabs as $slab)
                                <option value="{{ $slab->id }}" data-cgst="{{ $slab->cgst }}" data-sgst="{{ $slab->sgst }}">{{ $slab->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm price-input" name="other_items[__INDEX__][unit_price]" value="0" min="0" step="0.01" required>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm qty-input" name="other_items[__INDEX__][quantity]" value="1" min="0.01" step="0.01" required>
                    </td>
                    <td class="row-total fw-bold text-end pe-3">{{ Helper::defaultCurrencySymbol() }}0.00</td>
                    <td class="text-center">
                        <i class="fas fa-times-circle btn-remove-row" title="Remove"></i>
                    </td>
                </tr>
            `;

            $('#addOtherItemRow').on('click', function() {
                $('#otherItemsTableBody').append(otherItemRowTemplate.replace(/__INDEX__/g, otherIndex++));
                $('#otherItemsTableBody tr:last .select2').select2({
                    width: '100%'
                });
                $('#noOtherItemsMessage').hide();
            });

            // Remove Row Handler (Generic for all tables)
            $(document).on('click', '.btn-remove-row', function() {
                const $row = $(this).closest('tr');
                const $tbody = $row.closest('tbody');
                $row.remove();

                if ($tbody.attr('id') === 'otherItemsTableBody' && $tbody.children().length === 0) $(
                    '#noOtherItemsMessage').show();

                updateSummary();
            });

            // Fetch Details Handlers
            function fetchItemDetails($select, type) {
                const id = $select.val();
                const $row = $select.closest('tr');
                const $priceInput = $row.find('.price-input');
                const $asPerActualLabel = $row.find('.as-per-actual-label');
                const $pricingTypeInput = $row.find('.pricing-type-input');
                const $taxCheckbox = $row.find('.price-includes-tax-checkbox');

                if (!id) {
                    $priceInput.val('').show().prop('required', true);
                    $asPerActualLabel.addClass('d-none');
                    $pricingTypeInput.val('fixed');
                    $row.attr('data-pricing-type', 'fixed');
                    $row.removeAttr('data-price-includes-tax data-cgst-percent data-sgst-percent');
                    $taxCheckbox.prop('checked', false);
                    $row.find('.tax-slab-select').val('').trigger('change.select2');
                    $row.find('.row-total').text(CURRENCY_SYMBOL + '0.00');
                    calculateRowTotal($row);
                    return;
                }

                $.post('/orders/ajax/get-item-details', {
                    type: type,
                    id: id,
                    _token: '{{ csrf_token() }}'
                }, function(response) {
                    const pricingType = response.pricing_type || 'fixed';
                    $pricingTypeInput.val(pricingType);
                    $row.attr('data-pricing-type', pricingType);
                    $row.attr('data-price-includes-tax', response.price_includes_tax || 0);
                    $row.attr('data-cgst-percent', response.cgst_percent || 0);
                    $row.attr('data-sgst-percent', response.sgst_percent || 0);

                    if (response.tax_slab_id) {
                        $row.find('.tax-slab-select').val(response.tax_slab_id).trigger('change.select2');
                    } else {
                        $row.find('.tax-slab-select').val('').trigger('change.select2');
                    }

                    // Prefill the price_includes_tax checkbox
                    $taxCheckbox.prop('checked', response.price_includes_tax == 1);

                    // Always show price input (even for as_per_actual)
                    $priceInput.val(parseFloat(response.price).toFixed(2)).show().prop('required', true);
                    $asPerActualLabel.addClass('d-none');
                    calculateRowTotal($row);
                });
            }

            // On change of tax slab select, update row data attributes for percent logic
            $(document).on('change', '.tax-slab-select', function() {
                const $row = $(this).closest('tr');
                const $option = $(this).find('option:selected');
                const cgst = parseFloat($option.data('cgst')) || 0;
                const sgst = parseFloat($option.data('sgst')) || 0;

                $row.attr('data-cgst-percent', cgst);
                $row.attr('data-sgst-percent', sgst);
                calculateRowTotal($row);
            });

            $(document).on('change', '.other-item-select', function() {
                fetchItemDetails($(this), 'other_item');
            });

            // Recalculate when price_includes_tax checkbox is toggled
            $(document).on('change', '.price-includes-tax-checkbox', function() {
                const $row = $(this).closest('tr');
                $row.attr('data-price-includes-tax', $(this).is(':checked') ? 1 : 0);
                calculateRowTotal($row);
            });
        });
    </script>
@endpush
