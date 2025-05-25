<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Toko extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'toko';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'toko_id';

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
        'toko_id',
        'nama_toko',
        'pemilik',
        'alamat',
        'wilayah_kecamatan',
        'wilayah_kelurahan',
        'wilayah_kota_kabupaten',
        'nomer_telpon'
    ];

    /**
     * Relasi ke tabel barang_toko.
     */
    public function barangToko()
    {
        return $this->hasMany(BarangToko::class, 'toko_id', 'toko_id');
    }

    /**
     * Relasi ke tabel pengiriman.
     */
    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, 'toko_id', 'toko_id');
    }

    /**
     * Relasi ke tabel retur.
     */
    public function retur()
    {
        return $this->hasMany(Retur::class, 'toko_id', 'toko_id');
    }
    /**
     * Relasi ke tabel barang melalui barang_toko.
     */
    public function barang()
    {
        return $this->belongsToMany(Barang::class, 'barang_toko', 'toko_id', 'barang_id')
                    ->withPivot('barang_toko_id', 'harga_barang_toko');
    }
}