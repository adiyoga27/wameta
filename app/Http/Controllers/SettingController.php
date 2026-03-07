<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        // Default Meta Pricing categories
        $categories = [
            'marketing' => 'Marketing',
            'utility' => 'Utility',
            'authentication' => 'Authentication',
            'service' => 'Service'
        ];

        return view('settings.index', compact('settings', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'meta_pricing_marketing' => 'required|numeric|min:0',
            'meta_pricing_utility' => 'required|numeric|min:0',
            'meta_pricing_authentication' => 'required|numeric|min:0',
            'meta_pricing_service' => 'required|numeric|min:0',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->route('settings.index')->with('success', 'Pengaturan Harga Meta berhasil disimpan.');
    }
}
