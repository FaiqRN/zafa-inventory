<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangToko extends Model
{
    use HasFactory;

    public const TABLE = 'barang_toko';
    public const FIELD_BARANG_TOKO_ID = 'barang_toko_id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_HARGA_BARANG_TOKO = 'harga_barang_toko';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_BARANG_TOKO_ID;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        self::FIELD_BARANG_TOKO_ID,
        self::FIELD_TOKO_ID,
        self::FIELD_BARANG_ID,
        self::FIELD_HARGA_BARANG_TOKO,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_HARGA_BARANG_TOKO => 'decimal:2',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, self::FIELD_BARANG_ID, Barang::FIELD_BARANG_ID);
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }
}