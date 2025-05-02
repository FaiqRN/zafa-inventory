<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemesanan extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'pemesanan';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'pemesanan_id';

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
        'pemesanan_id',
        'barang_id',
        'nama_pemesan',
        'tanggal_pemesanan',
        'alamat_pemesan',
        'jumlah_pesanan',
        'total',
        'pemesanan_dari',
        'metode_pembayaran',
        'status_pemesanan',
        'no_telp_pemesan',
        'email_pemesan',
        'catatan_pemesanan'
    ];

    /**
     * Atribut yang harus dikonversi ke tipe data tertentu.
     *
     * @var array
     */
    protected $casts = [
        'tanggal_pemesanan' => 'date',
        'jumlah_pesanan' => 'integer',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relasi ke tabel barang.
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'barang_id');
    }

    /**
     * Mendapatkan harga satuan (total / jumlah_pesanan).
     */
    public function getHargaSatuanAttribute()
    {
        if ($this->jumlah_pesanan == 0) {
            return 0;
        }
        
        return round($this->total / $this->jumlah_pesanan, 2);
    }

    /**
     * Cek apakah pesanan telah selesai.
     */
    public function getIsSelesaiAttribute()
    {
        return $this->status_pemesanan === 'selesai';
    }

    /**
     * Cek apakah pesanan sedang diproses.
     */
    public function getIsProsesAttribute()
    {
        return $this->status_pemesanan === 'diproses' || $this->status_pemesanan === 'dikirim';
    }

    /**
     * Cek apakah pesanan dibatalkan.
     */
    public function getIsBatalAttribute()
    {
        return $this->status_pemesanan === 'dibatalkan';
    }

    /**
     * Cek apakah pesanan masih pending.
     */
    public function getIsPendingAttribute()
    {
        return $this->status_pemesanan === 'pending';
    }
}