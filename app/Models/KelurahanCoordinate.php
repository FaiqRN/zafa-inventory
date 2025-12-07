<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KelurahanCoordinate extends Model
{
    use HasFactory;

    public const TABLE = 'kelurahan_coordinates';
    public const FIELD_ID = 'id';
    public const FIELD_NAMA = 'nama';
    public const FIELD_NAMA_NORMALIZED = 'nama_normalized';
    public const FIELD_KECAMATAN = 'kecamatan';
    public const FIELD_KOTA = 'kota';
    public const FIELD_LATITUDE = 'latitude';
    public const FIELD_LONGITUDE = 'longitude';
    public const FIELD_IS_ACTIVE = 'is_active';
    public const FIELD_SOURCE = 'source';
    public const FIELD_ACCURACY = 'accuracy';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_NAMA,
        self::FIELD_NAMA_NORMALIZED,
        self::FIELD_KECAMATAN,
        self::FIELD_KOTA,
        self::FIELD_LATITUDE,
        self::FIELD_LONGITUDE,
        self::FIELD_IS_ACTIVE,
        self::FIELD_SOURCE,
        self::FIELD_ACCURACY,
    ];

    protected $casts = [
        self::FIELD_LATITUDE => 'decimal:8',
        self::FIELD_LONGITUDE => 'decimal:8',
        self::FIELD_IS_ACTIVE => 'boolean',
    ];

    /**
     * Scope untuk kelurahan aktif
     */
    public function scopeActive($query)
    {
        return $query->where(self::FIELD_IS_ACTIVE, true);
    }

    /**
     * Scope untuk pencarian kelurahan by nama
     */
    public function scopeByNama($query, $nama)
    {
        $normalized = strtolower(str_replace([' ', '_'], '', $nama));
        return $query->where(self::FIELD_NAMA_NORMALIZED, 'like', "%{$normalized}%");
    }

    /**
     * Scope untuk filter by kecamatan
     */
    public function scopeByKecamatan($query, $kecamatan)
    {
        return $query->where(self::FIELD_KECAMATAN, $kecamatan);
    }

    /**
     * Scope untuk filter by kota
     */
    public function scopeByKota($query, $kota)
    {
        return $query->where(self::FIELD_KOTA, 'like', "%{$kota}%");
    }

    /**
     * Relasi ke Jalan
     */
    public function jalans()
    {
        return $this->hasMany(Jalan::class, Jalan::FIELD_KELURAHAN_ID);
    }

    /**
     * Get coordinate as array
     */
    public function getCoordinatesAttribute()
    {
        return [
            'lat' => (float) $this->{self::FIELD_LATITUDE},
            'lng' => (float) $this->{self::FIELD_LONGITUDE},
        ];
    }

    /**
     * Get full location info
     */
    public function getFullLocationAttribute()
    {
        return "{$this->{self::FIELD_NAMA}}, {$this->{self::FIELD_KECAMATAN}}, {$this->{self::FIELD_KOTA}}";
    }

    /**
     * Static method untuk search kelurahan dengan fuzzy matching
     */
    public static function searchKelurahan($keyword, $limit = 10)
    {
        $normalized = strtolower(str_replace([' ', '_'], '', $keyword));
        
        return self::active()
            ->where(function($query) use ($normalized, $keyword) {
                $query->where(self::FIELD_NAMA_NORMALIZED, 'like', "%{$normalized}%")
                      ->orWhere(self::FIELD_NAMA, 'like', "%{$keyword}%");
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
        return self::active()->where(self::FIELD_NAMA_NORMALIZED, $normalized)->first();
    }
}
