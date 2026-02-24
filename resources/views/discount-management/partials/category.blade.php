@php
    /**
     * @var \App\Models\OrderCategory $category
     * @var \Illuminate\Support\Collection $existingDiscounts
     * @var int $iteration
     */
@endphp

<div class="category-card">
    <div class="category-header js-category-toggle">
        <h5>{{ $category->name }} <span class="text-muted" style="font-size: 12px;">(Category)</span></h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-muted">
                {{ $category->products->count() }} products
            </span>
            <i class="bi bi-chevron-{{ $iteration === 0 ? 'up' : 'down' }} toggle-icon"></i>
        </div>
    </div>
    <div class="category-body {{ $iteration === 0 ? '' : 'd-none' }}">
        @foreach($category->products as $product)
            <div class="product-block">
                <div class="product-title">
                    <div>
                        {{ $product->name }}
                        @if($product->sku)
                            <span class="muted">({{ $product->sku }})</span>
                        @endif
                    </div>
                    <span class="badge {{ $product->status ? 'bg-success' : 'bg-danger' }}">
                        {{ $product->status ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                @if($product->units->isEmpty())
                    <div class="text-muted small">No units configured for this product.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 units-table">
                            <thead>
                                <tr>
                                    <th style="min-width: 200px;">Unit</th>
                                    <th>Min Qty</th>
                                    <th>Max Qty</th>
                                    <th>Discount Type</th>
                                    <th>Discount Amount</th>
                                    <th>Price</th>
                                    <th>Price After Discount</th>
                                    <th class="action-col text-center">Action</th>
                                </tr>
                            </thead>
                            @foreach($product->units as $unit)
                                @php
                                    $key = $product->id . '::' . $unit->unit_id;
                                    $rows = $existingDiscounts->get($key) ?? collect();
                                    $basePrice = $basePriceMap[$product->id][$unit->unit_id] ?? 0;
                                @endphp
                                <tbody data-discount-body="{{ $product->id }}::{{ $unit->unit_id }}">
                                    <tr>
                                        <td colspan="8" class="bg-light fw-semibold">
                                            {{ $unit->unit->name ?? ('Unit #' . $unit->unit_id) }}
                                            <button type="button"
                                                    class="btn btn-primary btn-sm float-end btn-add-discount-row"
                                                    data-product-id="{{ $product->id }}"
                                                    data-unit-id="{{ $unit->unit_id }}"
                                                    data-mrp="{{ $basePrice }}">
                                                Add
                                            </button>
                                        </td>
                                    </tr>
                                    @if($rows->isEmpty())
                                        <tr class="no-discount-rules-yet">
                                            <td></td>
                                            <td colspan="7">
                                                <span class="muted">No discount rules yet. Click "Add" to create.</span>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach($rows as $rowIndex => $row)
                                            @php
                                                $mrp = $row->price_before_discount ?? $basePrice;
                                                $mrpAfter = $mrp;
                                                if ($row->discount_type == \App\Models\UnitDiscountTier::TYPE_PERCENTAGE) {
                                                    $mrpAfter = $mrp - (($mrp * $row->discount_amount) / 100);
                                                } else {
                                                    $mrpAfter = $mrp - $row->discount_amount;
                                                }
                                                if ($mrpAfter < 0) {
                                                    $mrpAfter = 0;
                                                }
                                            @endphp
                                            <tr class="discount-data-row">
                                                <td></td>
                                                <td>
                                                    <input type="number" min="1" class="form-control form-control-sm qty-input"
                                                        name="discounts[{{ $product->id }}][{{ $unit->unit_id }}][{{ $rowIndex }}][min_qty]"
                                                        value="{{ $row->min_qty }}" required>
                                                </td>
                                                <td>
                                                    <input type="number" min="1" class="form-control form-control-sm qty-input"
                                                        name="discounts[{{ $product->id }}][{{ $unit->unit_id }}][{{ $rowIndex }}][max_qty]"
                                                        value="{{ $row->max_qty }}">
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm discount-type-select"
                                                        name="discounts[{{ $product->id }}][{{ $unit->unit_id }}][{{ $rowIndex }}][discount_type]">
                                                        <option value="0" {{ $row->discount_type == 0 ? 'selected' : '' }}>Percentage (%)</option>
                                                        <option value="1" {{ $row->discount_type == 1 ? 'selected' : '' }}>Fixed Amount</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm discount-input"
                                                        name="discounts[{{ $product->id }}][{{ $unit->unit_id }}][{{ $rowIndex }}][discount_amount]"
                                                        value="{{ $row->discount_amount }}" required>
                                                </td>
                                                <td>
                                                    <span class="mrp-value" data-mrp="{{ $mrp }}">
                                                        {{ number_format($mrp, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="mrp-after">{{ number_format($mrpAfter, 2) }}</span>
                                                </td>
                                                <td class="action-col text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-discount-row">Remove</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>
        @endforeach

        @foreach($category->children as $childIndex => $child)
            @include('discount-management.partials.category', [
                'category' => $child,
                'existingDiscounts' => $existingDiscounts,
                'iteration' => $childIndex
            ])
        @endforeach
    </div>
</div>

