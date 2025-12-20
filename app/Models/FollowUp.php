<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    public const TABLE = 'follow_up';
    public const FIELD_FOLLOW_UP_ID = 'follow_up_id';
    public const FIELD_PEMESANAN_ID = 'pemesanan_id';
    public const FIELD_CUSTOMER_ID = 'customer_id';
    public const FIELD_TARGET_TYPE = 'target_type';
    public const FIELD_MESSAGE = 'message';
    public const FIELD_IMAGES = 'images';
    public const FIELD_STATUS = 'status';
    public const FIELD_SENT_AT = 'sent_at';
    public const FIELD_DELIVERED_AT = 'delivered_at';
    public const FIELD_READ_AT = 'read_at';
    public const FIELD_WABLAS_MESSAGE_ID = 'wablas_message_id';
    public const FIELD_WABLAS_RESPONSE = 'wablas_response';
    public const FIELD_ERROR_MESSAGE = 'error_message';
    public const FIELD_PHONE_NUMBER = 'phone_number';
    public const FIELD_CUSTOMER_NAME = 'customer_name';
    public const FIELD_CUSTOMER_EMAIL = 'customer_email';
    public const FIELD_SOURCE_CHANNEL = 'source_channel';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_FOLLOW_UP_ID;

    protected $fillable = [
        self::FIELD_PEMESANAN_ID,
        self::FIELD_CUSTOMER_ID,
        self::FIELD_TARGET_TYPE,
        self::FIELD_MESSAGE,
        self::FIELD_IMAGES,
        self::FIELD_STATUS,
        self::FIELD_SENT_AT,
        self::FIELD_DELIVERED_AT,
        self::FIELD_READ_AT,
        self::FIELD_WABLAS_MESSAGE_ID,
        self::FIELD_WABLAS_RESPONSE,
        self::FIELD_ERROR_MESSAGE,
        self::FIELD_PHONE_NUMBER,
        self::FIELD_CUSTOMER_NAME,
        self::FIELD_CUSTOMER_EMAIL,
        self::FIELD_SOURCE_CHANNEL,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_IMAGES => 'array',
        self::FIELD_SENT_AT => 'datetime',
        self::FIELD_DELIVERED_AT => 'datetime',
        self::FIELD_READ_AT => 'datetime',
        self::FIELD_WABLAS_RESPONSE => 'array',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];

    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, self::FIELD_PEMESANAN_ID, Pemesanan::FIELD_PEMESANAN_ID);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, self::FIELD_CUSTOMER_ID, Customer::FIELD_CUSTOMER_ID);
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => '<span class="badge badge-warning">Menunggu</span>',
            'sent' => '<span class="badge badge-info">Terkirim</span>',
            'delivered' => '<span class="badge badge-primary">Diterima</span>',
            'read' => '<span class="badge badge-success">Dibaca</span>',
            'failed' => '<span class="badge badge-danger">Gagal</span>',
        ];
        
        return $labels[$this->{self::FIELD_STATUS}] ?? '<span class="badge badge-secondary">Tidak Diketahui</span>';
    }

    public function getTargetTypeLabelAttribute()
    {
        $labels = [
            'pelangganLama' => 'Pelanggan Lama',
            'pelangganBaru' => 'Pelanggan Baru',
            'pelangganTidakKembali' => 'Pelanggan Tidak Kembali',
            'keseluruhan' => 'Keseluruhan',
        ];
        
        return $labels[$this->{self::FIELD_TARGET_TYPE}] ?? 'Tidak Diketahui';
    }

    public function getCustomerInitialAttribute()
    {
        $words = explode(' ', $this->{self::FIELD_CUSTOMER_NAME});
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->{self::FIELD_CUSTOMER_NAME}, 0, 2));
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where(self::FIELD_STATUS, $status);
    }

    public function scopeByTargetType($query, $targetType)
    {
        return $query->where(self::FIELD_TARGET_TYPE, $targetType);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween(self::FIELD_SENT_AT, [$startDate, $endDate]);
    }
}
