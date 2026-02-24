<?php

namespace App\Http\Controllers;

use App\Models\TaxSlab;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TaxSlabController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Tax Slabs";
        $page_description = "Manage Tax Slabs here";

        return view('tax-slabs.index', compact('page_title', 'page_description'));
    }

    public function ajax(Request $request)
    {
        $data = TaxSlab::query();

        return datatables()
            ->eloquent($data)
            ->addColumn('status', function($row){
                return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function($row){
                $action = '';
                if(auth()->user()->can('tax-slabs.edit')){
                    $action .= '<a href="'.route('tax-slabs.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
                }
                if(auth()->user()->can('tax-slabs.destroy')){
                    $action .= '<form method="POST" action="'.route("tax-slabs.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
                }
                if(auth()->user()->can('tax-slabs.show')){
                    $action .= '<a href="'.route('tax-slabs.show', $row->id).'" class="btn btn-info btn-sm me-2">Show</a>';
                }
                return $action;
            })
            ->editColumn('cgst', function($row){
                return $row->cgst . '%';
            })
            ->editColumn('sgst', function($row){
                return $row->sgst . '%';
            })
            // ->editColumn('igst', function($row){
            //     return $row->igst . '%';
            // })
            ->addIndexColumn()
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create()
    {
        $page_title = "Create Tax Slab";
        $page_description = "Add a new Tax Slab";
        return view('tax-slabs.create', compact('page_title', 'page_description'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:tax_slabs,name,NULL,id,deleted_at,NULL',
            'cgst' => 'required|numeric|min:0|max:100',
            'sgst' => 'required|numeric|min:0|max:100',
            // 'igst' => 'required|numeric|min:0|max:100',
            'status' => 'required|boolean'
        ]);

        if (($request->cgst + $request->sgst) > 100) {
            return back()->with('error', 'Total of CGST and SGST should be less than or equal to 100.')->withInput();
        }

        try {
            TaxSlab::create([
                'name' => $request->name,
                'cgst' => $request->cgst,
                'sgst' => $request->sgst,
                'igst' => 0,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('tax-slabs.index')->with('success', 'Tax Slab created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function show($id)
    {
        $taxSlab = TaxSlab::find($id);
        if(!$taxSlab){
             return redirect()->route('tax-slabs.index')->with('error', 'Tax Slab not found.');
        }

        $page_title = "Show Tax Slab";
        $page_description = "Show Tax Slab details";

        return view('tax-slabs.show', compact('taxSlab', 'page_title', 'page_description'));
    }

    public function edit($id)
    {
        $taxSlab = TaxSlab::find($id);
        if(!$taxSlab){
             return redirect()->route('tax-slabs.index')->with('error', 'Tax Slab not found.');
        }

        $page_title = "Edit Tax Slab";
        $page_description = "Edit Tax Slab";

        return view('tax-slabs.edit', compact('taxSlab', 'page_title', 'page_description'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:tax_slabs,name,'.$id.',id,deleted_at,NULL',
            'cgst' => 'required|numeric|min:0|max:100',
            'sgst' => 'required|numeric|min:0|max:100',
            // 'igst' => 'required|numeric|min:0|max:100',
            'status' => 'required|boolean'
        ]);

        if (($request->cgst + $request->sgst) > 100) {
            return back()->with('error', 'Total of CGST and SGST should be less than or equal to 100.')->withInput();
        }

        try {
            $taxSlab = TaxSlab::find($id);
            if(!$taxSlab){
                return redirect()->route('tax-slabs.index')->with('error', 'Tax Slab not found.');
            }

            $taxSlab->update([
                'name' => $request->name,
                'cgst' => $request->cgst,
                'sgst' => $request->sgst,
                'igst' => 0,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('tax-slabs.index')->with('success', 'Tax Slab updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $taxSlab = TaxSlab::find($id);
            if(!$taxSlab){
                 return response()->json(['status' => false, 'message' => 'Tax Slab not found.']);
            }
            $taxSlab->delete();
            return response()->json(['status' => true, 'message' => 'Tax Slab deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }
}
