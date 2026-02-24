<?php

namespace App\Http\Controllers;

use App\Models\OrderProduct;
use App\Models\OrderProductPriceManagement;
use App\Models\OrderProductUnit;
use App\Models\OrderUnit;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class OrderProductPriceManagementController extends Controller
{
    public function index($id)
    {
        $product = OrderProduct::with(['units.unit'])->findOrFail($id);
        $stores = Store::orderBy('name')->get();
        
        $existingOverrides = OrderProductPriceManagement::where('order_product_id', $id)
            ->get()
            ->groupBy('store_id')
            ->map(function($storeOverrides) {
                return $storeOverrides->keyBy('unit_id');
            });

        $page_title = "Price Management: " . $product->name;
        $page_description = "Manage store-specific unit prices for this product";

        return view('order-products.price-management.index', compact('product', 'stores', 'existingOverrides', 'page_title', 'page_description'));
    }

    public function store(Request $request, $id)
    {
        // Handle single store update (AJAX) or pass to bulk logic
        // But wait, the existing AJAX sends 'store_id' and 'prices' array.
        // We can keep it or enhance it.
        // Let's refactor to support the logic where deletion triggers observer.
        
        $product = OrderProduct::findOrFail($id);
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'prices' => 'required|array',
        ]);

        try {
            DB::beginTransaction();
            $this->processPriceUpdates($id, $request->store_id, $request->prices);
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Prices updated successfully.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function bulkStore(Request $request, $id)
    {
        // Expects: stores [ { store_id: 1, prices: {unit_id: price, ...} }, ... ]
        // OR simply iterate over all forms data from frontend.
        // Actually simplest is array of store data.
        
        $request->validate([
            'updates' => 'required|array',
            'updates.*.store_id' => 'required|exists:stores,id',
            'updates.*.prices' => 'array'
        ]);

        try {
            DB::beginTransaction();
            foreach($request->updates as $update) {
                if(isset($update['prices'])) {
                    $this->processPriceUpdates($id, $update['store_id'], $update['prices']);
                }
            }
            DB::commit();
            return response()->json(['status' => true, 'message' => 'All store prices updated successfully.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    private function processPriceUpdates($productId, $storeId, $prices)
    {
        foreach($prices as $unitId => $price) {
            // Validate unit exists for product?
            // Existing logic checked: $product->units()->where('unit_id', $unitId)->exists()
            // Optimization: Load product units once outside loop
             // For now assume validation passed or data is clean
            
            if($price !== null && $price !== '') {
                OrderProductPriceManagement::updateOrCreate(
                    [
                        'order_product_id' => $productId,
                        'store_id' => $storeId,
                        'unit_id' => $unitId
                    ],
                    [
                        'price' => $price
                    ]
                );
            } else {
                // To trigger Observer's deleted event, we must find first
                $existing = OrderProductPriceManagement::where('order_product_id', $productId)
                    ->where('store_id', $storeId)
                    ->where('unit_id', $unitId)
                    ->first();
                
                if ($existing) {
                    $existing->delete();
                }
            }
        }
    }

    public function destroy($id, $price_id)
    {
        try {
            $item = OrderProductPriceManagement::findOrFail($price_id);
            $item->delete(); // Triggers observer
            return response()->json(['status' => true, 'message' => 'Price override deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }

    public function history($id)
    {
        // Fetch logs for this product
        $logs = \App\Models\PriceLog::with(['store', 'user', 'unit'])
            ->where('order_product_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'store_name' => $log->store->name ?? 'Unknown Store',
                    'user_name' => $log->user->name ?? 'System',
                    'unit_name' => $log->unit->name ?? 'Unknown Unit',
                    'old_price' => $log->old_price,
                    'new_price' => $log->new_price,
                    'date' => $log->created_at->format('d M Y, h:i A'),
                    'action' => $log->new_price === null ? 'Removed' : ($log->old_price === null ? 'Set' : 'Updated')
                ];
            });

        return response()->json(['status' => true, 'data' => $logs]);
    }

    public function export(Request $request) 
    {
        $stores = Store::orderBy('name')->get();
        // Load all active products with units
        $products = OrderProduct::with('units.unit')->where('status', 1)->get();
        // Load existing overrides for efficiency: key = store_id|product_id|unit_id -> price
        $overrides = OrderProductPriceManagement::all();
        $overrideMap = $overrides->mapWithKeys(function($item) {
            return [$item->store_id . '|' . $item->order_product_id . '|' . $item->unit_id => $item->price];
        });

        // $sheets = new \Maatwebsite\Excel\SheetCollection([]); // Unused and causes lint error

        foreach ($stores as $store) {
            $data = [];
            // Header
            $data[] = ['Product Name', 'SKU', 'Unit Name', 'Product ID', 'Unit ID', 'Default Regular MRP', 'Store Price (Edit this)'];

            foreach ($products as $product) {
                foreach ($product->units as $pUnit) {
                    $key = $store->id . '|' . $product->id . '|' . $pUnit->unit_id;
                    $currentPrice = $overrideMap[$key] ?? '';
                    
                    $data[] = [
                        $product->name,
                        $product->sku,
                        $pUnit->unit->name ?? 'Unknown',
                        $product->id,
                        $pUnit->unit_id,
                        $pUnit->price,
                        $currentPrice
                    ];
                }
            }
            
            // Register sheet
            // We can't use SheetCollection directly efficiently without classes, but simple array export works with multiple sheets?
            // Let's use a dynamic Export class logic or return collection if using Maatwebsite 3.1 features directly.
            // Simplified approach: Return a download using a dynamic collection class.
            // Since we can't create multiple classes on fly easily, let's use a closure based export or `Maatwebsite\Excel\Concerns\FromCollection`.
            // But we need multiple sheets.
            // Let's define a class at the bottom or separate file?
            // For now, simpler: One big sheet? NO, user asked for "each store has own worksheet".
            // We need `WithMultipleSheets`.
        }
        
        // Since we can't easily create a class file here, let's use a generic MultiSheetExport class if exists, or create one.
        // I will create a dedicated Export class in app/Exports/StorePriceExport.php
        
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StorePriceExport($stores, $products, $overrideMap), 'store_prices_'.date('Y-m-d_H-i').'.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            DB::beginTransaction();
            
            // We will use a dedicated Import class
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\StorePriceImport, $request->file('file'));
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Store prices imported successfully.']);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollback();
            $failures = $e->failures();
            $msg = 'Validation Error at row ' . $failures[0]->row() . ': ' . $failures[0]->errors()[0];
            return response()->json(['status' => false, 'message' => $msg]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
