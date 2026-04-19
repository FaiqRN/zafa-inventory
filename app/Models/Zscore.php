<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zscore extends Model
{
    use HasFactory;

    public const TABLE = 'ss_zscore_setting';
    public const FIELD_ID = 'id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_LABEL = 'label';
    public const FIELD_SERVICE_LEVEL = 'service_level';
    public const FIELD_Z_SCORE = 'z_score';
    public const FIELD_KETERANGAN = 'keterangan';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_ID;
    public $timestamps = true;

    protected $fillable = [
        self::FIELD_TOKO_ID,
        self::FIELD_BARANG_ID,
        self::FIELD_LABEL,
        self::FIELD_SERVICE_LEVEL,
        self::FIELD_Z_SCORE,
        self::FIELD_KETERANGAN,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_SERVICE_LEVEL => 'decimal:2',
        self::FIELD_Z_SCORE => 'decimal:4',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    public function scopeOrderByServiceLevel($query, string $direction = 'asc')
    {
        return $query->orderBy(self::FIELD_SERVICE_LEVEL, $direction);
    }

    public function scopeForToko($query, string $tokoId)
    {
        return $query->where(self::FIELD_TOKO_ID, $tokoId);
    }

    public function scopeForBarang($query, string $barangId)
    {
        return $query->where(self::FIELD_BARANG_ID, $barangId);
    }

    public function scopeForServiceLevel($query, float $serviceLevel)
    {
        return $query->where(self::FIELD_SERVICE_LEVEL, $serviceLevel);
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, self::FIELD_BARANG_ID, Barang::FIELD_BARANG_ID);
    }
}
