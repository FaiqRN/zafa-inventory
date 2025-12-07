<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangStok extends Model
{
    use HasFactory;

    const TABLE = 'barang_stok';
    const FIELD_ID = 'id';
    const FIELD_BARANG_ID = 'barang_id';
    const FIELD_TANGGAL_STOCK_BARANG = 'tanggal_stock_barang';
    const FIELD_STOK = 'stok';
    const FIELD_SISA_STOK = 'sisa_stok';
    const FIELD_STOK_AWAL = 'stok_awal';
    const FIELD_CATATAN = 'catatan';
    const FIELD_CREATED_AT = 'created_at';
    const FIELD_UPDATED_AT = 'updated_at';
    const FIELD_USER_CREATE = 'user_create';
    const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_ID;
    public $timestamps = true;

    protected $fillable = [
        self::FIELD_BARANG_ID,
        self::FIELD_TANGGAL_STOCK_BARANG,
        self::FIELD_STOK,
        self::FIELD_SISA_STOK,
        self::FIELD_STOK_AWAL,
        self::FIELD_CATATAN,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_TANGGAL_STOCK_BARANG => 'date',
        self::FIELD_STOK => 'integer',
        self::FIELD_SISA_STOK => 'integer',
        self::FIELD_STOK_AWAL => 'integer',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    /**
     * Relationship: Barang Stok belongs to Barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, self::FIELD_BARANG_ID, Barang::FIELD_BARANG_ID);
    }

    /**
     * Scope: Filter batch dengan sisa_stok > 0
     */
    public function scopeAvailable($query)
    {
        return $query->where(self::FIELD_SISA_STOK, '>', 0);
    }

    /**
     * Scope: Order by FIFO (tanggal terlama dulu, kemudian ID terkecil)
     */
    public function scopeFifo($query)
    {
        return $query->orderBy(self::FIELD_TANGGAL_STOCK_BARANG, 'asc')
                     ->orderBy(self::FIELD_ID, 'asc');
    }

    /**
     * Scope: Filter by barang_id
     */
    public function scopeByBarang($query, $barangId)
    {
        return $query->where(self::FIELD_BARANG_ID, $barangId);
    }
}
