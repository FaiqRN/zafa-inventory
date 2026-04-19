<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryRekomendasi extends Model
{
    use HasFactory;

    public const TABLE = 'inventory_rekomendasi';
    public const FIELD_ID = 'id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_S_DIPAKAI = 's_dipakai';
    public const FIELD_S_DARI_OVERRIDE = 's_dari_override';
    public const FIELD_H_DIPAKAI = 'h_dipakai';
    public const FIELD_Z_DIPAKAI = 'z_dipakai';
    public const FIELD_SERVICE_LEVEL_DIPAKAI = 'service_level_dipakai';
    public const FIELD_HARI_OBSERVASI = 'hari_observasi';
    public const FIELD_EOQ_RESULT = 'eoq_result';
    public const FIELD_FREKUENSI_PER_TAHUN = 'frekuensi_per_tahun';
    public const FIELD_INTERVAL_EOQ_HARI = 'interval_eoq_hari';
    public const FIELD_SS_RESULT = 'ss_result';
    public const FIELD_ROP_RESULT = 'rop_result';
    public const FIELD_SHELF_LIFE_DAYS = 'shelf_life_days';
    public const FIELD_BATAS_AMAN_HARI = 'batas_aman_hari';
    public const FIELD_SHELF_LIFE_FLAG = 'shelf_life_flag';
    public const FIELD_INTERVAL_KIRIM_HARI = 'interval_kirim_hari';
    public const FIELD_AVG_JUAL_HARIAN = 'avg_jual_harian';
    public const FIELD_Q_KIRIM_RESULT = 'q_kirim_result';
    public const FIELD_STOK_AKTUAL = 'stok_aktual';
    public const FIELD_IS_BELOW_ROP = 'is_below_rop';
    public const FIELD_TOTAL_KIRIM_HISTORIS = 'total_kirim_historis';
    public const FIELD_TOTAL_RETUR_HISTORIS = 'total_retur_historis';
    public const FIELD_PENJUALAN_AKTUAL = 'penjualan_aktual';
    public const FIELD_CALCULATED_AT = 'calculated_at';
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
        self::FIELD_S_DIPAKAI,
        self::FIELD_S_DARI_OVERRIDE,
        self::FIELD_H_DIPAKAI,
        self::FIELD_Z_DIPAKAI,
        self::FIELD_SERVICE_LEVEL_DIPAKAI,
        self::FIELD_HARI_OBSERVASI,
        self::FIELD_EOQ_RESULT,
        self::FIELD_FREKUENSI_PER_TAHUN,
        self::FIELD_INTERVAL_EOQ_HARI,
        self::FIELD_SS_RESULT,
        self::FIELD_ROP_RESULT,
        self::FIELD_SHELF_LIFE_DAYS,
        self::FIELD_BATAS_AMAN_HARI,
        self::FIELD_SHELF_LIFE_FLAG,
        self::FIELD_INTERVAL_KIRIM_HARI,
        self::FIELD_AVG_JUAL_HARIAN,
        self::FIELD_Q_KIRIM_RESULT,
        self::FIELD_STOK_AKTUAL,
        self::FIELD_IS_BELOW_ROP,
        self::FIELD_TOTAL_KIRIM_HISTORIS,
        self::FIELD_TOTAL_RETUR_HISTORIS,
        self::FIELD_PENJUALAN_AKTUAL,
        self::FIELD_CALCULATED_AT,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_S_DIPAKAI => 'decimal:2',
        self::FIELD_S_DARI_OVERRIDE => 'boolean',
        self::FIELD_H_DIPAKAI => 'decimal:2',
        self::FIELD_Z_DIPAKAI => 'decimal:4',
        self::FIELD_SERVICE_LEVEL_DIPAKAI => 'decimal:2',
        self::FIELD_HARI_OBSERVASI => 'integer',
        self::FIELD_EOQ_RESULT => 'integer',
        self::FIELD_FREKUENSI_PER_TAHUN => 'integer',
        self::FIELD_INTERVAL_EOQ_HARI => 'decimal:2',
        self::FIELD_SS_RESULT => 'integer',
        self::FIELD_ROP_RESULT => 'integer',
        self::FIELD_SHELF_LIFE_DAYS => 'integer',
        self::FIELD_BATAS_AMAN_HARI => 'integer',
        self::FIELD_SHELF_LIFE_FLAG => 'boolean',
        self::FIELD_INTERVAL_KIRIM_HARI => 'decimal:2',
        self::FIELD_AVG_JUAL_HARIAN => 'decimal:4',
        self::FIELD_Q_KIRIM_RESULT => 'integer',
        self::FIELD_STOK_AKTUAL => 'integer',
        self::FIELD_IS_BELOW_ROP => 'boolean',
        self::FIELD_TOTAL_KIRIM_HISTORIS => 'integer',
        self::FIELD_TOTAL_RETUR_HISTORIS => 'integer',
        self::FIELD_PENJUALAN_AKTUAL => 'integer',
        self::FIELD_CALCULATED_AT => 'datetime',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, self::FIELD_BARANG_ID, Barang::FIELD_BARANG_ID);
    }
}
