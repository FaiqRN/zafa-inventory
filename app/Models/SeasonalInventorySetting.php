<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SeasonalInventorySetting extends Model
{
    protected $table = 'seasonal_inventory_settings';

    protected $fillable = [
        'key',
        'value',
        'label',
        'description',
        'type',
        'unit',
        'category',
        'min_value',
        'max_value',
        'sort_order',
        'user_create',
        'user_update'
    ];

    protected $casts = [
        'min_value' => 'integer',
        'max_value' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get setting value by key
     */
    public static function getValue($key, $default = null)
    {
        $cacheKey = 'seasonal_inventory_setting_' . $key;

        return Cache::remember($cacheKey, 60 * 24, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Get all settings grouped by category
     */
    public static function getAllGrouped()
    {
        return Cache::remember('seasonal_inventory_settings_all', 60 * 24, function () {
            return self::orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('category');
        });
    }

    /**
     * Update setting value
     */
    public static function updateValue($key, $value, $userId = null)
    {
        $setting = self::where('key', $key)->first();

        if ($setting) {
            $setting->value = $value;
            $setting->user_update = $userId ?? auth()->user()->username ?? null;
            $setting->save();

            // Clear cache
            Cache::forget('seasonal_inventory_setting_' . $key);
            Cache::forget('seasonal_inventory_settings_all');

            return true;
        }

        return false;
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        $keys = self::pluck('key');

        foreach ($keys as $key) {
            Cache::forget('seasonal_inventory_setting_' . $key);
        }

        Cache::forget('seasonal_inventory_settings_all');
    }
}
