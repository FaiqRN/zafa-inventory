<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class InventoryRecommendation extends Model
{
    use HasFactory;

    protected $table = 'inventory_recommendations';

    protected $fillable = [
        'toko_id',
        'barang_id',
        'historical_avg_shipped',
        'historical_avg_sold',
        'recommended_quantity',
        'seasonal_multiplier',
        'trend_multiplier',
        'confidence_level',
        'potential_savings',
        'improvement_percentage',
        'status',
        'applied_quantity',
        'notes',
        'applied_by',
        'applied_at'
    ];

    protected $casts = [
        'seasonal_multiplier' => 'decimal:2',
        'trend_multiplier' => 'decimal:2',
        'potential_savings' => 'decimal:2',
        'improvement_percentage' => 'decimal:2',
        'applied_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id', 'toko_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'barang_id');
    }

    /**
     * Apply recommendation
     */
    public function apply($appliedBy, $customQuantity = null, $notes = null)
    {
        try {
            $this->status = 'applied';
            $this->applied_quantity = $customQuantity ?? $this->recommended_quantity;
            $this->applied_by = $appliedBy;
            $this->applied_at = now();
            $this->notes = $notes;
            $this->save();

            // Log the action
            InventoryOptimizationLog::create([
                'toko_id' => $this->toko_id,
                'barang_id' => $this->barang_id,
                'action_type' => $customQuantity ? 'recommendation_customized' : 'recommendation_applied',
                'old_quantity' => $this->historical_avg_shipped,
                'new_quantity' => $this->applied_quantity,
                'metadata' => json_encode([
                    'original_recommendation' => $this->recommended_quantity,
                    'confidence_level' => $this->confidence_level,
                    'potential_savings' => $this->potential_savings,
                    'notes' => $notes
                ]),
                'performed_by' => $appliedBy,
                'performed_at' => now()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error applying recommendation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get confidence color for display
     */
    public function getConfidenceColorAttribute()
    {
        switch ($this->confidence_level) {
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

    /**
     * Get status color for display
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
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

    /**
     * Get priority based on potential savings
     */
    public function getPriorityAttribute()
    {
        if ($this->potential_savings > 1000000) {
            return 'High';
        } elseif ($this->potential_savings > 500000) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    /**
     * Get action needed based on data
     */
    public function getActionNeededAttribute()
    {
        $actions = [];
        
        if ($this->improvement_percentage > 30) {
            $actions[] = "Reduce allocation significantly";
        } elseif ($this->improvement_percentage > 15) {
            $actions[] = "Optimize allocation";
        }
        
        if ($this->confidence_level === 'Low' || $this->confidence_level === 'Very Low') {
            $actions[] = "Gather more data";
        }
        
        return implode('; ', $actions) ?: 'Apply recommendation';
    }

    /**
     * Generate recommendations for all active product-store combinations
     */
    public static function generateRecommendations()
    {
        // Clear existing pending recommendations
        static::where('status', 'pending')->delete();

        $barangTokos = BarangToko::with(['toko', 'barang'])
            ->whereHas('toko', function($q) {
                $q->where('is_active', true);
            })
            ->whereHas('barang', function($q) {
                $q->where('is_deleted', 0);
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

    /**
     * Generate single recommendation
     */
    private static function generateSingleRecommendation($barangToko)
    {
        try {
            $periodStart = now()->subMonths(6);
            
            // Get historical data
            $historicalData = \App\Models\Pengiriman::where('toko_id', $barangToko->toko_id)
                ->where('barang_id', $barangToko->barang_id)
                ->where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->get();

            if ($historicalData->isEmpty()) {
                return null;
            }

            $avgShipped = $historicalData->avg('jumlah_kirim') ?? 0;
            
            // Calculate average sold
            $avgSold = $historicalData->map(function ($shipment) {
                $returned = \App\Models\Retur::where('pengiriman_id', $shipment->pengiriman_id)
                    ->sum('jumlah_retur') ?? 0;
                return max(0, $shipment->jumlah_kirim - $returned);
            })->avg() ?? 0;

            // Get seasonal multiplier
            $seasonalMultiplier = SeasonalAdjustment::getMultiplierForMonth(now()->month);
            
            // Calculate trend multiplier
            $trendMultiplier = static::calculateTrendMultiplier($barangToko, $periodStart);
            
            // Calculate recommended quantity
            $recommendedQuantity = round($avgSold * $seasonalMultiplier * $trendMultiplier);
            
            // Calculate confidence level
            $confidenceLevel = static::calculateConfidenceLevel($historicalData->count());
            
            // Calculate potential savings
            $currentWaste = max($avgShipped - $avgSold, 0);
            $potentialSavings = $currentWaste * 15000; // Average cost per unit
            
            // Calculate improvement percentage
            $improvementPercentage = $avgShipped > 0 ? ($currentWaste / $avgShipped) * 100 : 0;

            return static::create([
                'toko_id' => $barangToko->toko_id,
                'barang_id' => $barangToko->barang_id,
                'historical_avg_shipped' => round($avgShipped, 0),
                'historical_avg_sold' => round($avgSold, 0),
                'recommended_quantity' => max(0, $recommendedQuantity),
                'seasonal_multiplier' => $seasonalMultiplier,
                'trend_multiplier' => $trendMultiplier,
                'confidence_level' => $confidenceLevel,
                'potential_savings' => $potentialSavings,
                'improvement_percentage' => round($improvementPercentage, 1),
                'status' => 'pending'
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating recommendation: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate trend multiplier
     */
    private static function calculateTrendMultiplier($barangToko, $periodStart)
    {
        try {
            // Recent 3 months
            $recent3Months = \App\Models\Pengiriman::where('toko_id', $barangToko->toko_id)
                ->where('barang_id', $barangToko->barang_id)
                ->where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', now()->subMonths(3))
                ->avg('jumlah_kirim') ?? 0;
            
            // Previous 3 months
            $previous3Months = \App\Models\Pengiriman::where('toko_id', $barangToko->toko_id)
                ->where('barang_id', $barangToko->barang_id)
                ->where('status', 'terkirim')
                ->whereBetween('tanggal_pengiriman', [now()->subMonths(6), now()->subMonths(3)])
                ->avg('jumlah_kirim') ?? 0;
            
            if (!$recent3Months || !$previous3Months) {
                return 1.0;
            }
            
            $trendRatio = $recent3Months / $previous3Months;
            
            // Cap the trend adjustment
            return max(0.7, min(1.4, $trendRatio));
        } catch (\Exception $e) {
            return 1.0;
        }
    }

    /**
     * Calculate confidence level
     */
    private static function calculateConfidenceLevel($dataPoints)
    {
        if ($dataPoints >= 12) return 'High';
        if ($dataPoints >= 6) return 'Medium';
        if ($dataPoints >= 3) return 'Low';
        return 'Very Low';
    }
}