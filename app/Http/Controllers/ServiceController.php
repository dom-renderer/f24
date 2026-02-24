<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\TaxSlab;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Services";
        $page_description = "Manage Services here";

        return view('services.index', compact('page_title', 'page_description'));
    }

    public function ajax(Request $request)
    {
        $data = Service::with('taxSlab');

        return datatables()
            ->eloquent($data)
            ->addColumn('status', function($row){
                return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function($row){
                $action = '';
                if(auth()->user()->can('services.edit')){
                    $action .= '<a href="'.route('services.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
                }
                if(auth()->user()->can('services.destroy')){
                    $action .= '<form method="POST" action="'.route("services.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
                }
                if(auth()->user()->can('services.show')){
                    $action .= '<a href="'.route('services.show', $row->id).'" class="btn btn-info btn-sm me-2">Show</a>';
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
        $page_title = "Create Service";
        $page_description = "Add a new Service";
        $taxSlabs = TaxSlab::where('status', 1)->get();
        return view('services.create', compact('page_title', 'page_description', 'taxSlabs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:services,name,NULL,id,deleted_at,NULL',
            'pricing_type' => 'required|in:fixed,as_per_actual',
            'price_per_piece' => 'required_if:pricing_type,fixed|nullable|numeric|min:0',
            'price_includes_tax' => 'boolean',
            'tax_slab_id' => 'required_if:price_includes_tax,0|nullable|exists:tax_slabs,id',
            'status' => 'required|boolean'
        ]);

        try {
            Service::create([
                'name' => $request->name,
                'pricing_type' => $request->pricing_type,
                'price_per_piece' => is_numeric($request->price_per_piece) && $request->price_per_piece >= 0 ? $request->price_per_piece : 0,
                'price_includes_tax' => $request->has('price_includes_tax') ? 1 : 0,
                'tax_slab_id' => $request->tax_slab_id ?? null,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('services.index')->with('success', 'Service created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function show($id)
    {
        $service = Service::with('taxSlab')->find($id);
        if(!$service){
             return redirect()->route('services.index')->with('error', 'Service not found.');
        }

        $page_title = "Show Service";
        $page_description = "Show Service details";

        return view('services.show', compact('service', 'page_title', 'page_description'));
    }

    public function edit($id)
    {
        $service = Service::find($id);
        if(!$service){
             return redirect()->route('services.index')->with('error', 'Service not found.');
        }

        $page_title = "Edit Service";
        $page_description = "Edit Service";
        $taxSlabs = TaxSlab::where('status', 1)->get();

        return view('services.edit', compact('service', 'page_title', 'page_description', 'taxSlabs'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:services,name,'.$id.',id,deleted_at,NULL',
            'pricing_type' => 'required|in:fixed,as_per_actual',
            'price_per_piece' => 'required_if:pricing_type,fixed|nullable|numeric|min:0',
            'price_includes_tax' => 'boolean',
            'tax_slab_id' => 'required_if:price_includes_tax,0|nullable|exists:tax_slabs,id',
            'status' => 'required|boolean'
        ]);

        try {
            $service = Service::find($id);
            if(!$service){
                return redirect()->route('services.index')->with('error', 'Service not found.');
            }

            $service->update([
                'name' => $request->name,
                'pricing_type' => $request->pricing_type,
                'price_per_piece' => is_numeric($request->price_per_piece) && $request->price_per_piece >= 0 ? $request->price_per_piece : 0,
                'price_includes_tax' => $request->has('price_includes_tax') ? 1 : 0,
                'tax_slab_id' => $request->tax_slab_id ?? null,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('services.index')->with('success', 'Service updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $service = Service::find($id);
            if(!$service){
                 return response()->json(['status' => false, 'message' => 'Service not found.']);
            }
            $service->delete();
            return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.');
        }
    }
}
