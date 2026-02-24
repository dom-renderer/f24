@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}" />
<style>
    .category-card {
        border: 1px solid #e3e6f0;
        border-radius: 6px;
        margin-bottom: 12px;
        background: #fff;
    }
    .category-header {
        padding: 10px 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8f9fc;
    }
    .category-header h5 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
    }
    .product-block {
        border-top: 1px solid #f1f1f1;
        padding: 12px 16px;
    }
    .product-title {
        font-weight: 600;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .units-table th,
    .units-table td {
        vertical-align: middle;
        font-size: 13px;
        white-space: nowrap;
    }
    .units-table thead th {
        background: #f8f9fa;
    }
    .discount-input {
        max-width: 110px;
    }
    .qty-input {
        max-width: 80px;
    }
    .scroll-container {
        max-height: 600px;
        overflow: auto;
        border: 1px solid #e3e6f0;
        border-radius: 6px;
        background: #fff;
    }
    .muted {
        color: #6c757d;
        font-size: 12px;
    }
    .action-col {
        width: 90px;
    }
</style>
@endpush

@section('content')
<div class="bg-light p-4 rounded">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1">{{ $page_title }}</h1>
            <div class="lead mb-0">{{ $page_description }}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- <form method="GET" action="{{ route('discount-management.index') }}" class="d-flex align-items-center gap-2">
                <select name="tier_id" id="tier_id" class="form-select form-select-sm select2" style="min-width: 220px;">
                    @foreach($tiers as $tier)
                        <option value="{{ $tier->id }}" {{ $selectedTier && $selectedTier->id === $tier->id ? 'selected' : '' }}>
                            {{ $tier->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Load Tier
                </button>
            </form> --}}
            <a href="{{ route('order-products.index') }}" class="btn btn-outline-secondary btn-sm">
                Back to Products
            </a>
        </div>
    </div>

    <div class="mb-2">
        @include('layouts.partials.messages')
    </div>

    @if(!$selectedTier)
        <div class="alert alert-info">
            Please create at least one pricing tier first from <strong>Pricing Tiers</strong> to configure discounts.
        </div>
    @else
        <form method="POST" action="{{ route('discount-management.store') }}" id="discountForm">
            @csrf
            <input type="hidden" name="pricing_tier_id" value="{{ $selectedTier->id }}">

            <button type="submit" class="btn btn-primary float-end">
                <i class="bi bi-save me-1"></i> Save Discounts
            </button>            

            <div class="scroll-container" style="display: inline;position:relative;top:25px;">
                @forelse($categories as $index => $category)
                    @include('discount-management.partials.category', [
                        'category' => $category,
                        'existingDiscounts' => $existingDiscounts,
                        'iteration' => $index
                    ])
                @empty
                    <div class="p-3 text-center text-muted">
                        No categories or products found. Please create products first.
                    </div>
                @endforelse
            </div>

            <div class="mt-3">
                <p class="muted mb-1">
                    If the <strong>Max Qty</strong> of the last row for a unit is left blank, it will be treated as infinity.
                    That rule will apply when ordered quantity exceeds all previous ranges.
                </p>
                <div class="text-end mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Discounts
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && $('.select2').length) {
            $('.select2').select2();
        }

        document.querySelectorAll('.js-category-toggle').forEach(function (header) {
            header.addEventListener('click', function () {
                var body = this.nextElementSibling;
                if (!body) return;
                body.classList.toggle('d-none');
                var icon = this.querySelector('.toggle-icon');
                if (icon) {
                    icon.classList.toggle('bi-chevron-down');
                    icon.classList.toggle('bi-chevron-up');
                }
            });
        });

        var $discountForm = window.jQuery ? $('#discountForm') : null;

        // jQuery Validate: attach rules + custom overlap validation
        if ($discountForm && $.fn.validate) {
            $.validator.addMethod('maxQtyGteMin', function(value, element) {
                if (value === '') return true;
                var minVal = parseInt($(element).closest('tr').find('.qty-input').first().val(), 10) || 0;
                return parseInt(value, 10) >= minVal;
            }, 'Max Qty must be greater than or equal to Min Qty');

            $discountForm.validate({
                ignore: [],
                errorElement: 'span',
                errorClass: 'text-danger small',
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                },
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                },
                submitHandler: function(form) {
                    // Ensure at least one rule exists overall
                    if (document.querySelectorAll('tr.discount-data-row').length === 0) {
                        Swal.fire('Validation', 'Please add at least one discount rule before saving.', 'warning');
                        return false;
                    }

                    // Clear previous overlap errors
                    $('.overlap-error').remove();
                    $('.qty-input').removeClass('is-invalid');

                    if (!checkOverlapPerUnit()) {
                        // Prevent submit if overlaps detected
                        return false;
                    }

                    form.submit();
                }
            });

            function attachValidationToRow($row) {
                if (!$row || !$row.length) return;

                var $min = $row.find('.qty-input').eq(0);
                var $max = $row.find('.qty-input').eq(1);
                var $amount = $row.find('.discount-input');

                if ($min.length) {
                    $min.rules('add', {
                        required: true,
                        number: true,
                        min: 1,
                        messages: {
                            required: 'Min Qty is required',
                            min: 'Min Qty must be at least 1'
                        }
                    });
                }

                if ($max.length) {
                    $max.rules('add', {
                        number: true,
                        maxQtyGteMin: true,
                        messages: {
                            maxQtyGteMin: 'Max Qty must be ≥ Min Qty'
                        }
                    });
                }

                if ($amount.length) {
                    $amount.rules('add', {
                        required: true,
                        number: true,
                        min: 0,
                        messages: {
                            required: 'Discount Amount is required',
                            min: 'Discount Amount cannot be negative'
                        }
                    });
                }
            }

            // Overlap validation per product/unit
            function checkOverlapPerUnit() {
                var ranges = {};
                var hasError = false;

                $('tr.discount-data-row').each(function() {
                    var $row = $(this);
                    var $tbody = $row.closest('tbody');
                    var key = $tbody.data('discount-body'); // "productId::unitId"
                    if (!key) return;

                    var $mins = $row.find('.qty-input');
                    var minVal = parseInt($mins.eq(0).val(), 10);
                    var maxStr = $mins.eq(1).val();
                    var maxVal = maxStr === '' ? null : parseInt(maxStr, 10);

                    if (isNaN(minVal)) return;

                    if (!ranges[key]) {
                        ranges[key] = [];
                    }

                    ranges[key].push({
                        min: minVal,
                        max: maxVal,
                        row: $row
                    });
                });

                Object.keys(ranges).forEach(function(key) {
                    var list = ranges[key];
                    list.sort(function(a, b) {
                        if (a.min === b.min) {
                            var aMax = a.max === null ? Number.MAX_SAFE_INTEGER : a.max;
                            var bMax = b.max === null ? Number.MAX_SAFE_INTEGER : b.max;
                            return aMax - bMax;
                        }
                        return a.min - b.min;
                    });

                    var prevMax = null;
                    list.forEach(function(entry) {
                        var $minInput = entry.row.find('.qty-input').eq(0);
                        var $maxInput = entry.row.find('.qty-input').eq(1);

                        if (entry.max !== null && entry.min > entry.max) {
                            hasError = true;
                            $maxInput.addClass('is-invalid');
                            $('<span class="text-danger small overlap-error">Max Qty must be ≥ Min Qty</span>')
                                .insertAfter($maxInput);
                        }

                        if (prevMax !== null) {
                            var effPrev = prevMax;
                            var effMin = entry.min;
                            if (effMin <= effPrev) {
                                hasError = true;
                                $minInput.addClass('is-invalid');
                                $('<span class="text-danger small overlap-error">Ranges for this unit must not overlap.</span>')
                                    .insertAfter($minInput);
                            }
                        }

                        prevMax = entry.max === null ? Number.MAX_SAFE_INTEGER : entry.max;
                    });
                });

                return !hasError;
            }

            // Apply to server-rendered rows on load
            $('.discount-data-row').each(function() {
                attachValidationToRow($(this));
            });

            // Expose helper to use after adding new rows
            window.__attachDiscountRowValidation = attachValidationToRow;
        }

        // Add discount row for a specific product/unit
        document.addEventListener('click', function (e) {
            if (!e.target.closest || !e.target.closest('.btn-add-discount-row')) {
                return;
            }
            e.preventDefault();
            var btn = e.target.closest('.btn-add-discount-row');
            var productId = btn.getAttribute('data-product-id');
            var unitId = btn.getAttribute('data-unit-id');
            var mrp = parseFloat(btn.getAttribute('data-mrp') || '0');
            var tbody = document.querySelector(
                'tbody[data-discount-body="' + productId + '::' + unitId + '"]'
            );

            if (!tbody) return;

            // Remove "no discount rules yet" row for this unit if present
            var placeholder = tbody.querySelector('.no-discount-rules-yet');
            if (placeholder) {
                placeholder.parentNode.removeChild(placeholder);
            }

            var index = tbody.querySelectorAll('tr.discount-data-row').length;
            var rowHtml = buildDiscountRow(productId, unitId, index, {}, mrp);
            tbody.insertAdjacentHTML('beforeend', rowHtml);

            if (window.__attachDiscountRowValidation && window.jQuery) {
                var $newRow = $(tbody).find('tr.discount-data-row').last();
                window.__attachDiscountRowValidation($newRow);
            }
        });

        // Remove discount row and restore placeholder if needed
        document.addEventListener('click', function (e) {
            if (!e.target.closest || !e.target.closest('.btn-remove-discount-row')) {
                return;
            }
            e.preventDefault();
            var row = e.target.closest('tr.discount-data-row');
            if (!row) return;
            var tbody = row.closest('tbody');
            row.parentNode.removeChild(row);

            if (!tbody) return;

            var remaining = tbody.querySelectorAll('tr.discount-data-row').length;
            if (remaining === 0 && !tbody.querySelector('.no-discount-rules-yet')) {
                var placeholderHtml = `
                    <tr class="no-discount-rules-yet">
                        <td></td>
                        <td colspan="7">
                            <span class="muted">No discount rules yet. Click "Add" to create.</span>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', placeholderHtml);
            }
        });

        // Live "Price After" updates
        document.addEventListener('input', function(e) {
            var target = e.target;
            if (!target.closest) return;
            if (!target.classList.contains('discount-input') && !target.classList.contains('discount-type-select')) {
                return;
            }
            recomputePriceAfter(target.closest('tr.discount-data-row'), false);
        });

        document.addEventListener('change', function(e) {
            var target = e.target;
            if (!target.closest) return;
            if (!target.classList.contains('discount-input') && !target.classList.contains('discount-type-select')) {
                return;
            }
            recomputePriceAfter(target.closest('tr.discount-data-row'), true);
        });

        function recomputePriceAfter(row, normalize) {
            if (!row) return;

            var typeSelect = row.querySelector('.discount-type-select');
            var amountInput = row.querySelector('.discount-input');
            var mrpEl = row.querySelector('.mrp-value');
            var mrpAfterEl = row.querySelector('.mrp-after');

            if (!typeSelect || !amountInput || !mrpEl || !mrpAfterEl) {
                return;
            }

            var mrp = parseFloat(mrpEl.getAttribute('data-mrp') || '0') || 0;

            var type = parseInt(typeSelect.value || '0', 10);
            var rawVal = amountInput.value;
            var amount = parseFloat(rawVal);

            if (rawVal === '' || isNaN(amount)) {
                // If field is being cleared, don't force 0.00; just show base MRP
                mrpAfterEl.textContent = mrp.toFixed(2);
                if (normalize) {
                    amountInput.value = '';
                }
                return;
            }

            if (normalize) {
                if (type === 0) {
                    // Percentage: clamp 0-100
                    if (amount < 0) amount = 0;
                    if (amount > 100) amount = 100;
                } else {
                    // Fixed: clamp >= 0
                    if (amount < 0) amount = 0;
                }
                amountInput.value = amount.toFixed(2);
            }

            var after = mrp;
            if (type === 0) {
                after = mrp - (mrp * (amount / 100));
            } else {
                after = mrp - amount;
            }

            if (after < 0) {
                after = 0;
            }

            mrpAfterEl.textContent = after.toFixed(2);
        }

        function buildDiscountRow(productId, unitId, index, data, mrp) {
            var minQty = data.min_qty || '';
            var maxQty = data.max_qty || '';
            var type = typeof data.discount_type !== 'undefined' ? data.discount_type : 0;
            var amount = typeof data.discount_amount !== 'undefined' ? data.discount_amount : '';
            var effectiveMrp = typeof data.price_before_discount !== 'undefined'
                ? data.price_before_discount
                : (typeof mrp !== 'undefined' ? mrp : 0);

            return `
                <tr class="discount-data-row">
                    <td></td>
                    <td>
                        <input type="number" min="1" class="form-control form-control-sm qty-input"
                            name="discounts[${productId}][${unitId}][${index}][min_qty]"
                            value="${minQty}" required>
                    </td>
                    <td>
                        <input type="number" min="1" class="form-control form-control-sm qty-input"
                            name="discounts[${productId}][${unitId}][${index}][max_qty]"
                            value="${maxQty}">
                    </td>
                    <td>
                        <select class="form-select form-select-sm discount-type-select"
                            name="discounts[${productId}][${unitId}][${index}][discount_type]">
                            <option value="0" ${parseInt(type, 10) === 0 ? 'selected' : ''}>Percentage (%)</option>
                            <option value="1" ${parseInt(type, 10) === 1 ? 'selected' : ''}>Fixed Amount</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm discount-input"
                            name="discounts[${productId}][${unitId}][${index}][discount_amount]"
                            value="${amount}" required>
                    </td>
                    <td>
                        <span class="mrp-value" data-mrp="${Number(effectiveMrp).toFixed(2)}">
                            ${Number(effectiveMrp).toFixed(2)}
                        </span>
                    </td>
                    <td>
                        <span class="mrp-after">0.00</span>
                    </td>
                    <td class="action-col text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-discount-row">Remove</button>
                    </td>
                </tr>
            `;
        }
    });
</script>
@endpush

