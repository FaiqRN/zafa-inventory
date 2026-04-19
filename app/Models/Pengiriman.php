<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengiriman extends Model
{
    use HasFactory;

    public const TABLE = 'pengiriman';
    public const FIELD_PENGIRIMAN_ID = 'pengiriman_id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_NOMER_PENGIRIMAN = 'nomer_pengiriman';
    public const FIELD_TANGGAL_PENGIRIMAN = 'tanggal_pengiriman';
    public const FIELD_TANGGAL_TERIMA = 'tanggal_terima';
    public const FIELD_JUMLAH_KIRIM = 'jumlah_kirim';
    public const FIELD_STATUS = 'status';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_PENGIRIMAN_ID;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        self::FIELD_PENGIRIMAN_ID,
        self::FIELD_TOKO_ID,
        self::FIELD_BARANG_ID,
        self::FIELD_NOMER_PENGIRIMAN,
        self::FIELD_TANGGAL_PENGIRIMAN,
        self::FIELD_TANGGAL_TERIMA,
        self::FIELD_JUMLAH_KIRIM,
        self::FIELD_STATUS,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_TANGGAL_PENGIRIMAN => 'date',
        self::FIELD_TANGGAL_TERIMA => 'date',
        self::FIELD_JUMLAH_KIRIM => 'integer',
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

    public function retur()
    {
        return $this->hasMany(Retur::class, Retur::FIELD_PENGIRIMAN_ID, self::FIELD_PENGIRIMAN_ID);
    }

    public function barangToko()
    {
        return $this->hasOne(BarangToko::class, BarangToko::FIELD_BARANG_ID, self::FIELD_BARANG_ID)
            ->where(BarangToko::FIELD_TOKO_ID, $this->{self::FIELD_TOKO_ID});
    }

    public function getHargaBarangTokoAttribute()
    {
        if ($this->relationLoaded('barangToko')) {
            return $this->barangToko ? $this->barangToko->{BarangToko::FIELD_HARGA_BARANG_TOKO} : 0;
        }
        
        $barangToko = BarangToko::where(BarangToko::FIELD_TOKO_ID, $this->{self::FIELD_TOKO_ID})
                                ->where(BarangToko::FIELD_BARANG_ID, $this->{self::FIELD_BARANG_ID})
                                ->first();
        
        return $barangToko ? $barangToko->{BarangToko::FIELD_HARGA_BARANG_TOKO} : 0;
    }

    public function getTotalNilaiAttribute()
    {
        return $this->{self::FIELD_JUMLAH_KIRIM} * $this->harga_barang_toko;
    }

    public function getTotalReturAttribute()
    {
        return $this->retur()->sum(Retur::FIELD_JUMLAH_RETUR);
    }

    public function getJumlahTerjualAttribute()
    {
        return $this->{self::FIELD_JUMLAH_KIRIM} - $this->total_retur;
    }
}
