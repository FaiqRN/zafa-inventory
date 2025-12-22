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
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_BARANG_ID;
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    /**
     * Boot method untuk auto-generate barang_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{self::FIELD_BARANG_ID})) {
                $model->{self::FIELD_BARANG_ID} = self::generateBarangId();
            }
        });
    }

    /**
     * Generate barang_id dengan format BRG0000001
     *
     * @return string
     */
    public static function generateBarangId(): string
    {
        $prefix = 'BRG';
        $padLength = 7; // Total 10 karakter: BRG + 7 digit

        $lastBarang = self::where(self::FIELD_BARANG_ID, 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(' . self::FIELD_BARANG_ID . ', 4) AS UNSIGNED) DESC')
            ->first();

        if (!$lastBarang) {
            return $prefix . str_pad('1', $padLength, '0', STR_PAD_LEFT);
        }

        $lastId = $lastBarang->{self::FIELD_BARANG_ID};
        $lastNumber = (int) substr($lastId, strlen($prefix));
        $nextNumber = $lastNumber + 1;

        return $prefix . str_pad($nextNumber, $padLength, '0', STR_PAD_LEFT);
    }

    protected $fillable = [
        self::FIELD_BARANG_ID,
        self::FIELD_BARANG_KODE,
        self::FIELD_NAMA_BARANG,
        self::FIELD_HARGA_AWAL_BARANG,
        self::FIELD_STOK,
        self::FIELD_SATUAN,
        self::FIELD_KETERANGAN,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_STOK => 'integer',
        self::FIELD_HARGA_AWAL_BARANG => 'decimal:2',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

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

    public function barangStok()
    {
        return $this->hasMany(BarangStok::class, BarangStok::FIELD_BARANG_ID, self::FIELD_BARANG_ID);
    }

    public function toko()
    {
        return $this->belongsToMany(Toko::class, BarangToko::TABLE, self::FIELD_BARANG_ID, Toko::FIELD_TOKO_ID)
                    ->withPivot(BarangToko::FIELD_BARANG_TOKO_ID, BarangToko::FIELD_HARGA_BARANG_TOKO);
    }
    
    public static function generateBarangKode()
    {
        // Get last barang ordered by kode
        $lastBarang = self::orderBy(self::FIELD_BARANG_KODE, 'desc')
                          ->first();
        
        if (!$lastBarang) {
            return 'BRG1';
        }
        
        $lastKode = $lastBarang->{self::FIELD_BARANG_KODE};
        $prefix = 'BRG';
        
        if (!preg_match('/^BRG\d+$/', $lastKode)) {
            return 'BRG1';
        }
        
        $numPart = substr($lastKode, strlen($prefix));
        $nextNum = intval($numPart) + 1;
        
        return $prefix . $nextNum;
    }

    /**
     * Get available stock dynamically
     * This uses BarangHelper to calculate real-time stock
     *
     * @return int
     */
    public function getAvailableStockAttribute()
    {
        // Avoid circular dependency by checking if helper exists
        if (class_exists(\App\Helpers\MasterData\barang\BarangHelper::class)) {
            return \App\Helpers\MasterData\barang\BarangHelper::calculateAvailableStock($this->barang_id);
        }
        
        return $this->stok ?? 0;
    }

    /**
     * Get stock status (sufficient, low_stock, out_of_stock)
     *
     * @return string
     */
    public function getStockStatusAttribute()
    {
        $availableStock = $this->available_stock;
        $baseStock = $this->stok ?? 0;
        
        if ($availableStock <= 0) {
            return 'out_of_stock';
        }
        
        $lowStockThreshold = max(10, $baseStock * 0.2);
        
        if ($availableStock <= $lowStockThreshold) {
            return 'low_stock';
        }
        
        return 'sufficient';
    }

    /**
     * Get detailed stock breakdown
     *
     * @return array
     */
    public function getStockDetailsAttribute()
    {
        if (class_exists(\App\Helpers\MasterData\barang\BarangHelper::class)) {
            return \App\Helpers\MasterData\barang\BarangHelper::getStockDetails($this->barang_id);
        }
        
        return [
            'base_stock' => $this->stok ?? 0,
            'pemesanan_out' => 0,
            'pengiriman_out' => 0,
            'retur_in' => 0,
            'available_stock' => $this->stok ?? 0,
            'stock_status' => 'sufficient'
        ];
    }
}