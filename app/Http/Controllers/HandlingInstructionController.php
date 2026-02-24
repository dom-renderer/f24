<?php

namespace App\Http\Controllers;

use App\Models\HandlingInstruction;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class HandlingInstructionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = HandlingInstruction::query();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $action = '';
                    if(auth()->user()->can('handling-instructions.show')){
                        $action .= '<a href="'.route('handling-instructions.show', $row->id).'" class="btn btn-info btn-sm me-2">Show</a>';
                    }
                    if(auth()->user()->can('handling-instructions.edit')){
                        $action .= '<a href="'.route('handling-instructions.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
                    }
                    if(auth()->user()->can('handling-instructions.destroy')){
                        $action .= '<form method="POST" action="'.route("handling-instructions.destroy", $row->id).'" style="display:inline;" onsubmit="return confirm(\'Are you sure?\');"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm">Delete</button></form>';
                    }
                    return $action;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $page_title = "Handling Instructions";
        $page_description = "Manage handling instructions here";

        return view('handling-instructions.index', compact('page_title', 'page_description'));
    }

    public function create()
    {
        $page_title = "Create Handling Instruction";
        $page_description = "Add a new handling instruction";
        return view('handling-instructions.create', compact('page_title', 'page_description'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:handling_instructions,name,NULL,id,deleted_at,NULL',
        ]);

        try {
            HandlingInstruction::create([
                'name' => $request->name,
            ]);

            return redirect()->route('handling-instructions.index')->with('success', 'Handling Instruction created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function edit($id)
    {
        $instruction = HandlingInstruction::find($id);
        if(!$instruction){
             return redirect()->route('handling-instructions.index')->with('error', 'Instruction not found.');
        }

        $page_title = "Edit Handling Instruction";
        $page_description = "Edit handling instruction";

        return view('handling-instructions.edit', compact('instruction', 'page_title', 'page_description'));
    }

    public function show($id)
    {
        $instruction = HandlingInstruction::find($id);
        if(!$instruction){
             return redirect()->route('handling-instructions.index')->with('error', 'Instruction not found.');
        }

        $page_title = "View Handling Instruction";
        $page_description = "View handling instruction";

        return view('handling-instructions.show', compact('instruction', 'page_title', 'page_description'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:handling_instructions,name,'.$id.',id,deleted_at,NULL',
        ]);

        try {
            $instruction = HandlingInstruction::find($id);
            if(!$instruction){
                return redirect()->route('handling-instructions.index')->with('error', 'Instruction not found.');
            }

            $instruction->update([
                'name' => $request->name,
            ]);

            return redirect()->route('handling-instructions.index')->with('success', 'Handling Instruction updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $instruction = HandlingInstruction::find($id);
            if(!$instruction){
                 return back()->with('error', 'Instruction not found.');
            }
            $instruction->delete();
            return back()->with('success', 'Handling Instruction deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.');
        }
    }
}
