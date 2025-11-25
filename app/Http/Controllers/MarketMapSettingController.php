<?php

namespace App\Http\Controllers;

use App\Models\MarketMapSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MarketMapSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-users'); // Same permission as user management
    }

    /**
     * Display settings page
     */
    public function index()
    {
        try {
            $settings = MarketMapSetting::getAllGrouped();
            
            return view('market-map-settings.index', [
                'activemenu' => 'market-map-settings',
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading market map settings: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Gagal memuat pengaturan Market Map');
        }
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updated = 0;
            $errors = [];

            foreach ($request->settings as $settingData) {
                $setting = MarketMapSetting::where('key', $settingData['key'])->first();
                
                if (!$setting) {
                    $errors[] = "Setting '{$settingData['key']}' tidak ditemukan";
                    continue;
                }

                // Validate min/max
                $value = $settingData['value'];
                
                if ($setting->min_value !== null && $value < $setting->min_value) {
                    $errors[] = "{$setting->label}: Nilai minimum adalah {$setting->min_value}";
                    continue;
                }
                
                if ($setting->max_value !== null && $value > $setting->max_value) {
                    $errors[] = "{$setting->label}: Nilai maksimum adalah {$setting->max_value}";
                    continue;
                }

                // Update
                $setting->value = $value;
                $setting->user_update = auth()->user()->username ?? null;
                $setting->save();
                
                $updated++;
            }

            // Clear cache
            MarketMapSetting::clearCache();

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beberapa setting gagal diupdate',
                    'errors' => $errors,
                    'updated' => $updated
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengupdate {$updated} pengaturan",
                'updated' => $updated
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating market map settings: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset to default values
     */
    public function reset()
    {
        try {
            $defaults = [
                'cluster_radius' => '1.5',
                'max_stores_per_cluster' => '5',
                'min_profit_margin' => '10',
                'good_profit_margin' => '20',
                'default_harga_awal' => '12000',
                'default_initial_stock' => '100',
                'cache_duration' => '30',
                'sold_percentage_terkirim' => '80',
                'sold_percentage_all' => '60'
            ];

            $updated = 0;
            
            foreach ($defaults as $key => $value) {
                if (MarketMapSetting::updateValue($key, $value, auth()->user()->username ?? null)) {
                    $updated++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil mereset {$updated} pengaturan ke nilai default",
                'updated' => $updated
            ]);

        } catch (\Exception $e) {
            Log::error('Error resetting market map settings: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single setting value
     */
    public function getValue($key)
    {
        try {
            $setting = MarketMapSetting::where('key', $key)->first();
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $setting
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil setting: ' . $e->getMessage()
            ], 500);
        }
    }
}
