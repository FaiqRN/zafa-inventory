<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokoLaporan extends Model
{
    protected $table = 'toko_laporan';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = [
        'toko_id', 'periode', 'bulan', 'tahun', 'catatan'
    ];
    
    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id', 'toko_id');
    }
}