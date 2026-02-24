<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Vehicles";
        $page_description = "Manage vehicles here";

        return view('vehicles.index', compact('page_title', 'page_description'));
    }

    public function ajax(Request $request)
    {
        $data = Vehicle::query();

        return datatables()
            ->eloquent($data)
            ->addColumn('status', function($row){
                return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function($row){
                $action = '';
                if(auth()->user()->can('vehicles.edit')){
                    $action .= '<a href="'.route('vehicles.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
                }
                if(auth()->user()->can('vehicles.destroy')){
                    $action .= '<form method="POST" action="'.route("vehicles.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
                }
                if(auth()->user()->can('vehicles.show')){
                    $action .= '<a href="'.route('vehicles.show', $row->id).'" class="btn btn-info btn-sm me-2">Show</a>';
                }
                return $action;
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create()
    {
        $page_title = "Create Vehicle";
        $page_description = "Add a new vehicle";
        return view('vehicles.create', compact('page_title', 'page_description'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'make' => 'required|string|max:255',
            'number' => 'required|string|max:255|unique:vehicles,number,NULL,id,deleted_at,NULL',
        ]);

        Vehicle::create([
            'name' => $request->name,
            'make' => $request->make,
            'number' => $request->number,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle created successfully.');
    }

    public function show(Vehicle $vehicle)
    {
        $page_title = "Show Vehicle";
        return view('vehicles.show', compact('page_title', 'vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        $page_title = "Edit Vehicle";
        return view('vehicles.edit', compact('page_title', 'vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'make' => 'required|string|max:255',
            'number' => 'required|string|max:255|unique:vehicles,number,' . $vehicle->id . ',id,deleted_at,NULL',
        ]);

        $vehicle->update([
            'name' => $request->name,
            'make' => $request->make,
            'number' => $request->number,
        ]);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $vehicle = Vehicle::find($id);
            if(!$vehicle){
                 return response()->json(['status' => false, 'message' => 'Vehicle not found.']);
            }
            $vehicle->delete();
            return response()->json(['status' => true, 'message' => 'Vehicle deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }
}
