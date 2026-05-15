<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'company_name' => Setting::getValue('company_name', config('app.name')),
            'company_address' => Setting::getValue('company_address', ''),
            'company_phone' => Setting::getValue('company_phone', ''),
            'company_email' => Setting::getValue('company_email', ''),
            'company_logo' => Setting::getValue('company_logo', ''),
            'receipt_header' => Setting::getValue('receipt_header', ''),
            'receipt_footer' => Setting::getValue('receipt_footer', 'Thank you for your visit!'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
            'receipt_header' => 'nullable|string',
            'receipt_footer' => 'nullable|string',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        foreach ($validated as $key => $value) {
            if ($key === 'company_logo' && $request->hasFile('company_logo')) {
                // Delete old logo if exists
                $oldLogo = Setting::getValue('company_logo');
                if ($oldLogo) {
                    Storage::disk('public')->delete($oldLogo);
                }

                $path = $request->file('company_logo')->store('company', 'public');
                Setting::setValue('company_logo', $path);
                continue;
            }

            if ($key !== 'company_logo') {
                Setting::setValue($key, $value);
            }
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
