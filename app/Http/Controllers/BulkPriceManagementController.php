<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\OrderCategory;
use App\Models\OrderProduct;
use App\Models\OrderUnit;
use App\Models\PricingTier;
use App\Models\UnitPriceTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BulkPriceExport;
use App\Imports\BulkPriceImport;

class BulkPriceManagementController extends Controller
{
    /**
     * Display the bulk price management screen.
     */
    public function index(Request $request)
    {
        $page_title = 'Bulk Price Management';
        $page_description = 'Manage product unit prices across all pricing tiers from a single screen.';

        $currencySymbol = Helper::defaultCurrencySymbol();

        // Load active pricing tiers
        $pricingTiers = PricingTier::where('status', true)
            ->orderBy('id')
            ->get();

        // Load categories tree with products and their units + unit price tiers
        $categories = OrderCategory::with([
                'children',
                'products.units.unit',
                'products.unitPriceTiers',
            ])
            ->withCount('products')
            ->whereNull('parent_id')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->get();

        // Also provide a flat list of units if needed later
        $units = OrderUnit::orderBy('name')->pluck('name', 'id');

        return view('order-products.bulk-price-management', compact(
            'page_title',
            'page_description',
            'pricingTiers',
            'categories',
            'units',
            'currencySymbol'
        ));
    }

    /**
     * Store bulk price updates for all products/units/tiers.
     */
    public function store(Request $request)
    {
        $request->validate([
            'prices' => 'nullable|array',
        ]);

        $prices = $request->input('prices', []);

        try {
            DB::beginTransaction();

            foreach ($prices as $productId => $productUnits) {
                foreach ($productUnits as $unitId => $tiers) {
                    foreach ($tiers as $tierId => $amount) {
                        $amount = trim((string) $amount);

                        $query = UnitPriceTier::where('product_id', $productId)
                            ->where('product_unit_id', $unitId)
                            ->where('pricing_tier_id', $tierId);

                        if ($amount !== '') {
                            UnitPriceTier::updateOrCreate(
                                [
                                    'product_id' => $productId,
                                    'product_unit_id' => $unitId,
                                    'pricing_tier_id' => $tierId,
                                ],
                                [
                                    'amount' => (float) $amount,
                                    'status' => true,
                                ]
                            );
                        } else {
                            $existing = $query->first();
                            if ($existing) {
                                $existing->delete();
                            }
                        }
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('bulk-price-management.index')
                ->with('success', 'Bulk prices updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('bulk-price-management.index')
                ->with('error', 'Something went wrong while saving prices: ' . $e->getMessage());
        }
    }

    /**
     * Export bulk price data to Excel.
     */
    public function export()
    {
        $pricingTiers = PricingTier::where('status', true)
            ->orderBy('id')
            ->get();

        $categories = OrderCategory::with([
                'children',
                'products.units.unit',
                'products.unitPriceTiers',
            ])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        [$headings, $rows] = BulkPriceExport::buildData($categories, $pricingTiers);

        $fileName = 'bulk_price_management_' . date('Y-m-d_H-i') . '.xlsx';

        return Excel::download(new BulkPriceExport($rows, $headings), $fileName);
    }

    /**
     * Import bulk prices from Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $pricingTiers = PricingTier::where('status', true)->get();

        try {
            DB::beginTransaction();

            $import = new BulkPriceImport($pricingTiers);
            Excel::import($import, $request->file('file'));

            DB::commit();

            $stats = $import->getStats();

            return response()->json([
                'status' => true,
                'message' => 'Bulk prices imported successfully.',
                'stats' => $stats,
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack();
            $failures = $e->failures();
            $msg = 'Validation error at row ' . $failures[0]->row() . ': ' . $failures[0]->errors()[0];

            return response()->json([
                'status' => false,
                'message' => $msg,
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}

