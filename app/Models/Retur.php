<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retur extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'retur';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'retur_id';

    /**
     * Menentukan apakah primary key auto-increment.
     *
     * @var bool
     */
    public $incrementing = true;

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
        'tanggal_retur',
        'harga_awal_barang',
        'jumlah_kirim',
        'jumlah_retur',
        'total_terjual',
        'hasil',
        'kondisi',
        'keterangan'
    ];

    /**
     * Atribut yang harus dikonversi ke tipe data tertentu.
     *
     * @var array
     */
    protected $casts = [
        'tanggal_pengiriman' => 'date',
        'tanggal_retur' => 'date',
        'harga_awal_barang' => 'decimal:2',
        'jumlah_kirim' => 'integer',
        'jumlah_retur' => 'integer',
        'total_terjual' => 'integer',
        'hasil' => 'decimal:2'
    ];

    /**
     * Relasi ke tabel pengiriman.
     */
    public function pengiriman()
    {
        return $this->belongsTo(Pengiriman::class, 'pengiriman_id', 'pengiriman_id');
    }

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
     * Mendapatkan nilai retur (jumlah_retur * harga_awal_barang).
     */
    public function getNilaiReturAttribute()
    {
        return $this->jumlah_retur * $this->harga_awal_barang;
    }

    /**
     * Mendapatkan persentase barang retur dari total pengiriman.
     */
    public function getPersentaseReturAttribute()
    {
        if ($this->jumlah_kirim == 0) {
            return 0;
        }
        
        return round(($this->jumlah_retur / $this->jumlah_kirim) * 100, 2);
    }
}