<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = SiteSetting::query()->first() ?? new SiteSetting();

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:64'],
            'address_en' => ['required', 'string', 'max:2000'],
            'address_ar' => ['required', 'string', 'max:2000'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'footer_desc_en' => ['nullable', 'string', 'max:2000'],
            'footer_desc_ar' => ['nullable', 'string', 'max:2000'],
        ]);

        $settings = SiteSetting::query()->first();
        if ($settings) {
            $settings->update($data);
        } else {
            SiteSetting::create($data);
        }

        return redirect()->route('admin.settings.edit')->with('status', __('admin.settings_updated'));
    }
}
