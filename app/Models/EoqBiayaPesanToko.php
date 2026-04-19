<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EoqBiayaPesanToko extends Model
{
    use HasFactory;

    public const TABLE = 'eoq_biaya_pesan_toko';
    public const FIELD_ID = 'id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_NAMA_BIAYA = 'nama_biaya';
    public const FIELD_NOMINAL = 'nominal';
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
        self::FIELD_NAMA_BIAYA,
        self::FIELD_NOMINAL,
        self::FIELD_KETERANGAN,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_NOMINAL => 'decimal:2',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }

    /**
     * Get total biaya pesan for specific toko (with global fallback)
     */
    public static function getTotalBiayaPesanForToko(string $tokoId): float
    {
        $globalBiaya = EoqBiayaPesanGlobal::all()->keyBy(EoqBiayaPesanGlobal::FIELD_NAMA_BIAYA);
        $tokoBiaya = self::where(self::FIELD_TOKO_ID, $tokoId)->get()->keyBy(self::FIELD_NAMA_BIAYA);

        $total = 0;
        foreach ($globalBiaya as $namaBiaya => $biaya) {
            if ($tokoBiaya->has($namaBiaya)) {
                $total += (float) $tokoBiaya[$namaBiaya]->{self::FIELD_NOMINAL};
            } else {
                $total += (float) $biaya->{EoqBiayaPesanGlobal::FIELD_NOMINAL};
            }
        }

        return $total;
    }

    public function scopeForToko($query, string $tokoId)
    {
        return $query->where(self::FIELD_TOKO_ID, $tokoId);
    }
}
