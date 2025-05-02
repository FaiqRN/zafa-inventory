<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengiriman extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'pengiriman';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'pengiriman_id';

    /**
     * Tipe primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Menentukan apakah model menggunakan timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'pengiriman_id',
        'toko_id',
        'barang_id',
        'nomer_pengiriman',
        'tanggal_pengiriman',
        'jumlah_kirim',
        'status'
    ];

    /**
     * Atribut yang harus dikonversi ke tipe data tertentu.
     *
     * @var array
     */
    protected $casts = [
        'tanggal_pengiriman' => 'date',
        'jumlah_kirim' => 'integer'
    ];

    /**
     * Relasi ke tabel barang.
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'barang_id');
    }

    /**
     * Relasi ke tabel toko.
     */
    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id', 'toko_id');
    }

    /**
     * Relasi ke tabel retur.
     */
    public function retur()
    {
        return $this->hasMany(Retur::class, 'pengiriman_id', 'pengiriman_id');
    }

    /**
     * Mendapatkan harga barang di toko untuk pengiriman ini.
     */
    public function getHargaBarangTokoAttribute()
    {
        $barangToko = BarangToko::where('toko_id', $this->toko_id)
                                ->where('barang_id', $this->barang_id)
                                ->first();
        
        return $barangToko ? $barangToko->harga_barang_toko : 0;
    }

    /**
     * Menghitung total nilai pengiriman.
     */
    public function getTotalNilaiAttribute()
    {
        return $this->jumlah_kirim * $this->harga_barang_toko;
    }

    /**
     * Mendapatkan total jumlah retur untuk pengiriman ini.
     */
    public function getTotalReturAttribute()
    {
        return $this->retur()->sum('jumlah_retur');
    }

    /**
     * Mendapatkan jumlah barang terjual (jumlah kirim - jumlah retur).
     */
    public function getJumlahTerjualAttribute()
    {
        return $this->jumlah_kirim - $this->total_retur;
    }
}