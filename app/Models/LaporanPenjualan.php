<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanPenjualan extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'laporan_penjualan';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'laporan_id';

    /**
     * Tipe primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'laporan_id',
        'periode_awal',
        'periode_akhir',
        'total_penjualan_toko',
        'total_barang_terjual_toko',
        'total_barang_retur_toko',
        'total_penjualan_pemesanan',
        'total_barang_terjual_pemesanan',
        'total_penjualan_keseluruhan',
        'total_barang_terjual_keseluruhan',
        'total_barang_retur_keseluruhan',
        'jumlah_toko_aktif',
        'jumlah_pemesanan',
        'barang_terlaris',
        'toko_terlaris',
        'catatan_laporan',
        'dibuat_oleh'
    ];

    /**
     * Atribut yang harus dikonversi ke tipe data tertentu.
     *
     * @var array
     */
    protected $casts = [
        'periode_awal' => 'date',
        'periode_akhir' => 'date',
        'total_penjualan_toko' => 'decimal:2',
        'total_barang_terjual_toko' => 'integer',
        'total_barang_retur_toko' => 'integer',
        'total_penjualan_pemesanan' => 'decimal:2',
        'total_barang_terjual_pemesanan' => 'integer',
        'total_penjualan_keseluruhan' => 'decimal:2',
        'total_barang_terjual_keseluruhan' => 'integer',
        'total_barang_retur_keseluruhan' => 'integer',
        'jumlah_toko_aktif' => 'integer',
        'jumlah_pemesanan' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relasi ke barang terlaris.
     */
    public function barangTerlaris()
    {
        return $this->belongsTo(Barang::class, 'barang_terlaris', 'barang_id');
    }

    /**
     * Relasi ke toko terlaris.
     */
    public function tokoTerlaris()
    {
        return $this->belongsTo(Toko::class, 'toko_terlaris', 'toko_id');
    }

    /**
     * Mendapatkan durasi periode dalam hari.
     */
    public function getDurasiPeriodeAttribute()
    {
        return $this->periode_awal->diffInDays($this->periode_akhir) + 1;
    }

    /**
     * Mendapatkan rata-rata penjualan per hari.
     */
    public function getRataPenjualanPerHariAttribute()
    {
        if ($this->durasi_periode == 0) {
            return 0;
        }
        
        return round($this->total_penjualan_keseluruhan / $this->durasi_periode, 2);
    }

    /**
     * Mendapatkan rata-rata barang terjual per hari.
     */
    public function getRataBarangTerjualPerHariAttribute()
    {
        if ($this->durasi_periode == 0) {
            return 0;
        }
        
        return round($this->total_barang_terjual_keseluruhan / $this->durasi_periode, 2);
    }

    /**
     * Mendapatkan persentase penjualan toko dari total.
     */
    public function getPersentasePenjualanTokoAttribute()
    {
        if ($this->total_penjualan_keseluruhan == 0) {
            return 0;
        }
        
        return round(($this->total_penjualan_toko / $this->total_penjualan_keseluruhan) * 100, 2);
    }

    /**
     * Mendapatkan persentase penjualan pemesanan dari total.
     */
    public function getPersentasePenjualanPemesananAttribute()
    {
        if ($this->total_penjualan_keseluruhan == 0) {
            return 0;
        }
        
        return round(($this->total_penjualan_pemesanan / $this->total_penjualan_keseluruhan) * 100, 2);
    }
}