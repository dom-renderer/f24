<?php

namespace App\Http\Controllers;

use App\Models\PricingTier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PricingTierController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PricingTier::latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function($row){
                    if($row->status){
                        return '<span class="badge bg-success">Active</span>';
                    }else{
                        return '<span class="badge bg-danger">Inactive</span>';
                    }
                })
                ->addColumn('action', function($row){
                    $btn = '';

                    if(auth()->user()->can('pricing-tiers.show')){
                        $btn .= '<a href="'.route('pricing-tiers.show', $row->id).'" class="edit btn btn-info btn-sm">Show</a>';
                    }
                    if(auth()->user()->can('pricing-tiers.edit')){
                        $btn .= '<a href="'.route('pricing-tiers.edit', $row->id).'" class="edit btn btn-warning btn-sm">Edit</a>';
                    }
                    if(auth()->user()->can('pricing-tiers.destroy')){
                        $btn .= '<form method="POST" action="'.route("pricing-tiers.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
                    }
                    if (auth()->user()->can('discount-management.store')) {
                        $btn .= '<a href="'.route('discount-management.store', ['tier_id' => $row->id]).'" class="edit btn btn-success btn-sm"> <i class="bi bi-percent"> </i> Quantity Discount</a>';
                    }
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('pricing-tiers.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pricing-tiers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:pricing_tiers,name',
            'status' => 'required|boolean',
        ]);

        $input = $request->all();
        $input['slug'] = Str::slug($request->name);

        PricingTier::create($input);

        return redirect()->route('pricing-tiers.index')
                        ->with('success','Pricing Tier created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PricingTier  $pricingTier
     * @return \Illuminate\Http\Response
     */
    public function show(PricingTier $pricingTier)
    {
        return view('pricing-tiers.show',compact('pricingTier'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PricingTier  $pricingTier
     * @return \Illuminate\Http\Response
     */
    public function edit(PricingTier $pricingTier)
    {
        return view('pricing-tiers.edit',compact('pricingTier'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PricingTier  $pricingTier
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PricingTier $pricingTier)
    {
        $request->validate([
            'name' => 'required|unique:pricing_tiers,name,'.$pricingTier->id,
            'status' => 'required|boolean',
        ]);

        $input = $request->all();
        $input['slug'] = Str::slug($request->name);

        $pricingTier->update($input);

        return redirect()->route('pricing-tiers.index')
                        ->with('success','Pricing Tier updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PricingTier  $pricingTier
     * @return \Illuminate\Http\Response
     */
    public function destroy(PricingTier $pricingTier)
    {
        // Deletion is disabled for now
        // $pricingTier->delete();
        // return redirect()->route('pricing-tiers.index')
        //                 ->with('success','Pricing Tier deleted successfully');
    }
}
