<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    public const TABLE = 'barang';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_BARANG_KODE = 'barang_kode';
    public const FIELD_NAMA_BARANG = 'nama_barang';
    public const FIELD_HARGA_AWAL_BARANG = 'harga_awal_barang';
    public const FIELD_STOK = 'stok';
    public const FIELD_SATUAN = 'satuan';
    public const FIELD_KETERANGAN = 'keterangan';
    public const FIELD_IS_DELETED = 'is_deleted';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_BARANG_ID;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        self::FIELD_BARANG_ID,
        self::FIELD_BARANG_KODE,
        self::FIELD_NAMA_BARANG,
        self::FIELD_HARGA_AWAL_BARANG,
        self::FIELD_STOK,
        self::FIELD_SATUAN,
        self::FIELD_KETERANGAN,
        self::FIELD_IS_DELETED,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_STOK => 'integer',
        self::FIELD_HARGA_AWAL_BARANG => 'decimal:2',
        self::FIELD_IS_DELETED => 'boolean',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    public function scopeNotDeleted($query)
    {
        return $query->where(self::FIELD_IS_DELETED, 0);
    }

    public function barangToko()
    {
        return $this->hasMany(BarangToko::class, BarangToko::FIELD_BARANG_ID, self::FIELD_BARANG_ID);
    }

    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, Pengiriman::FIELD_BARANG_ID, self::FIELD_BARANG_ID);
    }

    public function retur()
    {
        return $this->hasMany(Retur::class, Retur::FIELD_BARANG_ID, self::FIELD_BARANG_ID);
    }

    public function pemesanan()
    {
        return $this->hasMany(Pemesanan::class, Pemesanan::FIELD_BARANG_ID, self::FIELD_BARANG_ID);
    }

    public function toko()
    {
        return $this->belongsToMany(Toko::class, BarangToko::TABLE, self::FIELD_BARANG_ID, Toko::FIELD_TOKO_ID)
                    ->withPivot(BarangToko::FIELD_BARANG_TOKO_ID, BarangToko::FIELD_HARGA_BARANG_TOKO);
    }
    
    public static function generateBarangKode()
    {
        $lastBarang = self::orderBy(self::FIELD_BARANG_KODE, 'desc')->first();
        
        if (!$lastBarang) {
            return 'BRG001';
        }
        
        $lastKode = $lastBarang->{self::FIELD_BARANG_KODE};
        $prefix = 'BRG';
        
        if (!preg_match('/^BRG\d+$/', $lastKode)) {
            return 'BRG001';
        }
        
        $numPart = substr($lastKode, strlen($prefix));
        $nextNum = intval($numPart) + 1;
        
        return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
}