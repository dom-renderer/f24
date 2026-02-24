<?php

namespace App\Http\Controllers;

use App\Models\OrderProduct;
use App\Models\OrderProductImage;
use App\Models\OrderProductUnit;
use App\Models\UnitPriceTier;
use App\Models\PricingTier;
use App\Models\OrderCategory;
use App\Models\OrderUnit;
use App\Models\TaxSlab;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class OrderProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        if ($request->has('reset')) {
            session()->forget('order_product_category_filter');
            return redirect()->route('order-products.index');
        }

        if ($request->has('category_id')) {
            session()->put('order_product_category_filter', $request->category_id);
        }

        $categories = OrderCategory::withCount('products')
                    ->orderByDesc('products_count')
                    ->pluck('name', 'id');
        $selectedCategory = session('order_product_category_filter');

        $page_title = "Products";
        $page_description = "Manage products here";

        return view('order-products.index', compact('page_title', 'page_description', 'categories', 'selectedCategory'));
    }

    public function ajax(Request $request)
    {
        $query = OrderProduct::query()->with(['category', 'units']);

        if (session()->has('order_product_category_filter')) {
            $query->where('category_id', session('order_product_category_filter'));
        }

        return datatables()
            ->eloquent($query)
            ->addColumn('price', function($row){
                if($row->units->count() > 0){
                    $min = $row->units->min('price');
                    $max = $row->units->max('price');
                    if($min == $max){
                        return number_format($min, 2);
                    }
                    return number_format($min, 2) . ' - ' . number_format($max, 2);
                }
                return 'N/A';
            })
            ->addColumn('status', function($row){
                return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function($row){
                $action = '';
                if(auth()->user()->can('order-products.edit')){
                    $action .= '<a href="'.route('order-products.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
                }
                if(auth()->user()->can('order-products.destroy')){
                    $action .= '<form method="POST" action="'.route("order-products.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
                }
                return $action;
            })
            ->rawColumns(['status', 'action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        $page_title = "Create Product";
        $page_description = "Add a new product";
        
        $categories = OrderCategory::pluck('name', 'id');
        $units = OrderUnit::pluck('name', 'id');
        $taxSlabs = TaxSlab::where('status', 1)->pluck('name', 'id');

        return view('order-products.create', compact('page_title', 'page_description', 'categories', 'units', 'taxSlabs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'sku' => 'required|unique:order_products,sku,NULL,id,deleted_at,NULL',
            'hsn_code' => 'required',
            'category_id' => 'required',
            'tax_slab_id' => 'required',
            'status' => 'required|boolean',
            'description' => 'nullable',
            'document' => 'required|array|min:1',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required',
            'units.*.price' => 'required|numeric|min:0',
            'units.*.status' => 'required|boolean',
            'pricing_tiers' => 'nullable|array',
            'pricing_tiers.*.name' => 'required|string',
            'pricing_tiers.*.units' => 'required|array',
            'pricing_tiers.*.units.*.unit_id' => 'required',
            'pricing_tiers.*.units.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $product = OrderProduct::create([
                'name' => $request->name,
                'sku' => $request->sku,
                'hsn_code' => $request->hsn_code,
                'category_id' => $request->category_id,
                'tax_slab_id' => $request->tax_slab_id,
                'status' => boolval($request->status),
                'description' => $request->description,
            ]);

            foreach($request->units as $unitData){
                OrderProductUnit::create([
                    'order_product_id' => $product->id,
                    'unit_id' => $unitData['unit_id'],
                    'price' => $unitData['price'],
                    'status' => $unitData['status'],
                ]);
            }

            if($request->has('pricing_tiers')){
                $globalTiers = PricingTier::all();
                foreach($request->pricing_tiers as $tierIndex => $tierData){
                    $globalTier = $globalTiers->get($tierIndex);
                    if($globalTier && isset($tierData['units']) && is_array($tierData['units'])){
                        foreach($tierData['units'] as $tierUnitData){
                            if(!empty($tierUnitData['unit_id']) && isset($tierUnitData['price'])){
                                UnitPriceTier::create([
                                    'pricing_tier_id' => $globalTier->id,
                                    'product_id' => $product->id,
                                    'product_unit_id' => $tierUnitData['unit_id'],
                                    'amount' => $tierUnitData['price'],
                                    'status' => 1
                                ]);
                            }
                        }
                    }
                }
            }

            if ($request->has('document')) {
                foreach ($request->input('document', []) as $file) {
                    $tmpPath = storage_path('app/public/order-product-images/tmp/' . $file);
                    $newPath = storage_path('app/public/order-product-images/' . $file);
                    
                    if(File::exists($tmpPath)) {
                        if(!File::exists(dirname($newPath))) {
                            File::makeDirectory(dirname($newPath), 0755, true);
                        }
                        File::move($tmpPath, $newPath);
                    }

                    $exists = OrderProductImage::where('order_product_id', $product->id)->where('image_path', $file)->exists();
                    if (!$exists) {
                        OrderProductImage::create([
                            'order_product_id' => $product->id,
                            'image_path' => $file,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('order-products.index')->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $product = OrderProduct::with(['images', 'units', 'unitPriceTiers'])->find($id);
        if(!$product){
             return redirect()->route('order-products.index')->with('error', 'Product not found.');
        }

        $page_title = "Edit Order Product";
        $page_description = "Edit order product";
        
        $categories = OrderCategory::pluck('name', 'id');
        $units = OrderUnit::pluck('name', 'id');
        $taxSlabs = TaxSlab::where('status', 1)->pluck('name', 'id');

        return view('order-products.edit', compact('product', 'page_title', 'page_description', 'categories', 'units', 'taxSlabs'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'sku' => 'required|unique:order_products,sku,'.$id.',id,deleted_at,NULL',
            'hsn_code' => 'required',
            'category_id' => 'required',
            'tax_slab_id' => 'required',
            'status' => 'required|boolean',
            'description' => 'nullable',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required',
            'units.*.price' => 'required|numeric|min:0',
            'units.*.status' => 'required|boolean',
            'pricing_tiers' => 'nullable|array',
            'pricing_tiers.*.name' => 'required|string',
            'pricing_tiers.*.units' => 'required|array',
            'pricing_tiers.*.units.*.unit_id' => 'required',
            'pricing_tiers.*.units.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $product = OrderProduct::find($id);
            if(!$product){
                return redirect()->route('order-products.index')->with('error', 'Product not found.');
            }

            $product->update([
                'name' => $request->name,
                'sku' => $request->sku,
                'hsn_code' => $request->hsn_code,
                'category_id' => $request->category_id,
                'tax_slab_id' => $request->tax_slab_id,
                'status' => boolval($request->status),
                'description' => $request->description,
            ]);
            
            OrderProductUnit::where('order_product_id', $product->id)->delete();
            
            foreach($request->units as $unitData){
                OrderProductUnit::create([
                    'order_product_id' => $product->id,
                    'unit_id' => $unitData['unit_id'],
                    'price' => $unitData['price'],
                    'status' => $unitData['status'],
                ]);
            }

            // Sync Tiered Pricing
            UnitPriceTier::where('product_id', $product->id)->delete();
            if($request->has('pricing_tiers')){
                $globalTiers = PricingTier::all();
                foreach($request->pricing_tiers as $tierIndex => $tierData){
                    $globalTier = $globalTiers->get($tierIndex);
                    if($globalTier && isset($tierData['units']) && is_array($tierData['units'])){
                        foreach($tierData['units'] as $tierUnitData){
                            if(!empty($tierUnitData['unit_id']) && isset($tierUnitData['price'])){
                                UnitPriceTier::create([
                                    'pricing_tier_id' => $globalTier->id,
                                    'product_id' => $product->id,
                                    'product_unit_id' => $tierUnitData['unit_id'],
                                    'amount' => $tierUnitData['price'],
                                    'status' => 1
                                ]);
                            }
                        }
                    }
                }
            }

            if ($request->has('document')) {
                foreach ($request->input('document', []) as $file) {
                    $tmpPath = storage_path('app/public/order-product-images/tmp/' . $file);
                    $newPath = storage_path('app/public/order-product-images/' . $file);

                    if(File::exists($tmpPath)) {
                        if(!File::exists(dirname($newPath))) {
                            File::makeDirectory(dirname($newPath), 0755, true);
                        }
                        File::move($tmpPath, $newPath);
                    }

                    $exists = OrderProductImage::where('order_product_id', $product->id)->where('image_path', $file)->exists();
                    if (!$exists) {
                        OrderProductImage::create([
                            'order_product_id' => $product->id,
                            'image_path' => $file,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('order-products.index')->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $product = OrderProduct::with(['images', 'units', 'unitPriceTiers'])->find($id);
        if(!$product){
             return redirect()->route('order-products.index')->with('error', 'Product not found.');
        }

        $page_title = "View Product";
        $page_description = "View product";
        
        $categories = OrderCategory::pluck('name', 'id');
        $units = OrderUnit::pluck('name', 'id');

        return view('order-products.show', compact('product', 'page_title', 'page_description', 'categories', 'units'));
    }

    public function destroy($id)
    {
        try {
            $product = OrderProduct::find($id);
            if(!$product){
                 return response()->json(['status' => false, 'message' => 'Product not found.']);
            }
            $product->delete();
            return response()->json(['status' => true, 'message' => 'Product deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }

    public function uploadImage(Request $request)
    {
        $path = storage_path('app/public/order-product-images/tmp');

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $file = $request->file('file');

        $name = uniqid() . '_' . trim($file->getClientOriginalName());

        $file->move($path, $name);

        return response()->json([
            'name'          => $name,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }

    public function deleteImage(Request $request)
    {
        if($request->has('filename')){
             $filename = $request->get('filename');
             $path = storage_path('app/public/order-product-images/tmp/' . $filename);
             if (file_exists($path)) {
                 unlink($path);
             }
        }
        
        if($request->has('id')){
            $image = OrderProductImage::find($request->id);
            if($image){
                $image_path = storage_path('app/public/order-product-images/' . $image->image_path);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
                $image->delete();
            }
        }
        
        return response()->json(['success' => true]);
    }
}
