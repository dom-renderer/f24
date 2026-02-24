@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
    :root {
        --brand-primary: #012440;
        --brand-success: #10b981;
        --brand-surface: #f8fafc;
        --brand-border: #e2e8f0;
        --text-main: #1e293b;
    }

    body {
        background-color: #f1f5f9;
        color: var(--text-main);
    }

    .price-management-container h4 {
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -0.02em;
    }

    /* Accordion Styling */
    .store-accordion .accordion-item {
        border: 1px solid var(--brand-border);
        margin-bottom: 1.25rem;
        border-radius: 12px !important;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border-left: 4px solid var(--brand-primary); /* Cool accent strip */
    }

    .store-accordion .accordion-button {
        background-color: #fff;
        font-weight: 600;
        padding: 1.25rem;
        color: var(--text-main);
    }

    .store-accordion .accordion-button:not(.collapsed) {
        background-color: #fff;
        color: var(--brand-primary);
        box-shadow: none;
    }

    /* Headers and Rows */
    .unit-header {
        display: flex;
        padding: 0.75rem 1.25rem;
        background-color: #eef2ff; /* Very light indigo tint */
        color: #012440;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-weight: 700;
        border-bottom: 1px solid #e0e7ff;
    }

    .unit-row {
        display: flex;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--brand-border);
        background: #fff;
        transition: all 0.2s ease;
    }

    .unit-row:hover {
        background-color: var(--brand-surface);
    }

    /* Column Sizing */
    .col-unit { flex: 1; }
    .col-default { flex: 0 0 140px; text-align: center; }
    .col-override { flex: 0 0 180px; }
    .col-updated { flex: 0 0 160px; font-size: 0.75rem; color: #94a3b8; text-align: right; }

    .unit-name {
        font-weight: 600;
        display: flex;
        align-items: center;
    }

    .unit-icon-box {
        width: 32px;
        height: 32px;
        background: #f1f5f9;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #64748b;
    }

    /* Pricing Badges & Inputs */
    .price-badge {
        background: #f1f5f9;
        color: #475569;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.85rem;
        border: 1px solid #e2e8f0;
    }

    .input-group-text {
        background-color: #fff;
        border-right: none;
        color: var(--brand-success);
        font-weight: bold;
    }

    .override-input {
        border-left: none;
        font-weight: 600;
        color: var(--brand-success);
    }

    .override-input:focus {
        border-color: #ced4da;
        box-shadow: none;
    }

    /* Button Styling */
    .btn-save-store {
        background: var(--brand-primary);
        border: none;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        transition: transform 0.1s active;
    }

    .btn-save-store:hover {
        background: #012440;
        box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
    }

    .store-icon {
        color: var(--brand-primary);
        background: #eef2ff;
        padding: 8px;
        border-radius: 8px;
        margin-right: 12px;
    }

</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h4>{{ $page_title }}</h4>
            <p class="text-muted small mb-0">{{ $page_description }}</p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-outline-primary btn-sm me-2" id="btnViewHistory">
                <i class="fas fa-history me-1"></i> View History
            </button>
            <a href="{{ route('order-products.index') }}" class="btn btn-white btn-sm border shadow-sm bg-white">
                <i class="fas fa-arrow-left me-1"></i> Back to Products
            </a>
        </div>
    </div>

    @include('layouts.partials.messages')

    @if($product->units->isEmpty())
        <div class="card border-0 shadow-sm text-center p-5">
            <div class="card-body">
                <i class="fas fa-box-open fa-3x mb-3 text-muted opacity-50"></i>
                <h5>No Units Assigned</h5>
                <p class="text-muted">Add units to this product to manage store pricing.</p>
            </div>
        </div>
    @else
        <div class="accordion store-accordion mb-4" id="storeAccordion">
            @foreach($stores as $index => $store)
                <div class="accordion-item shadow-sm">
                    <h2 class="accordion-header" id="heading{{ $store->id }}">
                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $store->id }}">
                            <i class="fas fa-store store-icon"></i>
                            {{ $store->name }}
                        </button>
                    </h2>
                    <div id="collapse{{ $store->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#storeAccordion">
                        <div class="accordion-body p-0">
                            <form class="store-price-form" data-store-id="{{ $store->id }}" data-product-id="{{ $product->id }}">
                                @csrf
                                <input type="hidden" name="store_id" value="{{ $store->id }}">
                                
                                <div class="unit-header">
                                    <div class="col-unit">Unit Information</div>
                                    <div class="col-default">Default Price</div>
                                    <div class="col-override">Override Price</div>
                                    <div class="col-updated">Last Updated</div>
                                </div>
                                
                                @foreach($product->units as $productUnit)
                                    @php
                                        $override = isset($existingOverrides[$store->id]) ? ($existingOverrides[$store->id][$productUnit->unit_id] ?? null) : null;
                                        $overridePrice = $override ? $override->price : '';
                                    @endphp
                                    <div class="unit-row">
                                        <div class="col-unit">
                                            <div class="unit-name">
                                                <div class="unit-icon-box"><i class="fas fa-weight-hanging small"></i></div>
                                                {{ $productUnit->unit->name }}
                                            </div>
                                        </div>
                                        <div class="col-default">
                                            <span class="price-badge">{{ Helper::defaultCurrencySymbol() }}{{ number_format($productUnit->price, 2) }}</span>
                                        </div>
                                        <div class="col-override">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">{{ Helper::defaultCurrencySymbol() }}</span>
                                                <input type="number" step="0.01" min="0" class="form-control override-input" name="prices[{{ $productUnit->unit_id }}]" value="{{ $overridePrice }}" placeholder="Enter override...">
                                            </div>
                                        </div>
                                        <div class="col-updated">
                                            @if($override)
                                                <i class="fas fa-clock-rotate-left me-1 opacity-50"></i> {{ date('d M Y, H:i', strtotime($override->updated_at ?: $override->created_at)) }}
                                            @else
                                                <span class="opacity-25">-</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                
                                <div class="p-3 bg-white text-end">
                                    <button type="submit" class="btn btn-primary btn-save-store" data-tta="Save Prices for {{ $store->name }}">
                                        <i class="fas fa-save me-1"></i> Save Prices for {{ $store->name }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Bulk Save Button -->
        <div class="card border-0 shadow-sm p-4 text-center">
            <button class="btn btn-primary btn-lg" id="btnSaveAll">
                <i class="fas fa-save me-2"></i> Update All Stores Price
            </button>
        </div>
    @endif
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history me-2 text-primary"></i> Price Modification History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="historyLoading" class="text-center p-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="mt-2 text-muted">Loading history...</p>
                </div>
                <div id="historyList" class="timeline p-4"></div>
                <div id="historyEmpty" class="text-center p-5 d-none">
                    <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No history found for this product.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline-item { position: relative; padding-left: 2rem; border-left: 2px solid #e2e8f0; margin-bottom: 1.5rem; }
    .timeline-item::before { content: ''; position: absolute; left: -9px; top: 0; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 2px solid var(--brand-primary); }
    .timeline-date { font-size: 0.8rem; color: #64748b; margin-bottom: 0.2rem; }
    .timeline-content { background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; }
</style>
@endsection

@push('js')
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" }
    });

    $('.store-price-form').each(function() {
        $(this).validate({
            submitHandler: function(form) {
                submitStoreForm($(form));
                return false;
            }
        });
    });

    function submitStoreForm($form) {
        var productId = $form.data('product-id');
        var $btn = $form.find('button[type=submit]');
        
        return $.ajax({
            url: '/order-products/' + productId + '/price-management',
            type: 'POST',
            data: $form.serialize(),
            beforeSend: function() {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
            },
            success: function(response) {
                if(response.status) {
                    Swal.fire({ icon: 'success', title: 'Success!', text: response.message, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error!', text: response.message });
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Saved');
                setTimeout(function () {
                    $btn.html('<i class="fas fa-save me-1"></i> ' + $btn.data('tta'));
                }, 2000);
            }
        });
    }

    $('#btnSaveAll').click(function() {
        var updates = [];
        $('.store-price-form').each(function() {
            var $form = $(this);
            var prices = {};
            $form.find('input[name^="prices"]').each(function() {
                var name = $(this).attr('name');
                var val = $(this).val();
                var unitId = name.match(/\[(\d+)\]/)[1];
                prices[unitId] = val;
            });
            updates.push({
                store_id: $form.data('store-id'),
                prices: prices
            });
        });

        if(updates.length === 0) return;

        var productId = $('.store-price-form').first().data('product-id');
        var $btn = $(this);

        $.ajax({
            url: '/order-products/' + productId + '/price-management/bulk-store',
            type: 'POST',
            data: { updates: updates },
            beforeSend: function() {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Updating All...');
            },
            success: function(response) {
                Swal.fire({ icon: 'success', title: 'Done', text: response.message });
            },
            error: function(err) {
                Swal.fire({ icon: 'error', title: 'Error', text: err.responseJSON?.message || 'Failed' });
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Update All Stores Price');
            }
        });
    });

    // View History
    $('#btnViewHistory').click(function() {
        var productId = $('.store-price-form').first().data('product-id');
        $('#historyModal').modal('show');
        $('#historyList').empty();
        $('#historyLoading').show();
        $('#historyEmpty').addClass('d-none');

        $.ajax({
            url: '/order-products/' + productId + '/price-management/history',
            type: 'GET',
            success: function(response) {
                $('#historyLoading').hide();
                if(response.data.length > 0) {
                    var html = '';
                    response.data.forEach(function(log) {
                        html += `
                            <div class="timeline-item">
                                <div class="timeline-date">${log.date}</div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between">
                                        <strong>${log.store_name}</strong>
                                        <span class="badge bg-secondary">${log.action}</span>
                                    </div>
                                    <div class="small text-muted mb-2">by ${log.user_name}</div>
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="d-block text-uppercase text-secondary" style="font-size:0.65rem">Product/Unit</small>
                                            ${log.unit_name}
                                        </div>
                                        <div class="col-6 text-end">
                                             <small class="d-block text-uppercase text-secondary" style="font-size:0.65rem">Price Change</small>
                                             ${log.old_price ? '<span class="text-danger text-decoration-line-through me-2">' + log.old_price + '</span>' : ''}
                                             ${log.new_price ? '<span class="text-success fw-bold">' + log.new_price + '</span>' : '<span class="text-muted italic">Removed</span>'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#historyList').html(html);
                } else {
                    $('#historyEmpty').removeClass('d-none');
                }
            },
            error: function() {
                $('#historyLoading').hide();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load history' });
            }
        });
    });
});
</script>
@endpush