@extends('layouts.app-master')

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --brand-primary: #012440;
            --brand-surface: #f8fafc;
            --brand-border: #e2e8f0;
        }

        .select2-container {
            width: 100% !important;
        }

        .items-table th,
        .other-items-table th {
            background: #f1f5f9;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }
        .preview-grandtotal-row {
            background: #f1f5f9;
            font-weight: 600;
        }
        .items-table td {
            vertical-align: middle;
        }

        .btn-remove-row {
            color: #dc3545;
            cursor: pointer;
        }

        .btn-remove-row:hover {
            color: #a71d2a;
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

        .customer-section {
            display: none;
            background: #fffbeb;
            border: 1px dashed #fbbf24;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .form-switch .form-check-input {
            width: 30px;
            height: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .type-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 4px;
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
                    {{-- Order Details Card --}}
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
                                                } else if ($tmp == 'dealer-location') {
                                                    return 'dealer';
                                                } else {
                                                    return 'company';
                                                }
                                            });
                                        @endphp
                                        @foreach ($groupedStores as $typeName => $storeGroup)
                                            <optgroup label="{{ strtoupper($typeName) }}">
                                                @foreach ($storeGroup as $store)
                                                    <option value="{{ $store->id }}"
                                                        data-order-type="{{ $typeName }}">
                                                        {{ $store->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="receiver_store_id" id="receiver_store_id">
                                    <input type="hidden" name="order_type" id="order_type" value="company">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Dispatched From <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control select2" name="sender_store_id" id="sender_store_id"
                                        required>
                                        <option value="">Select Store</option>
                                        @php
                                            $groupedStores = $storesWithType->groupBy(function ($s) {
                                                return optional($s->storetype)->name ?: 'Other';
                                            });
                                        @endphp
                                        @foreach ($groupedStores as $typeName => $storeGroup)
                                            <optgroup label="{{ strtoupper($typeName) }}">
                                                @foreach ($storeGroup as $store)
                                                    <option value="{{ $store->id }}"
                                                        @if ($typeName == 'factory') selected @endif
                                                        data-order-type="{{ \Illuminate\Support\Str::contains(strtolower($typeName), 'franchise') ? 'franchise' : 'company' }}">
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
                                            id="for_customer" value="1">
                                        <label class="form-check-label fw-semibold" for="for_customer">
                                            Ordering for Customer
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 3 + 4: Customer Name + Delivery Remarks (shown only when for_customer) --}}
                            <div id="customerSection" class="customer-section">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Customer Name</label>
                                        <input type="text" class="form-control" name="customer_first_name"
                                            id="customer_first_name">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="customer_phone_number"
                                            id="customer_phone_number">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Alternate Person Name</label>
                                        <input type="text" class="form-control" name="alternate_name"
                                            id="alternate_name" value="">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Alternate Phone Number</label>
                                        <input type="text" class="form-control" name="alternate_phone_number"
                                            id="alternate_phone_number" value="">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Delivery Remarks</label>
                                        <textarea class="form-control" name="customer_remark" id="customer_remark" rows="2"></textarea>
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
                                            id="delivery_date" placeholder="Select Date" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Time Slot</label>
                                    <select class="form-control select2" name="time_slot" id="time_slot_select">
                                        <option value="">Select Time Slot</option>
                                    </select>
                                    <div id="time_slot_error"></div>
                                </div>
                            </div>

                            {{-- Row 6: Deliver to Store Checkbox + Delivery Address --}}
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="delivery_to_store"
                                            id="delivery_to_store" value="1">
                                        <label class="form-check-label fw-semibold" for="delivery_to_store">
                                            Deliver to Store
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Delivery Address <span
                                            class="text-danger">*</span> </label>
                                    <textarea class="form-control" name="delivery_address" id="delivery_address" rows="2" required></textarea>
                                </div>
                            </div>

                            {{-- Row 7: Delivery Address Map Link --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Delivery Address Map Link</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="delivery_link"
                                            id="delivery_link" placeholder="Paste Google Map link">
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
                                    <select class="form-control select2" name="handling_instructions[]"
                                        id="handling_instructions" multiple>
                                        @foreach ($handlingInstructions as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Row 9: Handling Instructions Remarks --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Handling Instructions Remarks</label>
                                    <textarea class="form-control" name="handling_note" id="handling_note" rows="2"></textarea>
                                </div>
                            </div>

                            {{-- Row 10: Driver & Vehicle --}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Driver Selection</label>
                                    <select class="form-control select2" name="delivery_user" id="delivery_user">
                                        <option value="">Select Driver</option>
                                        @foreach ($drivers as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Vehicle Selection</label>
                                    <select class="form-control select2" name="vehicle_id" id="vehicle_id">
                                        <option value="">Select Vehicle</option>
                                        @foreach ($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}">{{ $vehicle->name }} -
                                                {{ $vehicle->make }} -
                                                {{ $vehicle->number }}
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
                                            id="utencils_collected" value="1">
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
                                            id="collect_on_delivery" value="1">
                                        <label class="form-check-label fw-semibold" for="collect_on_delivery">
                                            Collect Amount on Delivery
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 mb-3" id="collect_amount_wrapper" style="display:none;">
                                    <label class="form-label fw-semibold">Amount Received</label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        name="amount_collected" id="amount_collected" placeholder="0.00">
                                </div>
                            </div>

                            {{-- Hidden payment_received (driven by Collect Amount on Delivery) --}}
                            <input type="hidden" name="payment_received" id="payment_received_hidden" value="0">
                        </div>
                    </div>

                    {{-- Addresses Section --}}
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Addresses</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="same_as_bill_to_switch"
                                    name="bill_to_same_as_ship_to">
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
                                                name="billing_name" id="billing_name">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small fw-bold">Contact Number</label>
                                            <input type="text" class="form-control form-control-sm"
                                                name="billing_contact_number" id="billing_contact_number">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small fw-bold">GST IN</label>
                                            <input type="text" class="form-control form-control-sm"
                                                name="billing_gst_in" id="billing_gst_in">
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Full Address</label>
                                            <textarea class="form-control form-control-sm" name="billing_address_1" id="billing_address_1"
                                                style="field-sizing: content;"></textarea>
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Google Map Link</label>
                                            <div class="input-group input-group-sm">
                                                <input type="url" class="form-control" name="billing_google_map_link"
                                                    id="billing_google_map_link"
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
                                            <input type="hidden" name="billing_latitude" id="billing_latitude">
                                            <input type="hidden" name="billing_longitude" id="billing_longitude">
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
                                                name="shipping_name" id="shipping_name">
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Contact Number</label>
                                            <input type="text" class="form-control form-control-sm"
                                                name="shipping_contact_number" id="shipping_contact_number">
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Full Address</label>
                                            <textarea class="form-control form-control-sm" name="shipping_address_1" id="shipping_address_1"
                                                style="field-sizing: content;"></textarea>
                                        </div>
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label small fw-bold">Google Map Link</label>
                                            <div class="input-group input-group-sm">
                                                <input type="url" class="form-control"
                                                    name="shipping_google_map_link" id="shipping_google_map_link"
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
                                            <input type="hidden" name="shipping_latitude" id="shipping_latitude">
                                            <input type="hidden" name="shipping_longitude" id="shipping_longitude">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Items Card --}}
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
                            <div id="noItemsMessage" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i><br>
                                Click "Add Item" to add products.
                            </div>
                        </div>
                    </div>

                    <!-- Other Items Section -->
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-cubes me-2 text-primary"></i>Other Items</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="addOtherItemRow">
                                <i class="fas fa-plus me-1"></i> Add Item
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table other-items-table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 25%">Item</th>
                                            <th style="width: 15%">Tax Slab</th>
                                            <th class="text-center" style="width: 10%">Price Incl. Tax</th>
                                            <th style="width: 12%">Price</th>
                                            <th style="width: 10%">Qty</th>
                                            <th style="width: 18%">Total</th>
                                            <th class="text-center" style="width: 10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="otherItemsTableBody"></tbody>
                                </table>
                            </div>
                            <div id="noOtherItemsMessage" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i><br>
                                Click "Add Item" to add other items.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">

                    {{-- Summary Card --}}
                    <div class="card border-0 mb-2 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0"><i class="fas fa-receipt me-2 text-primary"></i>Order Summary</h6>
                        </div>
                        <div class="card-body p-0">
                            <div id="orderPreviewEmpty" class="text-center text-muted small py-3">
                                No items added yet. Add items to see a quick preview here.
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

                            {{-- Discount --}}
                            <div class="summary-row flex-column align-items-start">
                                <div class="d-flex w-100 justify-content-between mb-1">
                                    <label class="text-muted small mb-0">Discount</label>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0" id="toggle_discount_edit">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                </div>
                                <div class="d-flex w-100 gap-2">
                                    <select class="form-select form-select-sm discount-input" name="discount_type" id="discount_type"
                                        style="width: 80px; pointer-events: none; background-color: #e9ecef;">
                                        <option value="0">%</option>
                                        <option value="1">Fix</option>
                                    </select>
                                    <input type="number" step="0.01" min="0"
                                        class="form-control form-control-sm discount-input" name="discount_amount" id="discount_amount"
                                        placeholder="0" value="0" readonly style="background-color: #e9ecef;">
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
                                    {{-- dynamic rows here --}}
                                </div>
                                <div class="d-flex justify-content-between w-100 mt-1">
                                    <span class="text-muted small">Total Additional Charges:</span>
                                    <span class="text-primary small">{{ Helper::defaultCurrencySymbol() }}<span
                                            id="additionalChargesTotalDisplay">0.00</span></span>
                                </div>
                            </div>

                            </div>
                            {{-- CGST & SGST display removed — tax is now per-product and shown in preview --}}

                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Grand Total:</span>
                                <span class="grand-total text-primary">{{ Helper::defaultCurrencySymbol() }}<span
                                        id="grandTotalDisplay">0.00</span></span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-check me-1"></i> Create Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

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

    <template id="otherItemRowTemplate">
        <tr class="other-item-row" data-row-index="__INDEX__" data-pricing-type="fixed">
            <td>
                <select class="form-control form-control-sm other-item-select"
                    name="other_items[__INDEX__][other_item_id]" required>
                    <option value="">Select Item</option>
                    @foreach ($otherItems as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <input type="hidden" class="pricing-type-input" name="other_items[__INDEX__][pricing_type]"
                    value="fixed">
            </td>
            <td>
                <select class="form-control form-control-sm tax-slab-select" name="other_items[__INDEX__][tax_slab_id]">
                    <option value="" data-cgst="0" data-sgst="0">None</option>
                    @foreach ($taxSlabs as $slab)
                        <option value="{{ $slab->id }}" data-cgst="{{ $slab->cgst }}"
                            data-sgst="{{ $slab->sgst }}">
                            {{ $slab->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="text-center">
                <input type="hidden" name="other_items[__INDEX__][price_includes_tax]" value="0">
                <input type="checkbox" class="form-check-input price-includes-tax-checkbox"
                    name="other_items[__INDEX__][price_includes_tax]" value="1">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm price-input"
                    name="other_items[__INDEX__][unit_price]" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm qty-input"
                    name="other_items[__INDEX__][quantity]" placeholder="1" required>
            </td>
            <td><strong class="row-total">{{ Helper::defaultCurrencySymbol() }}0.00</strong></td>
            <td class="text-center"><span class="btn-remove-row" title="Remove"><i
                        class="fas fa-times-circle fa-lg"></i></span></td>
        </tr>
    </template>

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
            <td>
                <select class="form-control form-control-sm product-select" name="items[__INDEX__][product_id]" required
                    disabled>
                    <option value="">Select Product</option>
                </select>
                <input type="hidden" class="tax-slab-id-input" name="items[__INDEX__][tax_slab_id]" value="">
            </td>
            <td>
                <select class="form-control form-control-sm unit-select" name="items[__INDEX__][unit_id]" required
                    disabled>
                    <option value="">Unit</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm price-input"
                    name="items[__INDEX__][unit_price]" placeholder="0.00" required>
                <input type="hidden" class="price-ge" name="items[__INDEX__][ge_price]" value="0">
                <input type="hidden" class="price-gi" name="items[__INDEX__][gi_price]" value="0">
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm qty-input"
                    name="items[__INDEX__][quantity]" placeholder="1" required>
            </td>
            <td><strong class="row-total">{{ Helper::defaultCurrencySymbol() }}0.00</strong></td>
            <td class="text-center"><span class="btn-remove-row" title="Remove"><i
                        class="fas fa-times-circle fa-lg"></i></span></td>
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
    {{-- Utencil Movement Modal --}}
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
@endsection

@push('js')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_KEY') }}&libraries=places" async defer>
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            let rowIndex = 0;
            let CURRENCY_SYMBOL = "{{ Helper::defaultCurrencySymbol() }}";
            let chargeIndex = 0;
            let utencilIndex = 0;
            let utencilMovements = []; // {id, name, sent, received, note}
            let activeAddressContext = null; // 'billing' or 'shipping'
            let mapInstance = null;
            let mapMarker = null;
            let mapSearchBox = null;
            let selectedLatLng = null;

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            });
            $('.select2').select2();

            $('#modal_utencil_id').select2({
                dropdownParent: $('#utencilModal')
            });

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
                        optionsHtml += `<option value="${slot}">${label}</option>`;
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

            // Order From -> set receiver_store_id and order_type
            $('#order_from_store').on('change', function() {
                const $opt = $(this).find('option:selected');
                const storeId = $opt.val();
                const orderType = $opt.data('order-type') || 'company';
                $('#receiver_store_id').val(storeId);
                $('#order_type').val(orderType);
            });

            // Deliver to Store Toggle
            function applyDeliverToStore() {
                const storeId = $('#order_from_store').val();
                if (!storeId) {
                    $('#delivery_to_store').prop('checked', false);
                    return;
                }
                $.get('{{ route('orders.ajax.store-details', '') }}/' + storeId, function(response) {
                    if (response.status) {
                        const data = response.data;
                        const storeAddress = data.address1 || '';

                        // Pre-fill and disable Delivery Address
                        $('#delivery_address').val(storeAddress).prop('disabled', true);
                        $('#delivery_link').val(data.location_url || '').prop('disabled', true);

                        // Pre-fill and disable Ship To fields
                        $('#shipping_name').val(data.name || '').prop('disabled', true);
                        $('#shipping_contact_number').val(data.mobile || '').prop('disabled', true);
                        $('#shipping_address_1').val(storeAddress).prop('disabled', true);
                        $('#shipping_google_map_link').val(data.location_url || '').prop('disabled', true);
                        $('#shipping_gst_in').val(data.gst_in || '').prop('disabled', true);
                    }
                });
            }

            function clearDeliverToStore() {
                // Re-enable and clear Delivery Address
                $('#delivery_address').prop('disabled', false).val('');
                $('#delivery_link').prop('disabled', false).val('');

                // Re-enable and clear Ship To fields
                $('#shipping_name').prop('disabled', false).val('');
                $('#shipping_contact_number').prop('disabled', false).val('');
                $('#shipping_address_1').prop('disabled', false).val('');
                $('#shipping_google_map_link').prop('disabled', false).val('');
                $('#shipping_gst_in').prop('disabled', false).val('');
            }

            $('#delivery_to_store').on('change', function() {
                if ($(this).is(':checked')) {
                    applyDeliverToStore();
                } else {
                    clearDeliverToStore();
                }
            });

            // Re-apply when store changes while checkbox is checked
            $('#order_from_store').on('change', function() {
                if ($('#delivery_to_store').is(':checked')) {
                    applyDeliverToStore();
                }
            });

            // For Customer Toggle
            $('#for_customer').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#customerSection').slideDown();
                    $('#customer_first_name').prop('required', true);
                    $('#customer_phone_number').prop('required', true);
                } else {
                    $('#customerSection').slideUp();
                    $('#customer_first_name').prop('required', false).val('');
                    $('#customer_phone_number').prop('required', false).val('');
                    $('#customer_remark').val('');
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

            // --- Other Items ---
            $('#addOtherItemRow').on('click', function() {
                const template = $('#otherItemRowTemplate').html().replace(/__INDEX__/g, rowIndex++);
                $('#otherItemsTableBody').append(template);
                $('#noOtherItemsMessage').hide();
                // init select2
                $(`tr[data-row-index="${rowIndex - 1}"]`).find('.other-item-select, .tax-slab-select')
                    .select2({
                        placeholder: 'Select',
                        width: '100%'
                    });
            });
            // Remove logic for new sections (re-uses .btn-remove-row, but we need to check empty state)
            $(document).on('click', '.btn-remove-row', function() {
                const $row = $(this).closest('tr');
                const $tbody = $row.closest('tbody');
                const isOther = $tbody.attr('id') === 'otherItemsTableBody';
                const isItem = $tbody.attr('id') === 'itemsTableBody';

                Swal.fire({
                    title: 'Remove Item?',
                    text: "Are you sure you want to remove this item?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $row.remove();
                        if (isItem && $('#itemsTableBody tr').length === 0) $('#noItemsMessage')
                            .show();
                        if (isOther && $('#otherItemsTableBody tr').length === 0) $(
                            '#noOtherItemsMessage').show();
                        updateSummary();
                    }
                });
            });

            // Category Change -> Load Products
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
                                `<option value="${p.id}" data-sku="${p.sku}" data-tax-slab-id="${p.tax_slab_id || ''}" data-tax-slab-name="${p.tax_slab_name || 'None'}" data-cgst="${p.cgst || 0}" data-sgst="${p.sgst || 0}">${p.name} (${p.sku})</option>`;
                        });
                        $productSelect.html(options).prop('disabled', false).trigger(
                            'change.select2');
                    });
                }
            });

            // Product Change -> Load Units (with duplicate check) + set tax slab data
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

            // Unit Change -> Get Price (with duplicate check)
            $(document).on('change', '.unit-select', function() {
                const $row = $(this).closest('tr');
                const unitId = $(this).val();
                const productId = $row.find('.product-select').val();
                const storeId = $('#order_from_store').val();
                const $priceInput = $row.find('.price-input');
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
                    $priceInput.val(parseFloat(response.price).toFixed(2));
                    $priceInput.data('gi', parseFloat(response.gi_price).toFixed(2));
                    $priceInput.data('ge', parseFloat(response.ge_price).toFixed(2));
                    $row.find('.price-ge').val(parseFloat(response.ge_price).toFixed(2));
                    $row.find('.price-gi').val(parseFloat(response.gi_price).toFixed(2));
                    calculateRowTotal($row);
                });
            });

            // Quantity/Price Change -> Recompute discounted price & total
            $(document).on('input', '.qty-input', function() {
                const $row = $(this).closest('tr');
                const unitId = $row.find('.unit-select').val();
                const productId = $row.find('.product-select').val();
                const storeId = $('#order_from_store').val();
                const qty = parseFloat($row.find('.qty-input').val()) || 1;

                // If we have a full product+unit selection, refresh price with discount based on qty
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

            // Discount/Tax Change
            $('#discount_type, #discount_amount, #tax_type, #tax_amount').on('change input', function() {
                updateSummary();
            });

            function calculateRowTotal($row) {
                const priceGiDi = parseFloat($row.find('.price-input').val()) || 0;
                const qty = parseFloat($row.find('.qty-input').val()) || 0;
                $row.find('.row-total').text(CURRENCY_SYMBOL + (priceGiDi * qty).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
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

                // Helper to build a preview row HTML (used for both products and other items)
                function buildPreviewRowData($row, typeLabel) {
                    let nameText = '';
                    let metaText = '';
                    let pricingType = 'fixed';

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
                        pricingType = $row.attr('data-pricing-type') || 'fixed';
                        if (!nameText || nameText === 'Select ' + typeLabel.replace('Packaging Material',
                                'Material').replace('Other Item', 'Item')) return null;
                    }

                    const qtyVal = parseFloat($row.find('.qty-input').val());
                    let priceVal = 0;
                    let taxablePriceVal = 0;
                    if (typeLabel === 'Product') {
                        priceVal = parseFloat($row.find('.price-ge').val());
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
                        const priceIncludesTax = $row.find('.price-includes-tax-checkbox').is(':checked') ? 1 : (
                            parseInt($row.attr('data-price-includes-tax')) || 0);
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
                let productSlabGroups = {}; // { slabName: { cgst, sgst, rows: html, subtotal, taxableTotal } }
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
                    // Products header
                    $body.append(`
                        <tr>
                            <td colspan="2" class="fw-bold text-uppercase small text-muted pt-2 pb-1">Products</td>
                        </tr>
                    `);

                    // Render each slab group
                    Object.keys(productSlabGroups).forEach(function(slabKey) {
                        const group = productSlabGroups[slabKey];
                        const cgstVal = group.taxableTotal * group.cgst / 100;
                        const sgstVal = group.taxableTotal * group.sgst / 100;

                        // Product rows for this slab
                        $body.append(group.rows);

                        // Slab subtotal
                        $body.append(`
                            <tr class="preview-subtotal-row">
                                <td class="pe-3">${group.name} Subtotal</td>
                                <td class="text-end">${CURRENCY_SYMBOL + group.subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        `);

                        // CGST / SGST rows for this slab
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

                    // Products Total (subtotal + all slab taxes)
                    const productsTotal = productGrandSubtotal + productGrandCgst + productGrandSgst;
                    $body.append(`
                        <tr class="preview-grandtotal-row">
                            <td class="pe-3">Products Total</td>
                            <td class="text-end">${CURRENCY_SYMBOL + productsTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        </tr>
                    `);
                    count++;
                }

                // --- Other Items Section (unchanged logic) ---
                let otherSectionTotal = 0;
                let otherSectionRows = '';
                let hasOtherItems = false;

                $('#otherItemsTableBody tr').each(function() {
                    const result = buildPreviewRowData($(this), 'Other Item');
                    if (result) {
                        otherSectionRows += result.html;
                        // Add per-item tax rows
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
                    const price = parseFloat($(this).find('.price-ge').val()) || 0;
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
                    const priceIncludesTax = $row.find('.price-includes-tax-checkbox').is(':checked') ? 1 : 0;
                    const cgstPercent = parseFloat($row.attr('data-cgst-percent')) || 0;
                    const sgstPercent = parseFloat($row.attr('data-sgst-percent')) || 0;
                    const totalTaxPercent = cgstPercent + sgstPercent;

                    // Calculate Base and Tax
                    let unitBasePrice = 0;
                    let unitTaxAmount = 0;

                    if (priceIncludesTax === 1 && totalTaxPercent > 0) {
                        // Inclusive: Base = Price / (1 + Rate)
                        unitBasePrice = enteredPrice / (1 + totalTaxPercent / 100);
                        unitTaxAmount = enteredPrice - unitBasePrice;
                    } else {
                        // Exclusive (or no tax): Base = Price
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

                $('#totalItemsCount').text($('#itemsTableBody tr').length);
                $('#subtotalDisplay').text(totalBasePrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#discountValueDisplay').text('-' + CURRENCY_SYMBOL + discountValue.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                $('#additionalChargesTotalDisplay').text(additionalChargesTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#grandTotalDisplay').text(grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                updatePreview();
            }

            $(document).on('input', '.price-input', function() {
                const $row = $(this).closest('tr');
                // For product items, recalculate ge_price when price is manually changed
                if ($row.closest('tbody').attr('id') === 'itemsTableBody') {
                    const enteredPrice = parseFloat($(this).val()) || 0;
                    const originalGi = parseFloat($(this).data('gi')) || 0;
                    const cgst = parseFloat($row.attr('data-cgst-percent')) || 0;
                    const sgst = parseFloat($row.attr('data-sgst-percent')) || 0;
                    const totalTax = cgst + sgst;

                    if (Math.abs(enteredPrice - originalGi) > 0.001 && totalTax > 0) {
                        // Manual override: treat entered price as tax-inclusive, back-calculate base
                        const newGe = enteredPrice / (1 + totalTax / 100);
                        $row.find('.price-ge').val(newGe.toFixed(2));
                        $row.find('.price-gi').val(enteredPrice.toFixed(2));
                    } else {
                        // Price unchanged or no tax: restore original ge
                        $row.find('.price-ge').val($(this).data('ge'));
                        $row.find('.price-gi').val(enteredPrice.toFixed(2));
                    }
                }
                calculateRowTotal($row);
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
                    $type.css({'pointer-events': 'auto', 'background-color': '#fff'});
                    $(this).find('i').removeClass('fa-lock').addClass('fa-lock-open');
                } else {
                    $amount.prop('readonly', true).css('background-color', '#e9ecef');
                    $type.css({'pointer-events': 'none', 'background-color': '#e9ecef'});
                    $(this).find('i').removeClass('fa-lock-open').addClass('fa-lock');
                }
            });

            // Collect Amount on Delivery toggle
            $('#collect_on_delivery').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#collect_amount_wrapper').slideDown();
                    $('#amount_collected').prop('required', true);
                    $('#payment_received_hidden').val(1);
                } else {
                    $('#collect_amount_wrapper').slideUp();
                    $('#amount_collected').prop('required', false).val('');
                    $('#payment_received_hidden').val(0);
                }
            });

            // Utencil movement modal save
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
                    // For create screen, deduct before any send doesn't make domain sense.
                    Swal.fire('Not Allowed',
                        'Deduct (receive) operations are only available after order is created.',
                        'warning');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }

                // Merge into utencilMovements summary
                let existing = utencilMovements.find(m => m.id === utencilId);
                if (!existing) {
                    existing = {
                        id: utencilId,
                        name: utencilName,
                        sent: 0,
                        received: 0,
                        note: ''
                    };
                    utencilMovements.push(existing);
                }
                if (type === 'add') {
                    existing.sent += qty;
                }
                if (note) {
                    existing.note = note;
                }

                // Add hidden fields for backend (send only, same as existing behaviour)
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
                $('#modal_utencil_type').val('');
                $('#modal_utencil_note').val('');
            });

            function renderUtencilSummary() {
                const $body = $('#utencilSummaryBody');
                const $empty = $('#utencilSummaryEmptyRow');
                $body.find('tr').not($empty).remove();

                if (!utencilMovements.length) {
                    $empty.show();
                    return;
                }

                $empty.hide();
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

            // Sender Store Change -> Recalculate prices
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
                            $row.find('.price-input').val(parseFloat(response.price)
                                .toFixed(2));
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
                    // Re-enable disabled fields so they get submitted
                    $('#orderForm').find(':disabled').prop('disabled', false);
                    form.submit();
                }
            });

            // Pre-fill addresses based on Receiver Store
            $('#order_from_store').on('change', function() {
                const storeId = $(this).val();
                const orderType = $('#order_type').val();

                if (storeId && (orderType === 'company' || orderType === 'franchise' || orderType === 'dealer')) {
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

            // --- New Sections Preview & Price Fetching ---

            function fetchItemDetails($select, type) {
                const id = $select.val();
                const $row = $select.closest('tr');
                const $priceInput = $row.find('.price-input');
                const $pricingTypeInput = $row.find('.pricing-type-input');
                const $taxCheckbox = $row.find('.price-includes-tax-checkbox');

                if (!id) {
                    $priceInput.val('').prop('required', true);
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
                    id: id
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

                    // Prefill price_includes_tax checkbox from database
                    $taxCheckbox.prop('checked', response.price_includes_tax == 1);

                    // Always show price input — for as_per_actual, user enters the actual price
                    $priceInput.val(parseFloat(response.price).toFixed(2)).prop('required', true);
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
                calculateRowTotal($row);
            });
        });
    </script>
@endpush
