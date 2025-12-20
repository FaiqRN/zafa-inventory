<?php

namespace App\Http\Controllers;

use App\Models\SeasonalInventorySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SeasonalInventorySettingController extends Controller
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
            $settings = SeasonalInventorySetting::getAllGrouped();

            return view('settings.seasonal-inventory', [
                'activemenu' => 'seasonal-inventory-settings',
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading seasonal inventory settings: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Gagal memuat pengaturan Seasonal Inventory');
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
                $setting = SeasonalInventorySetting::where('key', $settingData['key'])->first();

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
            SeasonalInventorySetting::clearCache();

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
            Log::error('Error updating seasonal inventory settings: ' . $e->getMessage());

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
                'forecast_period' => '90',
                'historical_data_months' => '12',
                'safety_stock_percentage' => '20',
                'lead_time_days' => '7',
                'high_season_multiplier' => '1.5',
                'low_season_multiplier' => '0.7',
                'reorder_point_days' => '14',
                'max_stock_months' => '3',
                'alert_threshold' => '30',
                'cache_duration' => '60'
            ];

            $updated = 0;

            foreach ($defaults as $key => $value) {
                if (SeasonalInventorySetting::updateValue($key, $value, auth()->user()->username ?? null)) {
                    $updated++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil mereset {$updated} pengaturan ke nilai default",
                'updated' => $updated
            ]);

        } catch (\Exception $e) {
            Log::error('Error resetting seasonal inventory settings: ' . $e->getMessage());

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
            $setting = SeasonalInventorySetting::where('key', $key)->first();

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
