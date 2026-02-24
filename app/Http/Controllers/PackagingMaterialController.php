<?php

namespace App\Http\Controllers;

use App\Models\PackagingMaterial;
use App\Models\TaxSlab;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PackagingMaterialController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Packaging Materials";
        $page_description = "Manage Packaging Materials here";

        return view('packaging-materials.index', compact('page_title', 'page_description'));
    }

    public function ajax(Request $request)
    {
        $data = PackagingMaterial::with('taxSlab');

        return datatables()
            ->eloquent($data)
            ->addColumn('status', function($row){
                return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function($row){
                $action = '';
                if(auth()->user()->can('packaging-materials.edit')){
                    $action .= '<a href="'.route('packaging-materials.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
                }
                if(auth()->user()->can('packaging-materials.destroy')){
                    $action .= '<form method="POST" action="'.route("packaging-materials.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
                }
                if(auth()->user()->can('packaging-materials.show')){
                    $action .= '<a href="'.route('packaging-materials.show', $row->id).'" class="btn btn-info btn-sm me-2">Show</a>';
                }
                return $action;
            })
            ->editColumn('pricing_type', function($row) {
                return ucfirst(str_replace('_', ' ', $row->pricing_type));
            })
             ->editColumn('price_per_piece', function($row) {
                return $row->pricing_type == 'fixed' ? number_format($row->price_per_piece, 2) : '-';
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create()
    {
        $page_title = "Create Packaging Material";
        $page_description = "Add a new Packaging Material";
        $taxSlabs = TaxSlab::where('status', 1)->get();
        return view('packaging-materials.create', compact('page_title', 'page_description', 'taxSlabs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:packaging_materials,name,NULL,id,deleted_at,NULL',
            'pricing_type' => 'required|in:fixed,as_per_actual',
            'price_per_piece' => 'required_if:pricing_type,fixed|nullable|numeric|min:0',
            'price_includes_tax' => 'boolean',
            'tax_slab_id' => 'required_if:price_includes_tax,0|nullable|exists:tax_slabs,id',
            'status' => 'required|boolean'
        ]);

        try {
            PackagingMaterial::create([
                'name' => $request->name,
                'pricing_type' => $request->pricing_type,
                'price_per_piece' => is_numeric($request->price_per_piece) && $request->price_per_piece >= 0 ? $request->price_per_piece : 0,
                'price_includes_tax' => $request->has('price_includes_tax') ? 1 : 0,
                'tax_slab_id' => $request->tax_slab_id ?? null,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('packaging-materials.index')->with('success', 'Packaging Material created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function show($id)
    {
        $packagingMaterial = PackagingMaterial::with('taxSlab')->find($id);
        if(!$packagingMaterial){
             return redirect()->route('packaging-materials.index')->with('error', 'Packaging Material not found.');
        }

        $page_title = "Show Packaging Material";
        $page_description = "Show Packaging Material details";

        return view('packaging-materials.show', compact('packagingMaterial', 'page_title', 'page_description'));
    }

    public function edit($id)
    {
        $packagingMaterial = PackagingMaterial::find($id);
        if(!$packagingMaterial){
             return redirect()->route('packaging-materials.index')->with('error', 'Packaging Material not found.');
        }

        $page_title = "Edit Packaging Material";
        $page_description = "Edit Packaging Material";
        $taxSlabs = TaxSlab::where('status', 1)->get();

        return view('packaging-materials.edit', compact('packagingMaterial', 'page_title', 'page_description', 'taxSlabs'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:packaging_materials,name,'.$id.',id,deleted_at,NULL',
            'pricing_type' => 'required|in:fixed,as_per_actual',
            'price_per_piece' => 'required_if:pricing_type,fixed|nullable|numeric|min:0',
            'price_includes_tax' => 'boolean',
            'tax_slab_id' => 'required_if:price_includes_tax,0|nullable|exists:tax_slabs,id',
            'status' => 'required|boolean'
        ]);

        try {
            $packagingMaterial = PackagingMaterial::find($id);
            if(!$packagingMaterial){
                return redirect()->route('packaging-materials.index')->with('error', 'Packaging Material not found.');
            }

            $packagingMaterial->update([
                'name' => $request->name,
                'pricing_type' => $request->pricing_type,
                'price_per_piece' => is_numeric($request->price_per_piece) && $request->price_per_piece >= 0 ? $request->price_per_piece : 0,
                'price_includes_tax' => $request->has('price_includes_tax') ? 1 : 0,
                'tax_slab_id' => $request->tax_slab_id ?? null,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('packaging-materials.index')->with('success', 'Packaging Material updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $packagingMaterial = PackagingMaterial::find($id);
            if(!$packagingMaterial){
                 return response()->json(['status' => false, 'message' => 'Packaging Material not found.']);
            }
            $packagingMaterial->delete();
            return redirect()->route('packaging-materials.index')->with('success', 'Packaging material deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.');
        }
    }
}
