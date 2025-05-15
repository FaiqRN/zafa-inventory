<?php
// app/Models/PemesananLaporan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemesananLaporan extends Model
{
    use HasFactory;

    protected $table = 'pemesanan_laporan';
    
    protected $fillable = [
        'pemesanan_id',
        'periode',
        'bulan',
        'tahun',
        'catatan'
    ];
    
    // Relasi dengan pemesanan
    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, 'pemesanan_id', 'pemesanan_id');
    }
}