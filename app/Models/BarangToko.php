<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangToko extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'barang_toko';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'barang_toko_id';

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
        'barang_toko_id',
        'toko_id',
        'barang_id',
        'harga_barang_toko'
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
}