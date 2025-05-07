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
     * Menentukan apakah primary key auto-increment.
     *
     * @var bool
     */
    public $incrementing = false;

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
        'tanggal_diproses',
        'tanggal_dikirim',
        'tanggal_selesai',
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
        'tanggal_diproses' => 'date',
        'tanggal_dikirim' => 'date',
        'tanggal_selesai' => 'date',
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
     * Mendapatkan status dengan format yang lebih baik.
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => '<span class="badge badge-warning">Menunggu</span>',
            'diproses' => '<span class="badge badge-info">Diproses</span>',
            'dikirim' => '<span class="badge badge-primary">Dikirim</span>',
            'selesai' => '<span class="badge badge-success">Selesai</span>',
            'dibatalkan' => '<span class="badge badge-danger">Dibatalkan</span>'
        ];
        
        return $labels[$this->status_pemesanan] ?? '<span class="badge badge-secondary">Tidak Diketahui</span>';
    }
}