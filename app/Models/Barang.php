<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'barang';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'barang_id';

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
        'barang_id',
        'barang_kode',
        'nama_barang',
        'harga_awal_barang',
        'satuan',
        'keterangan',
        'is_deleted'
    ];

    /**
     * Scope untuk mengambil barang yang belum dihapus
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', 0);
    }

    /**
     * Relasi ke tabel barang_toko.
     */
    public function barangToko()
    {
        return $this->hasMany(BarangToko::class, 'barang_id', 'barang_id');
    }

    /**
     * Relasi ke tabel pengiriman.
     */
    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, 'barang_id', 'barang_id');
    }

    /**
     * Relasi ke tabel retur.
     */
    public function retur()
    {
        return $this->hasMany(Retur::class, 'barang_id', 'barang_id');
    }

    /**
     * Relasi ke tabel pemesanan.
     */
    public function pemesanan()
    {
        return $this->hasMany(Pemesanan::class, 'barang_id', 'barang_id');
    }

    /**
     * Relasi ke tabel toko melalui barang_toko.
     */
    public function toko()
    {
        return $this->belongsToMany(Toko::class, 'barang_toko', 'barang_id', 'toko_id')
                    ->withPivot('barang_toko_id', 'harga_barang_toko');
    }
    
    /**
     * Generate kode barang baru berdasarkan kode terakhir di database
     */
    public static function generateBarangKode()
    {
        $lastBarang = self::orderBy('barang_kode', 'desc')->first();
        
        if (!$lastBarang) {
            return 'BRG001';
        }
        
        $lastKode = $lastBarang->barang_kode;
        $prefix = 'BRG';
        
        // Jika kode tidak sesuai dengan format yang diharapkan, mulai dari BRG001
        if (!preg_match('/^BRG\d+$/', $lastKode)) {
            return 'BRG001';
        }
        
        $numPart = substr($lastKode, strlen($prefix));
        $nextNum = intval($numPart) + 1;
        
        // Padding dengan nol di depan sampai 3 digit
        return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
}