<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeasonalAdjustment extends Model
{
    use HasFactory;

    public const TABLE = 'penyesuaian_musiman';
    public const FIELD_ID = 'id';
    public const FIELD_MONTH = 'month';
    public const FIELD_MULTIPLIER = 'multiplier';
    public const FIELD_DESCRIPTION = 'description';
    public const FIELD_IS_ACTIVE = 'is_active';
    public const FIELD_CREATED_BY = 'created_by';
    public const FIELD_UPDATED_BY = 'updated_by';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_MONTH,
        self::FIELD_MULTIPLIER,
        self::FIELD_DESCRIPTION,
        self::FIELD_IS_ACTIVE,
        self::FIELD_CREATED_BY,
        self::FIELD_UPDATED_BY,
    ];

    protected $casts = [
        self::FIELD_MONTH => 'integer',
        self::FIELD_MULTIPLIER => 'decimal:2',
        self::FIELD_IS_ACTIVE => 'boolean',
    ];

    protected $attributes = [
        self::FIELD_MULTIPLIER => 1.00,
        self::FIELD_IS_ACTIVE => true,
    ];

    public function scopeActive($query)
    {
        return $query->where(self::FIELD_IS_ACTIVE, true);
    }

    public function scopeForMonth($query, $month)
    {
        return $query->where(self::FIELD_MONTH, $month);
    }

    public static function getMultiplierForMonth($month)
    {
        $adjustment = static::where(self::FIELD_MONTH, $month)
            ->where(self::FIELD_IS_ACTIVE, true)
            ->first();
        
        return $adjustment ? $adjustment->{self::FIELD_MULTIPLIER} : 1.00;
    }
}
