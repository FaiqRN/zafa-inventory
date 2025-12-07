<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemesanan extends Model
{
    use HasFactory;

    public const TABLE = 'pemesanan';
    public const FIELD_PEMESANAN_ID = 'pemesanan_id';
    public const FIELD_NOMOR_PEMESANAN = 'nomor_pemesanan';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_NAMA_PEMESAN = 'nama_pemesan';
    public const FIELD_TANGGAL_PEMESANAN = 'tanggal_pemesanan';
    public const FIELD_TANGGAL_DIPROSES = 'tanggal_diproses';
    public const FIELD_TANGGAL_DIKIRIM = 'tanggal_dikirim';
    public const FIELD_TANGGAL_SELESAI = 'tanggal_selesai';
    public const FIELD_ALAMAT_PEMESAN = 'alamat_pemesan';
    public const FIELD_JUMLAH_PESANAN = 'jumlah_pesanan';
    public const FIELD_TOTAL = 'total';
    public const FIELD_PEMESANAN_DARI = 'pemesanan_dari';
    public const FIELD_METODE_PEMBAYARAN = 'metode_pembayaran';
    public const FIELD_STATUS_PEMESANAN = 'status_pemesanan';
    public const FIELD_NO_TELP_PEMESAN = 'no_telp_pemesan';
    public const FIELD_EMAIL_PEMESAN = 'email_pemesan';
    public const FIELD_CATATAN_PEMESANAN = 'catatan_pemesanan';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_PEMESANAN_ID;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        self::FIELD_PEMESANAN_ID,
        self::FIELD_NOMOR_PEMESANAN,
        self::FIELD_BARANG_ID,
        self::FIELD_NAMA_PEMESAN,
        self::FIELD_TANGGAL_PEMESANAN,
        self::FIELD_TANGGAL_DIPROSES,
        self::FIELD_TANGGAL_DIKIRIM,
        self::FIELD_TANGGAL_SELESAI,
        self::FIELD_ALAMAT_PEMESAN,
        self::FIELD_JUMLAH_PESANAN,
        self::FIELD_TOTAL,
        self::FIELD_PEMESANAN_DARI,
        self::FIELD_METODE_PEMBAYARAN,
        self::FIELD_STATUS_PEMESANAN,
        self::FIELD_NO_TELP_PEMESAN,
        self::FIELD_EMAIL_PEMESAN,
        self::FIELD_CATATAN_PEMESANAN,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_TANGGAL_PEMESANAN => 'date',
        self::FIELD_TANGGAL_DIPROSES => 'date',
        self::FIELD_TANGGAL_DIKIRIM => 'date',
        self::FIELD_TANGGAL_SELESAI => 'date',
        self::FIELD_JUMLAH_PESANAN => 'integer',
        self::FIELD_TOTAL => 'decimal:2',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, self::FIELD_BARANG_ID, Barang::FIELD_BARANG_ID);
    }

    public function getHargaSatuanAttribute()
    {
        if ($this->{self::FIELD_JUMLAH_PESANAN} == 0) {
            return 0;
        }
        
        return round($this->{self::FIELD_TOTAL} / $this->{self::FIELD_JUMLAH_PESANAN}, 2);
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => '<span class="badge badge-warning">Menunggu</span>',
            'diproses' => '<span class="badge badge-info">Diproses</span>',
            'dikirim' => '<span class="badge badge-primary">Dikirim</span>',
            'selesai' => '<span class="badge badge-success">Selesai</span>',
            'dibatalkan' => '<span class="badge badge-danger">Dibatalkan</span>',
        ];
        
        return $labels[$this->{self::FIELD_STATUS_PEMESANAN}] ?? '<span class="badge badge-secondary">Tidak Diketahui</span>';
    }
}
