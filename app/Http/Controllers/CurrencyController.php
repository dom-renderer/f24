<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $currencies = Currency::query();

            return datatables()
                ->eloquent($currencies)
                ->addColumn('is_default', function ($row) {
                    $checked = $row->is_default ? 'checked' : '';
                    return '<div class="form-check form-switch"><input class="form-check-input default-currency-checkbox" type="checkbox" data-id="' . $row->id . '" ' . $checked . '></div>';
                })
                ->addColumn('action', function ($row) {
                    $action = '';

                    if (auth()->user()->can('currencies.show')) {
                        $action .= '<a href="' . route('currencies.show', $row->id) . '" class="btn btn-info btn-sm me-2">Show</a>';
                    }

                    if (auth()->user()->can('currencies.edit')) {
                        $action .= '<a href="' . route('currencies.edit', $row->id) . '" class="btn btn-warning btn-sm me-2">Edit</a>';
                    }

                    if (auth()->user()->can('currencies.destroy') && !$row->is_default) {
                        $action .= '<button onclick="deleteCurrency(' . $row->id . ')" class="btn btn-danger btn-sm">Delete</button>';
                    }

                    return $action;
                })
                ->rawColumns(['action', 'is_default'])
                ->addIndexColumn()
                ->toJson();
        }

        $page_title = "Currencies";
        $page_description = "Manage currencies here";

        return view('currencies.index', compact('page_title', 'page_description'));
    }

    public function create()
    {
        $page_title = "Create Currency";
        return view('currencies.create', compact('page_title'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:currencies,name',
            'symbol' => 'required',
        ]);

        Currency::create($request->all());

        return redirect()->route('currencies.index')
            ->withSuccess(__('Currency created successfully.'));
    }

    public function edit($id)
    {
        $currency = Currency::findOrFail($id);
        $page_title = "Edit Currency";
        return view('currencies.edit', compact('currency', 'page_title'));
    }

    public function show($id)
    {
        $currency = Currency::findOrFail($id);
        $page_title = "Show Currency";
        return view('currencies.show', compact('currency', 'page_title'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:currencies,name,' . $id,
            'symbol' => 'required',
        ]);

        $currency = Currency::findOrFail($id);
        $currency->update($request->all());

        return redirect()->route('currencies.index')
            ->withSuccess(__('Currency updated successfully.'));
    }

    public function destroy($id)
    {
        $currency = Currency::findOrFail($id);
        $currency->delete();

        return response()->json(['success' => 'Currency deleted successfully.']);
    }
    public function setDefault($id)
    {
        Currency::where('id', '!=', $id)->update(['is_default' => false]);
        
        $currency = Currency::findOrFail($id);
        $currency->update(['is_default' => true]);

        return response()->json(['success' => 'Default currency updated.']);
    }
}
