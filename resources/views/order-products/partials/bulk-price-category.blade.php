@php
    /**
     * @var \App\Models\OrderCategory $category
     * @var \Illuminate\Support\Collection|\App\Models\PricingTier[] $pricingTiers
     */
@endphp

<div class="category-card">
    <div class="category-header js-category-toggle">
        <h5>{{ $category->name }}</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-muted">
                {{ $category->products->count() }} products
            </span>
            <i class="bi bi-chevron-down toggle-icon"></i>
        </div>
    </div>
    <div class="category-body @if($iteration != 0) d-none @endif">
        @foreach($category->products as $product)
            <div class="product-block">
                <div class="product-title">
                    <div>
                        {{ $product->name }}
                        <span class="muted">({{ $product->sku }})</span>
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
                                    <th style="min-width: 220px;">Unit</th>
                                    <th class="text-start">Default Regular MRP</th>
                                    @foreach($pricingTiers as $tier)
                                        <th class="tier-header">{{ $tier->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->units as $unit)
                                    <tr>
                                        <td class="text-start">
                                            <strong>{{ $unit->unit->name ?? 'Unit #'.$unit->unit_id }}</strong>
                                        </td>
                                        <td class="text-start">
                                            <span class="muted">{{ $currencySymbol }} {{ number_format($unit->price, 2) }}</span>
                                        </td>
                                        @foreach($pricingTiers as $tier)
                                            @php
                                                $existing = $product->unitPriceTiers
                                                    ->where('pricing_tier_id', $tier->id)
                                                    ->where('product_unit_id', $unit->unit_id)
                                                    ->first();
                                            @endphp
                                            <td class="text-start">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">{{ $currencySymbol }}</span>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        class="form-control form-control-sm price-input text-start"
                                                        name="prices[{{ $product->id }}][{{ $unit->unit_id }}][{{ $tier->id }}]"
                                                        value="{{ $existing ? number_format($existing->amount, 2) : number_format($unit->price, 2) }}"
                                                        placeholder="0.00"
                                                        min="0"
                                                    >
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach

        @foreach($category->children as $child)
            @include('order-products.partials.bulk-price-category', [
                'category' => $child,
                'pricingTiers' => $pricingTiers
            ])
        @endforeach
    </div>
</div>

