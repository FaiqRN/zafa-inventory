<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EoqBiayaPesanGlobal extends Model
{
    use HasFactory;

    public const TABLE = 'eoq_biaya_pesan_global';
    public const FIELD_ID = 'id';
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

    public static function getTotalBiayaPesan(): float
    {
        return (float) self::sum(self::FIELD_NOMINAL);
    }

    public static function getByNama(string $namaBiaya)
    {
        return self::where(self::FIELD_NAMA_BIAYA, $namaBiaya)->first();
    }
}
