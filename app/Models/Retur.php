<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retur extends Model
{
    use HasFactory;

    public const TABLE = 'retur';
    public const FIELD_RETUR_ID = 'retur_id';
    public const FIELD_PENGIRIMAN_ID = 'pengiriman_id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_NOMER_PENGIRIMAN = 'nomer_pengiriman';
    public const FIELD_TANGGAL_PENGIRIMAN = 'tanggal_pengiriman';
    public const FIELD_TANGGAL_RETUR = 'tanggal_retur';
    public const FIELD_HARGA_AWAL_BARANG = 'harga_awal_barang';
    public const FIELD_JUMLAH_KIRIM = 'jumlah_kirim';
    public const FIELD_JUMLAH_RETUR = 'jumlah_retur';
    public const FIELD_TOTAL_TERJUAL = 'total_terjual';
    public const FIELD_HASIL = 'hasil';
    public const FIELD_KONDISI = 'kondisi';
    public const FIELD_KETERANGAN = 'keterangan';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_RETUR_ID;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        self::FIELD_PENGIRIMAN_ID,
        self::FIELD_TOKO_ID,
        self::FIELD_BARANG_ID,
        self::FIELD_NOMER_PENGIRIMAN,
        self::FIELD_TANGGAL_PENGIRIMAN,
        self::FIELD_TANGGAL_RETUR,
        self::FIELD_HARGA_AWAL_BARANG,
        self::FIELD_JUMLAH_KIRIM,
        self::FIELD_JUMLAH_RETUR,
        self::FIELD_TOTAL_TERJUAL,
        self::FIELD_HASIL,
        self::FIELD_KONDISI,
        self::FIELD_KETERANGAN,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_TANGGAL_PENGIRIMAN => 'date',
        self::FIELD_TANGGAL_RETUR => 'date',
        self::FIELD_HARGA_AWAL_BARANG => 'decimal:2',
        self::FIELD_JUMLAH_KIRIM => 'integer',
        self::FIELD_JUMLAH_RETUR => 'integer',
        self::FIELD_TOTAL_TERJUAL => 'integer',
        self::FIELD_HASIL => 'decimal:2',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    public function pengiriman()
    {
        return $this->belongsTo(Pengiriman::class, self::FIELD_PENGIRIMAN_ID, Pengiriman::FIELD_PENGIRIMAN_ID);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, self::FIELD_BARANG_ID, Barang::FIELD_BARANG_ID);
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }

    public function getNilaiReturAttribute()
    {
        return $this->{self::FIELD_JUMLAH_RETUR} * $this->{self::FIELD_HARGA_AWAL_BARANG};
    }

    public function getPersentaseReturAttribute()
    {
        if ($this->{self::FIELD_JUMLAH_KIRIM} == 0) {
            return 0;
        }
        
        return round(($this->{self::FIELD_JUMLAH_RETUR} / $this->{self::FIELD_JUMLAH_KIRIM}) * 100, 2);
    }
}
