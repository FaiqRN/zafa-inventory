<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemesananLaporan extends Model
{
    protected $table = 'pemesanan_laporan';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = [
        'tipe', 'reference_id', 'catatan', 'periode', 'bulan', 'tahun'
    ];
}