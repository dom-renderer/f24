<?php

namespace App\Http\Controllers;

use App\Models\OrderCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Categories";
        $page_description = "Manage categories here";

        return view('order-categories.index', compact('page_title', 'page_description'));
    }

    public function ajax(Request $request)
    {
        $data = OrderCategory::query()->with('parent');

        return datatables()
            ->eloquent($data)
            ->addColumn('parent', function($row){
                return $row->parent ? $row->parent->name : 'N/A';
            })
            ->addColumn('status', function($row){
                return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function($row){
                $action = '';
                if(auth()->user()->can('order-categories.edit')){
                    $action .= '<a href="'.route('order-categories.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
                }
                if(auth()->user()->can('order-categories.destroy')){
                    $action .= '<form method="POST" action="'.route("order-categories.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
                }
                if(auth()->user()->can('order-categories.show')){
                    $action .= '<a href="'.route('order-categories.show', $row->id).'" class="btn btn-info btn-sm me-2">Show</a>';
                }
                return $action;
            })
            ->addIndexColumn()
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function create()
    {
        $page_title = "Create Category";
        $page_description = "Add a new category";
        $parents = OrderCategory::pluck('name', 'id');
        return view('order-categories.create', compact('page_title', 'page_description', 'parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:order_categories,name,NULL,id,deleted_at,NULL',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:order_categories,id'
        ]);

        try {
            OrderCategory::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => boolval($request->status),
                'parent_id' => $request->parent_id
            ]);

            return redirect()->route('order-categories.index')->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function edit($id)
    {
        $category = OrderCategory::find($id);
        if(!$category){
             return redirect()->route('order-categories.index')->with('error', 'Category not found.');
        }

        $page_title = "Edit Category";
        $page_description = "Edit category";
        $parents = OrderCategory::where('id', '!=', $id)->pluck('name', 'id');

        return view('order-categories.edit', compact('category', 'page_title', 'page_description', 'parents'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:order_categories,name,'.$id.',id,deleted_at,NULL',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:order_categories,id|not_in:'.$id
        ]);
        
        if($request->parent_id){
             
             $parent = OrderCategory::find($request->parent_id);
             $loop = false;
             while($parent){
                 if($parent->parent_id == $id){
                     $loop = true; 
                     break;
                 }
                 $parent = $parent->parent;
             }
             
             if($loop){
                 return back()->with('error', 'Cannot select a descendant as parent (Loop verified).')->withInput();
             }
        }

        try {
            $category = OrderCategory::find($id);
            if(!$category){
                return redirect()->route('order-categories.index')->with('error', 'Category not found.');
            }

            $category->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => boolval($request->status),
                'parent_id' => $request->parent_id
            ]);

            return redirect()->route('order-categories.index')->with('success', 'Category updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function show($id)
    {
        $category = OrderCategory::find($id);
        if(!$category){
             return redirect()->route('order-categories.index')->with('error', 'Category not found.');
        }

        $page_title = "View Category";
        $page_description = "View category";

        return view('order-categories.show', compact('category', 'page_title', 'page_description'));
    }

    public function destroy($id)
    {
        try {
            $category = OrderCategory::find($id);
            if(!$category){
                 return response()->json(['status' => false, 'message' => 'Category not found.']);
            }
            $category->delete();
            return response()->json(['status' => true, 'message' => 'Category deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }
}
