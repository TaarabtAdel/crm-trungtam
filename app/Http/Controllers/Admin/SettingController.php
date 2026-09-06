<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = [
            'center_name' => Setting::get('center_name', 'CRM Trung Tâm'),
            'logo_text' => Setting::get('logo_text', 'TPT2'),
        ];

        return view('admin.system.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'center_name' => 'required|string|max:255',
            'logo_text' => 'required|string|max:50',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Đã lưu cài đặt.');
    }
}
