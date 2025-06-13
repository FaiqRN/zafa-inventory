<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeasonalAdjustment extends Model
{
    use HasFactory;

    protected $table = 'seasonal_adjustments';

    protected $fillable = [
        'month',
        'multiplier',
        'description',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'month' => 'integer',
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    protected $attributes = [
        'multiplier' => 1.00,
        'is_active' => true
    ];

    // Scope untuk bulan aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope untuk bulan tertentu
    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    // Get multiplier untuk bulan tertentu
    public static function getMultiplierForMonth($month)
    {
        $adjustment = static::where('month', $month)
            ->where('is_active', true)
            ->first();
        
        return $adjustment ? $adjustment->multiplier : 1.00;
    }
}