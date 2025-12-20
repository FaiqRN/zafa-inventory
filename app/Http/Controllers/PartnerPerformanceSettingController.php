<?php

namespace App\Http\Controllers;

use App\Models\PartnerPerformanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class PartnerPerformanceSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-partner-performance-settings');
    }

    /**
     * Display Partner Performance Settings
     */
    public function index()
    {
        try {
            $breadcrumb = (object)[
                'title' => 'Pengaturan Partner Performance',
                'list' => ['Home', 'Sistem Pengaturan', 'Partner Performance']
            ];

            $settings = PartnerPerformanceSetting::getAllGrouped();

            return view('settings.partner-performance', [
                'breadcrumb' => $breadcrumb,
                'settings' => $settings,
                'activemenu' => 'partner-performance-settings'
            ]);
        } catch (Exception $e) {
            Log::error('Partner Performance Settings index error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat pengaturan.');
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
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = auth()->user()->username ?? null;
            $updatedCount = 0;

            foreach ($request->settings as $setting) {
                if (PartnerPerformanceSetting::updateValue($setting['key'], $setting['value'], $userId)) {
                    $updatedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil memperbarui {$updatedCount} pengaturan"
            ]);
        } catch (Exception $e) {
            Log::error('Update Partner Performance Settings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset to default values
     */
    public function resetDefaults()
    {
        try {
            $defaults = $this->getDefaultSettings();
            $userId = auth()->user()->username ?? null;

            foreach ($defaults as $default) {
                PartnerPerformanceSetting::updateOrCreate(
                    ['key' => $default['key']],
                    array_merge($default, ['user_update' => $userId])
                );
            }

            PartnerPerformanceSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan berhasil direset ke nilai default'
            ]);
        } catch (Exception $e) {
            Log::error('Reset Partner Performance Settings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get default settings
     */
    private function getDefaultSettings()
    {
        return [
            // Grading System
            [
                'key' => 'grade_a_plus_min',
                'value' => '85',
                'label' => 'Grade A+ Minimum',
                'description' => 'Minimum sell-through rate untuk grade A+ (Excellent Partner)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 80,
                'max_value' => 100,
                'sort_order' => 1
            ],
            [
                'key' => 'grade_a_min',
                'value' => '75',
                'label' => 'Grade A Minimum',
                'description' => 'Minimum sell-through rate untuk grade A (Good Partner)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 70,
                'max_value' => 90,
                'sort_order' => 2
            ],
            [
                'key' => 'grade_b_plus_min',
                'value' => '65',
                'label' => 'Grade B+ Minimum',
                'description' => 'Minimum sell-through rate untuk grade B+ (Average Partner)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 60,
                'max_value' => 80,
                'sort_order' => 3
            ],
            [
                'key' => 'grade_b_min',
                'value' => '55',
                'label' => 'Grade B Minimum',
                'description' => 'Minimum sell-through rate untuk grade B (Below Average)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 50,
                'max_value' => 70,
                'sort_order' => 4
            ],
            [
                'key' => 'grade_c_min',
                'value' => '45',
                'label' => 'Grade C Minimum',
                'description' => 'Minimum sell-through rate untuk grade C (Poor Partner)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 30,
                'max_value' => 60,
                'sort_order' => 5
            ],
            // Performance Weights
            [
                'key' => 'weight_sell_through',
                'value' => '0.4',
                'label' => 'Bobot Sell-Through Rate',
                'description' => 'Bobot untuk sell-through rate dalam perhitungan performance score',
                'type' => 'decimal',
                'unit' => '',
                'category' => 'weights',
                'min_value' => 0,
                'max_value' => 1,
                'sort_order' => 1
            ],
            [
                'key' => 'weight_speed',
                'value' => '0.25',
                'label' => 'Bobot Kecepatan Return',
                'description' => 'Bobot untuk kecepatan return dalam perhitungan performance score',
                'type' => 'decimal',
                'unit' => '',
                'category' => 'weights',
                'min_value' => 0,
                'max_value' => 1,
                'sort_order' => 2
            ],
            [
                'key' => 'weight_revenue',
                'value' => '0.25',
                'label' => 'Bobot Revenue',
                'description' => 'Bobot untuk revenue dalam perhitungan performance score',
                'type' => 'decimal',
                'unit' => '',
                'category' => 'weights',
                'min_value' => 0,
                'max_value' => 1,
                'sort_order' => 3
            ],
            [
                'key' => 'weight_volume',
                'value' => '0.1',
                'label' => 'Bobot Volume',
                'description' => 'Bobot untuk volume transaksi dalam perhitungan performance score',
                'type' => 'decimal',
                'unit' => '',
                'category' => 'weights',
                'min_value' => 0,
                'max_value' => 1,
                'sort_order' => 4
            ],
            // Performance Thresholds
            [
                'key' => 'good_performance_min',
                'value' => '70',
                'label' => 'Good Performance Minimum',
                'description' => 'Threshold minimum untuk kategori Good Performance',
                'type' => 'number',
                'unit' => '%',
                'category' => 'performance',
                'min_value' => 50,
                'max_value' => 100,
                'sort_order' => 1
            ],
            [
                'key' => 'warning_performance_max',
                'value' => '50',
                'label' => 'Warning Performance Maximum',
                'description' => 'Threshold maksimum untuk warning performance (dibawah ini perlu perhatian)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'performance',
                'min_value' => 30,
                'max_value' => 70,
                'sort_order' => 2
            ],
            [
                'key' => 'alert_days_no_transaction',
                'value' => '30',
                'label' => 'Alert Days No Transaction',
                'description' => 'Jumlah hari tanpa transaksi untuk memicu alert',
                'type' => 'number',
                'unit' => 'hari',
                'category' => 'performance',
                'min_value' => 7,
                'max_value' => 90,
                'sort_order' => 3
            ],
            // Calculation Settings
            [
                'key' => 'analysis_period_months',
                'value' => '3',
                'label' => 'Periode Analisis',
                'description' => 'Periode analisis performance dalam bulan',
                'type' => 'number',
                'unit' => 'bulan',
                'category' => 'calculation',
                'min_value' => 1,
                'max_value' => 12,
                'sort_order' => 1
            ],
            [
                'key' => 'min_transactions_for_grading',
                'value' => '5',
                'label' => 'Minimum Transaksi untuk Grading',
                'description' => 'Jumlah minimum transaksi yang diperlukan untuk memberikan grade',
                'type' => 'number',
                'unit' => 'transaksi',
                'category' => 'calculation',
                'min_value' => 1,
                'max_value' => 20,
                'sort_order' => 2
            ],
            [
                'key' => 'cache_duration',
                'value' => '60',
                'label' => 'Durasi Cache',
                'description' => 'Durasi cache untuk data partner performance (dalam menit)',
                'type' => 'number',
                'unit' => 'menit',
                'category' => 'system',
                'min_value' => 15,
                'max_value' => 1440,
                'sort_order' => 1
            ]
        ];
    }
}
