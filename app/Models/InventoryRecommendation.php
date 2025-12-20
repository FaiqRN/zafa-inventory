<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class InventoryRecommendation extends Model
{
    use HasFactory;

    public const TABLE = 'inventory_recommendations';
    public const FIELD_ID = 'id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_HISTORICAL_AVG_SHIPPED = 'historical_avg_shipped';
    public const FIELD_HISTORICAL_AVG_SOLD = 'historical_avg_sold';
    public const FIELD_RECOMMENDED_QUANTITY = 'recommended_quantity';
    public const FIELD_SEASONAL_MULTIPLIER = 'seasonal_multiplier';
    public const FIELD_TREND_MULTIPLIER = 'trend_multiplier';
    public const FIELD_CONFIDENCE_LEVEL = 'confidence_level';
    public const FIELD_POTENTIAL_SAVINGS = 'potential_savings';
    public const FIELD_IMPROVEMENT_PERCENTAGE = 'improvement_percentage';
    public const FIELD_STATUS = 'status';
    public const FIELD_APPLIED_QUANTITY = 'applied_quantity';
    public const FIELD_NOTES = 'notes';
    public const FIELD_APPLIED_BY = 'applied_by';
    public const FIELD_APPLIED_AT = 'applied_at';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_TOKO_ID,
        self::FIELD_BARANG_ID,
        self::FIELD_HISTORICAL_AVG_SHIPPED,
        self::FIELD_HISTORICAL_AVG_SOLD,
        self::FIELD_RECOMMENDED_QUANTITY,
        self::FIELD_SEASONAL_MULTIPLIER,
        self::FIELD_TREND_MULTIPLIER,
        self::FIELD_CONFIDENCE_LEVEL,
        self::FIELD_POTENTIAL_SAVINGS,
        self::FIELD_IMPROVEMENT_PERCENTAGE,
        self::FIELD_STATUS,
        self::FIELD_APPLIED_QUANTITY,
        self::FIELD_NOTES,
        self::FIELD_APPLIED_BY,
        self::FIELD_APPLIED_AT,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_SEASONAL_MULTIPLIER => 'decimal:2',
        self::FIELD_TREND_MULTIPLIER => 'decimal:2',
        self::FIELD_POTENTIAL_SAVINGS => 'decimal:2',
        self::FIELD_IMPROVEMENT_PERCENTAGE => 'decimal:2',
        self::FIELD_APPLIED_AT => 'datetime',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, self::FIELD_BARANG_ID, Barang::FIELD_BARANG_ID);
    }

    public function apply($appliedBy, $customQuantity = null, $notes = null)
    {
        try {
            $this->{self::FIELD_STATUS} = 'applied';
            $this->{self::FIELD_APPLIED_QUANTITY} = $customQuantity ?? $this->{self::FIELD_RECOMMENDED_QUANTITY};
            $this->{self::FIELD_APPLIED_BY} = $appliedBy;
            $this->{self::FIELD_APPLIED_AT} = now();
            $this->{self::FIELD_NOTES} = $notes;
            $this->save();

            InventoryOptimizationLog::create([
                InventoryOptimizationLog::FIELD_TOKO_ID => $this->{self::FIELD_TOKO_ID},
                InventoryOptimizationLog::FIELD_BARANG_ID => $this->{self::FIELD_BARANG_ID},
                InventoryOptimizationLog::FIELD_ACTION_TYPE => $customQuantity ? 'recommendation_customized' : 'recommendation_applied',
                InventoryOptimizationLog::FIELD_OLD_QUANTITY => $this->{self::FIELD_HISTORICAL_AVG_SHIPPED},
                InventoryOptimizationLog::FIELD_NEW_QUANTITY => $this->{self::FIELD_APPLIED_QUANTITY},
                InventoryOptimizationLog::FIELD_METADATA => json_encode([
                    'original_recommendation' => $this->{self::FIELD_RECOMMENDED_QUANTITY},
                    'confidence_level' => $this->{self::FIELD_CONFIDENCE_LEVEL},
                    'potential_savings' => $this->{self::FIELD_POTENTIAL_SAVINGS},
                    'notes' => $notes
                ]),
                InventoryOptimizationLog::FIELD_PERFORMED_BY => $appliedBy,
                InventoryOptimizationLog::FIELD_PERFORMED_AT => now()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error applying recommendation: ' . $e->getMessage());
            return false;
        }
    }

    public function getConfidenceColorAttribute()
    {
        switch ($this->{self::FIELD_CONFIDENCE_LEVEL}) {
            case 'High':
                return 'success';
            case 'Medium':
                return 'primary';
            case 'Low':
                return 'warning';
            case 'Very Low':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    public function getStatusColorAttribute()
    {
        switch ($this->{self::FIELD_STATUS}) {
            case 'applied':
                return 'success';
            case 'customized':
                return 'info';
            case 'rejected':
                return 'danger';
            case 'pending':
            default:
                return 'warning';
        }
    }

    public function getPriorityAttribute()
    {
        if ($this->{self::FIELD_POTENTIAL_SAVINGS} > 1000000) {
            return 'High';
        } elseif ($this->{self::FIELD_POTENTIAL_SAVINGS} > 500000) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    public function getActionNeededAttribute()
    {
        $actions = [];
        
        if ($this->{self::FIELD_IMPROVEMENT_PERCENTAGE} > 30) {
            $actions[] = "Reduce allocation significantly";
        } elseif ($this->{self::FIELD_IMPROVEMENT_PERCENTAGE} > 15) {
            $actions[] = "Optimize allocation";
        }
        
        if ($this->{self::FIELD_CONFIDENCE_LEVEL} === 'Low' || $this->{self::FIELD_CONFIDENCE_LEVEL} === 'Very Low') {
            $actions[] = "Gather more data";
        }
        
        return implode('; ', $actions) ?: 'Apply recommendation';
    }

    public static function generateRecommendations()
    {
        static::where(self::FIELD_STATUS, 'pending')->delete();

        $barangTokos = BarangToko::with(['toko', 'barang'])
            ->whereHas('toko', function($q) {
                $q->where(Toko::FIELD_IS_ACTIVE, true);
            })
            ->whereHas('barang', function($q) {
                $q->where(Barang::FIELD_IS_DELETED, 0);
            })
            ->get();

        $generatedCount = 0;

        foreach ($barangTokos as $barangToko) {
            $recommendation = static::generateSingleRecommendation($barangToko);
            if ($recommendation) {
                $generatedCount++;
            }
        }

        return $generatedCount;
    }

    private static function generateSingleRecommendation($barangToko)
    {
        try {
            $periodStart = now()->subMonths(6);
            
            $historicalData = Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $barangToko->{BarangToko::FIELD_TOKO_ID})
                ->where(Pengiriman::FIELD_BARANG_ID, $barangToko->{BarangToko::FIELD_BARANG_ID})
                ->where(Pengiriman::FIELD_STATUS, 'terkirim')
                ->where(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $periodStart)
                ->get();

            if ($historicalData->isEmpty()) {
                return null;
            }

            $avgShipped = $historicalData->avg(Pengiriman::FIELD_JUMLAH_KIRIM) ?? 0;
            
            $avgSold = $historicalData->map(function ($shipment) {
                $returned = Retur::where(Retur::FIELD_PENGIRIMAN_ID, $shipment->{Pengiriman::FIELD_PENGIRIMAN_ID})
                    ->sum(Retur::FIELD_JUMLAH_RETUR) ?? 0;
                return max(0, $shipment->{Pengiriman::FIELD_JUMLAH_KIRIM} - $returned);
            })->avg() ?? 0;

            $seasonalMultiplier = SeasonalAdjustment::getMultiplierForMonth(now()->month);
            $trendMultiplier = static::calculateTrendMultiplier($barangToko, $periodStart);
            $recommendedQuantity = round($avgSold * $seasonalMultiplier * $trendMultiplier);
            $confidenceLevel = static::calculateConfidenceLevel($historicalData->count());
            $currentWaste = max($avgShipped - $avgSold, 0);
            $potentialSavings = $currentWaste * 15000;
            $improvementPercentage = $avgShipped > 0 ? ($currentWaste / $avgShipped) * 100 : 0;

            return static::create([
                self::FIELD_TOKO_ID => $barangToko->{BarangToko::FIELD_TOKO_ID},
                self::FIELD_BARANG_ID => $barangToko->{BarangToko::FIELD_BARANG_ID},
                self::FIELD_HISTORICAL_AVG_SHIPPED => round($avgShipped, 0),
                self::FIELD_HISTORICAL_AVG_SOLD => round($avgSold, 0),
                self::FIELD_RECOMMENDED_QUANTITY => max(0, $recommendedQuantity),
                self::FIELD_SEASONAL_MULTIPLIER => $seasonalMultiplier,
                self::FIELD_TREND_MULTIPLIER => $trendMultiplier,
                self::FIELD_CONFIDENCE_LEVEL => $confidenceLevel,
                self::FIELD_POTENTIAL_SAVINGS => $potentialSavings,
                self::FIELD_IMPROVEMENT_PERCENTAGE => round($improvementPercentage, 1),
                self::FIELD_STATUS => 'pending'
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating recommendation: ' . $e->getMessage());
            return null;
        }
    }

    private static function calculateTrendMultiplier($barangToko, $periodStart)
    {
        try {
            $recent3Months = Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $barangToko->{BarangToko::FIELD_TOKO_ID})
                ->where(Pengiriman::FIELD_BARANG_ID, $barangToko->{BarangToko::FIELD_BARANG_ID})
                ->where(Pengiriman::FIELD_STATUS, 'terkirim')
                ->where(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', now()->subMonths(3))
                ->avg(Pengiriman::FIELD_JUMLAH_KIRIM) ?? 0;
            
            $previous3Months = Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $barangToko->{BarangToko::FIELD_TOKO_ID})
                ->where(Pengiriman::FIELD_BARANG_ID, $barangToko->{BarangToko::FIELD_BARANG_ID})
                ->where(Pengiriman::FIELD_STATUS, 'terkirim')
                ->whereBetween(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, [now()->subMonths(6), now()->subMonths(3)])
                ->avg(Pengiriman::FIELD_JUMLAH_KIRIM) ?? 0;
            
            if (!$recent3Months || !$previous3Months) {
                return 1.0;
            }
            
            $trendRatio = $recent3Months / $previous3Months;
            
            return max(0.7, min(1.4, $trendRatio));
        } catch (\Exception $e) {
            return 1.0;
        }
    }

    private static function calculateConfidenceLevel($dataPoints)
    {
        if ($dataPoints >= 12) return 'High';
        if ($dataPoints >= 6) return 'Medium';
        if ($dataPoints >= 3) return 'Low';
        return 'Very Low';
    }
}
