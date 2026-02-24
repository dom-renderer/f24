<?php

namespace App\Http\Controllers;

use App\Models\OrderCategory;
use App\Models\OrderProductUnit;
use App\Models\OrderUnit;
use App\Models\PricingTier;
use App\Models\Setting;
use App\Models\UnitDiscountTier;
use App\Models\UnitPriceTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiscountManagementController extends Controller
{
    /**
     * Show the discount management screen.
     */
    public function index(Request $request)
    {
        $page_description = 'Manage quantity based discounts for product units, tier wise.';

        $tiers = PricingTier::where('status', true)
            ->orderBy('name')
            ->get();

        $selectedTierId = $request->get('tier_id');
        if (!$selectedTierId && $tiers->isNotEmpty()) {
            $selectedTierId = $tiers->first()->id;
        }

        $selectedTier = $selectedTierId
            ? $tiers->firstWhere('id', (int) $selectedTierId)
            : null;

        $page_title = 'Discount Management for Tier - ' . (PricingTier::where('id', request('tier_id')))->value('name');

        // Load categories tree with products and their units
        $categories = OrderCategory::with([
                'children',
                'products.units.unit',
            ])
            ->withCount('products')
            ->whereNull('parent_id')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->get();

        // Existing discount rules for selected tier
        $existingDiscounts = collect();
        $basePriceMap = [];

        if ($selectedTier) {
            $existingDiscounts = UnitDiscountTier::where('pricing_tier_id', $selectedTier->id)
                ->orderBy('product_id')
                ->orderBy('product_unit_id')
                ->orderBy('min_qty')
                ->get()
                ->groupBy(function ($row) {
                    return $row->product_id . '::' . $row->product_unit_id;
                });

            // Pre-compute base prices for this tier & all visible product units
            $productIds = [];
            $unitIdsPerProduct = [];

            foreach ($categories as $category) {
                foreach ($category->products as $product) {
                    $productIds[] = $product->id;
                    foreach ($product->units as $unit) {
                        $unitIdsPerProduct[$product->id][] = $unit->unit_id;
                    }
                }
            }

            $productIds = array_values(array_unique($productIds));

            if (!empty($productIds)) {
                // Tier-specific prices (unit_price_tiers)
                $tierPrices = UnitPriceTier::where('pricing_tier_id', $selectedTier->id)
                    ->whereIn('product_id', $productIds)
                    ->get();

                foreach ($tierPrices as $row) {
                    $basePriceMap[$row->product_id][$row->product_unit_id] = (float) $row->amount;
                }

                // Fallback to base product unit prices
                $unitPrices = OrderProductUnit::whereIn('order_product_id', $productIds)->get();
                foreach ($unitPrices as $unitRow) {
                    if (!isset($basePriceMap[$unitRow->order_product_id][$unitRow->unit_id])) {
                        $basePriceMap[$unitRow->order_product_id][$unitRow->unit_id] = (float) $unitRow->price;
                    }
                }
            }
        }

        $units = OrderUnit::orderBy('name')->pluck('name', 'id');

        $setting = Setting::first();
        $cgstPercentage = (float) ($setting->cgst_percentage ?? 0);
        $sgstPercentage = (float) ($setting->sgst_percentage ?? 0);
        $gstTotalPercentage = $cgstPercentage + $sgstPercentage;

        return view('discount-management.index', compact(
            'page_title',
            'page_description',
            'tiers',
            'selectedTier',
            'categories',
            'existingDiscounts',
            'units',
            'basePriceMap',
            'cgstPercentage',
            'sgstPercentage',
            'gstTotalPercentage'
        ));
    }

    /**
     * Persist discounts for a specific pricing tier.
     */
    public function store(Request $request)
    {
        $request->validate([
            'pricing_tier_id' => 'required|exists:pricing_tiers,id',
            'discounts' => 'nullable|array',
        ]);

        $tierId = (int) $request->pricing_tier_id;
        $discounts = $request->input('discounts', []);

        try {
            DB::beginTransaction();

            // Build a structure per product/unit to validate and then persist
            $byUnit = [];

            foreach ($discounts as $productId => $units) {
                foreach ($units as $unitId => $rows) {
                    $key = (int) $productId . '::' . (int) $unitId;

                    foreach ($rows as $row) {
                        $min = isset($row['min_qty']) ? (int) $row['min_qty'] : null;
                        $max = isset($row['max_qty']) && $row['max_qty'] !== '' ? (int) $row['max_qty'] : null;
                        $discountType = isset($row['discount_type']) ? (int) $row['discount_type'] : null;
                        $discountAmount = isset($row['discount_amount']) ? (float) $row['discount_amount'] : null;

                        // Skip completely empty rows
                        if ($min === null && $max === null && $discountAmount === null) {
                            continue;
                        }

                        if ($min === null || $min < 1) {
                            $min = 1;
                        }

                        if (!in_array($discountType, [UnitDiscountTier::TYPE_PERCENTAGE, UnitDiscountTier::TYPE_FIXED], true)) {
                            $discountType = UnitDiscountTier::TYPE_PERCENTAGE;
                        }

                        if ($discountAmount === null) {
                            $discountAmount = 0.0;
                        }

                        // Strict discount validation
                        if ($discountType === UnitDiscountTier::TYPE_PERCENTAGE) {
                            if ($discountAmount < 0 || $discountAmount > 100) {
                                throw ValidationException::withMessages([
                                    'discounts' => 'Percentage discount must be between 0 and 100.',
                                ]);
                            }
                        } else {
                            if ($discountAmount < 0) {
                                throw ValidationException::withMessages([
                                    'discounts' => 'Fixed discount cannot be negative.',
                                ]);
                            }
                        }

                        $byUnit[$key][] = [
                            'product_id' => (int) $productId,
                            'unit_id' => (int) $unitId,
                            'min_qty' => $min,
                            'max_qty' => $max,
                            'discount_type' => $discountType,
                            'discount_amount' => $discountAmount,
                        ];
                    }
                }
            }

            // Validate overlapping ranges and then persist
            UnitDiscountTier::where('pricing_tier_id', $tierId)->delete();

            foreach ($byUnit as $key => $rows) {
                // Sort by min qty, then max qty (null considered infinity)
                usort($rows, function ($a, $b) {
                    if ($a['min_qty'] === $b['min_qty']) {
                        $aMax = $a['max_qty'] ?? PHP_INT_MAX;
                        $bMax = $b['max_qty'] ?? PHP_INT_MAX;
                        return $aMax <=> $bMax;
                    }
                    return $a['min_qty'] <=> $b['min_qty'];
                });

                $prevMax = null;
                foreach ($rows as $index => $row) {
                    if ($row['max_qty'] !== null && $row['min_qty'] > $row['max_qty']) {
                        throw ValidationException::withMessages([
                            'discounts' => 'For product ' . $row['product_id'] . ' unit ' . $row['unit_id'] . ', Min Qty cannot be greater than Max Qty.',
                        ]);
                    }

                    if ($prevMax !== null) {
                        if ($row['min_qty'] <= $prevMax) {
                            throw ValidationException::withMessages([
                                'discounts' => 'Overlapping quantity ranges detected for product ' . $row['product_id'] . ' unit ' . $row['unit_id'] . '. Please adjust Min/Max quantities.',
                            ]);
                        }
                    }

                    $prevMax = $row['max_qty'] ?? PHP_INT_MAX;
                }

                // Determine base price once per unit (tier price → fallback to product unit price)
                [$productId, $unitId] = array_map('intval', explode('::', $key));
                $basePrice = $this->resolveBasePrice($tierId, $productId, $unitId);

                foreach ($rows as $row) {
                    UnitDiscountTier::create([
                        'pricing_tier_id' => $tierId,
                        'product_id' => $row['product_id'],
                        'product_unit_id' => $row['unit_id'],
                        'min_qty' => $row['min_qty'],
                        'max_qty' => $row['max_qty'],
                        'discount_type' => $row['discount_type'],
                        'discount_amount' => $row['discount_amount'],
                        'price_before_discount' => $basePrice,
                        'status' => true,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('discount-management.index', ['tier_id' => $tierId])
                ->with('success', 'Discount tiers saved successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('discount-management.index', ['tier_id' => $tierId])
                ->with('error', 'Failed to save discounts: ' . $e->getMessage());
        }
    }

    /**
     * Resolve base price for a single unit, preferring tier-specific price.
     */
    protected function resolveBasePrice(int $tierId, int $productId, int $unitId): float
    {
        static $cache = [];
        $cacheKey = $tierId . '|' . $productId . '|' . $unitId;

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $price = UnitPriceTier::where('pricing_tier_id', $tierId)
            ->where('product_id', $productId)
            ->where('product_unit_id', $unitId)
            ->value('amount');

        if ($price === null) {
            $price = OrderProductUnit::where('order_product_id', $productId)
                ->where('unit_id', $unitId)
                ->value('price');
        }

        $cache[$cacheKey] = (float) ($price ?? 0);

        return $cache[$cacheKey];
    }
}

