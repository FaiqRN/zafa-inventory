<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $table = 'follow_up';
    protected $primaryKey = 'follow_up_id';

    protected $fillable = [
        'pemesanan_id',
        'customer_id',
        'target_type',
        'message',
        'images',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'wablas_message_id',
        'wablas_response',
        'error_message',
        'phone_number',
        'customer_name',
        'customer_email',
        'source_channel'
    ];

    protected $casts = [
        'images' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'wablas_response' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relasi ke tabel pemesanan
     */
    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, 'pemesanan_id', 'pemesanan_id');
    }

    /**
     * Relasi ke tabel data_customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    /**
     * Get status label with badge
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => '<span class="badge badge-warning">Menunggu</span>',
            'sent' => '<span class="badge badge-info">Terkirim</span>',
            'delivered' => '<span class="badge badge-primary">Diterima</span>',
            'read' => '<span class="badge badge-success">Dibaca</span>',
            'failed' => '<span class="badge badge-danger">Gagal</span>'
        ];
        
        return $labels[$this->status] ?? '<span class="badge badge-secondary">Tidak Diketahui</span>';
    }

    /**
     * Get target type label
     */
    public function getTargetTypeLabelAttribute()
    {
        $labels = [
            'pelangganLama' => 'Pelanggan Lama',
            'pelangganBaru' => 'Pelanggan Baru',
            'pelangganTidakKembali' => 'Pelanggan Tidak Kembali',
            'keseluruhan' => 'Keseluruhan'
        ];
        
        return $labels[$this->target_type] ?? 'Tidak Diketahui';
    }

    /**
     * Get customer initial for avatar replacement
     */
    public function getCustomerInitialAttribute()
    {
        $words = explode(' ', $this->customer_name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->customer_name, 0, 2));
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter berdasarkan target type
     */
    public function scopeByTargetType($query, $targetType)
    {
        return $query->where('target_type', $targetType);
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sent_at', [$startDate, $endDate]);
    }
}