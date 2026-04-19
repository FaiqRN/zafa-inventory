<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NotificationSettingController extends Controller
{

    private const SETTINGS_FILE = 'settings/notification.json';


    private const DEFAULT_SETTINGS = [
        'stock_threshold' => 0,           
        'pending_return_days' => 12,      
        'return_deadline_days' => 14,     
        'check_interval' => 60,           
    ];

    public function index()
    {
        $settings = $this->getSettings();
        return view('settings.notification', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'stock_threshold' => 'required|integer|min:0|max:1000',
            'pending_return_days' => 'required|integer|min:1|max:30',
            'return_deadline_days' => 'required|integer|min:7|max:60',
            'check_interval' => 'required|integer|min:30|max:300',
        ]);

        $this->saveSettings($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan notifikasi berhasil disimpan',
            ]);
        }

        return redirect()->back()->with('success', 'Pengaturan notifikasi berhasil disimpan');
    }

    public function getSettings()
    {
        if (!Storage::exists(self::SETTINGS_FILE)) {
            $this->saveSettings(self::DEFAULT_SETTINGS);
            return self::DEFAULT_SETTINGS;
        }

        try {
            $content = Storage::get(self::SETTINGS_FILE);
            $settings = json_decode($content, true);
            
            return array_merge(self::DEFAULT_SETTINGS, $settings);
        } catch (\Exception $e) {
            return self::DEFAULT_SETTINGS;
        }
    }

    public function getSettingsApi()
    {
        return response()->json([
            'success' => true,
            'settings' => $this->getSettings(),
        ]);
    }

    private function saveSettings(array $settings)
    {
        Storage::makeDirectory('settings');
        
        Storage::put(self::SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));
    }

    public function reset(Request $request)
    {
        $this->saveSettings(self::DEFAULT_SETTINGS);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan dikembalikan ke default',
                'settings' => self::DEFAULT_SETTINGS,
            ]);
        }

        return redirect()->back()->with('success', 'Pengaturan dikembalikan ke default');
    }
}
