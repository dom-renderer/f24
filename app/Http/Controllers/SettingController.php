<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function edit()
    {
        $setting = Setting::first();
        $currencies = \App\Models\Currency::all();
        return view('settings.edit', compact('setting', 'currencies'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_common_upi' => 'required|string',
            'default_currency_id' => 'required|exists:currencies,id',
            'company_store_discount' => 'required|numeric|min:0|max:100',
            'company_name' => 'nullable|string',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string',
            'cin' => 'nullable|string',
        ]);

        $setting = Setting::first();
        $oldUpi = $setting ? $setting->company_common_upi : null;

        Setting::updateOrCreate(
            ['id' => 1],
            [
                'company_common_upi' => $request->company_common_upi,
                'default_currency_id' => $request->default_currency_id,
                'company_store_discount' => $request->company_store_discount,
                'company_name' => $request->company_name,
                'address' => $request->address,
                'gstin' => $request->gstin,
                'cin' => $request->cin
            ]
        );

        \App\Models\Currency::query()->update(['is_default' => false]);
        \App\Models\Currency::where('id', $request->default_currency_id)->update(['is_default' => true]);

        if ($oldUpi !== $request->company_common_upi) {
            \App\Helpers\QrCodeHelper::generateQrCode($request->company_common_upi, 'company_upi_qr.png');
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
