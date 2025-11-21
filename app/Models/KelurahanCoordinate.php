<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KelurahanCoordinate extends Model
{
    use HasFactory;

    protected $table = 'kelurahan_coordinates';

    protected $fillable = [
        'nama',
        'nama_normalized',
        'kecamatan',
        'kota',
        'latitude',
        'longitude',
        'is_active',
        'source',
        'accuracy',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    /**
     * Scope untuk kelurahan aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk pencarian kelurahan by nama
     */
    public function scopeByNama($query, $nama)
    {
        $normalized = strtolower(str_replace([' ', '_'], '', $nama));
        return $query->where('nama_normalized', 'like', "%{$normalized}%");
    }

    /**
     * Scope untuk filter by kecamatan
     */
    public function scopeByKecamatan($query, $kecamatan)
    {
        return $query->where('kecamatan', $kecamatan);
    }

    /**
     * Scope untuk filter by kota
     */
    public function scopeByKota($query, $kota)
    {
        return $query->where('kota', 'like', "%{$kota}%");
    }

    /**
     * Get coordinate as array
     */
    public function getCoordinatesAttribute()
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Get full location info
     */
    public function getFullLocationAttribute()
    {
        return "{$this->nama}, {$this->kecamatan}, {$this->kota}";
    }

    /**
     * Static method untuk search kelurahan dengan fuzzy matching
     */
    public static function searchKelurahan($keyword, $limit = 10)
    {
        $normalized = strtolower(str_replace([' ', '_'], '', $keyword));
        
        return self::active()
            ->where(function($query) use ($normalized, $keyword) {
                $query->where('nama_normalized', 'like', "%{$normalized}%")
                      ->orWhere('nama', 'like', "%{$keyword}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Get kelurahan by exact normalized name
     */
    public static function findByNormalizedName($nama)
    {
        $normalized = strtolower(str_replace([' ', '_'], '', $nama));
        return self::active()->where('nama_normalized', $normalized)->first();
    }
}
