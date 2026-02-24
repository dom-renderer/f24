<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderUnit;

class OrderUnitController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Units";
        $page_description = "Manage units here";

        return view('order-units.index', compact('page_title', 'page_description'));
    }

    public function ajax(Request $request)
    {
        $data = OrderUnit::query();

        return datatables()
            ->eloquent($data)
            ->addColumn('status', function($row){
                return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function($row){
                $action = '';
                if(auth()->user()->can('order-units.edit')){
                    $action .= '<a href="'.route('order-units.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
                }
                if(auth()->user()->can('order-units.destroy')){
                    $action .= '<form method="POST" action="'.route("order-units.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
                }
                if(auth()->user()->can('order-units.show')){
                    $action .= '<a href="'.route('order-units.show', $row->id).'" class="btn btn-info btn-sm me-2">Show</a>';
                }
                return $action;
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create()
    {
        $page_title = "Create Unit";
        $page_description = "Add a new unit";
        return view('order-units.create', compact('page_title', 'page_description'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:order_units,name,NULL,id,deleted_at,NULL',
            'description' => 'nullable|string'
        ]);

        try {
            OrderUnit::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('order-units.index')->with('success', 'Unit created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function edit($id)
    {
        $unit = OrderUnit::find($id);
        if(!$unit){
             return redirect()->route('order-units.index')->with('error', 'Unit not found.');
        }

        $page_title = "Edit Unit";
        $page_description = "Edit unit";

        return view('order-units.edit', compact('unit', 'page_title', 'page_description'));
    }

    public function show($id)
    {
        $unit = OrderUnit::find($id);
        if(!$unit){
             return redirect()->route('order-units.index')->with('error', 'Unit not found.');
        }

        $page_title = "Edit Unit";
        $page_description = "Edit unit";

        return view('order-units.show', compact('unit', 'page_title', 'page_description'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:order_units,name,'.$id.',id,deleted_at,NULL',
            'description' => 'nullable|string'
        ]);

        try {
            $unit = OrderUnit::find($id);
            if(!$unit){
                return redirect()->route('order-units.index')->with('error', 'Unit not found.');
            }

            $unit->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => boolval($request->status)
            ]);

            return redirect()->route('order-units.index')->with('success', 'Unit updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $unit = OrderUnit::find($id);
            if(!$unit){
                 return response()->json(['status' => false, 'message' => 'Unit not found.']);
            }
            $unit->delete();
            return response()->json(['status' => true, 'message' => 'Unit deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }
}
