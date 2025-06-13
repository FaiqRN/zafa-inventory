<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryOptimizationLog extends Model
{
    use HasFactory;

    protected $table = 'inventory_optimization_logs';

    protected $fillable = [
        'toko_id',
        'barang_id',
        'action_type',
        'old_quantity',
        'new_quantity',
        'metadata',
        'performed_by',
        'performed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'performed_at' => 'datetime',
        'old_quantity' => 'integer',
        'new_quantity' => 'integer'
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id', 'toko_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'barang_id');
    }
}