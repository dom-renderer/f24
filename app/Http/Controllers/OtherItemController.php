<?php

namespace App\Http\Controllers;

use App\Models\OtherItem;
use App\Models\TaxSlab;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OtherItemController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Other Items";
        $page_description = "Manage Other Items here";

        return view('other-items.index', compact('page_title', 'page_description'));
    }

    public function ajax(Request $request)
    {
        $data = OtherItem::with('taxSlab');

        return datatables()
            ->eloquent($data)
            ->addColumn('status', function($row){
                return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function($row){
                $action = '';
                if(auth()->user()->can('other-items.edit')){
                    $action .= '<a href="'.route('other-items.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
                }
                if(auth()->user()->can('other-items.destroy')){
                    $action .= '<form method="POST" action="'.route("other-items.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
                }
                if(auth()->user()->can('other-items.show')){
                    $action .= '<a href="'.route('other-items.show', $row->id).'" class="btn btn-info btn-sm me-2">Show</a>';
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
        $page_title = "Create Other Item";
        $page_description = "Add a new Other Item";
        $taxSlabs = TaxSlab::where('status', 1)->get();
        return view('other-items.create', compact('page_title', 'page_description', 'taxSlabs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:other_items,name,NULL,id,deleted_at,NULL',
            'pricing_type' => 'required|in:fixed,as_per_actual',
            'price_per_piece' => 'required_if:pricing_type,fixed|nullable|numeric|min:0',
            'price_includes_tax' => 'boolean',
            'tax_slab_id' => 'required_if:price_includes_tax,0|nullable|exists:tax_slabs,id',
            'status' => 'required|boolean'
        ]);

        try {
            OtherItem::create([
                'name' => $request->name,
                'pricing_type' => $request->pricing_type,
                'price_per_piece' => is_numeric($request->price_per_piece) && $request->price_per_piece >= 0 ? $request->price_per_piece : 0,
                'price_includes_tax' => $request->has('price_includes_tax') ? 1 : 0,
                'tax_slab_id' => $request->tax_slab_id ?? null,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('other-items.index')->with('success', 'Other Item created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function show($id)
    {
        $otherItem = OtherItem::with('taxSlab')->find($id);
        if(!$otherItem){
             return redirect()->route('other-items.index')->with('error', 'Other Item not found.');
        }

        $page_title = "Show Other Item";
        $page_description = "Show Other Item details";

        return view('other-items.show', compact('otherItem', 'page_title', 'page_description'));
    }

    public function edit($id)
    {
        $otherItem = OtherItem::find($id);
        if(!$otherItem){
             return redirect()->route('other-items.index')->with('error', 'Other Item not found.');
        }

        $page_title = "Edit Other Item";
        $page_description = "Edit Other Item";
        $taxSlabs = TaxSlab::where('status', 1)->get();

        return view('other-items.edit', compact('otherItem', 'page_title', 'page_description', 'taxSlabs'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:other_items,name,'.$id.',id,deleted_at,NULL',
            'pricing_type' => 'required|in:fixed,as_per_actual',
            'price_per_piece' => 'required_if:pricing_type,fixed|nullable|numeric|min:0',
            'price_includes_tax' => 'boolean',
            'tax_slab_id' => 'required_if:price_includes_tax,0|nullable|exists:tax_slabs,id',
            'status' => 'required|boolean'
        ]);

        try {
            $otherItem = OtherItem::find($id);
            if(!$otherItem){
                return redirect()->route('other-items.index')->with('error', 'Other Item not found.');
            }

            $otherItem->update([
                'name' => $request->name,
                'pricing_type' => $request->pricing_type,
                'price_per_piece' => is_numeric($request->price_per_piece) && $request->price_per_piece >= 0 ? $request->price_per_piece : 0,
                'price_includes_tax' => $request->has('price_includes_tax') ? 1 : 0,
                'tax_slab_id' => $request->tax_slab_id ?? null,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('other-items.index')->with('success', 'Other Item updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $otherItem = OtherItem::find($id);
            if(!$otherItem){
                 return response()->json(['status' => false, 'message' => 'Other Item not found.']);
            }
            $otherItem->delete();
            return redirect()->route('services.index')->with('success', 'Other item deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.');
        }
    }
}
