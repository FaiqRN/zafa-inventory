<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    public const TABLE = 'data_customer';
    public const FIELD_CUSTOMER_ID = 'customer_id';
    public const FIELD_NAMA = 'nama';
    public const FIELD_USIA = 'usia';
    public const FIELD_GENDER = 'gender';
    public const FIELD_ALAMAT = 'alamat';
    public const FIELD_EMAIL = 'email';
    public const FIELD_NO_TLP = 'no_tlp';
    public const FIELD_PEMESANAN_ID = 'pemesanan_id';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';
    public const FIELD_DELETED_AT = 'deleted_at';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_CUSTOMER_ID;
    
    protected $fillable = [
        self::FIELD_NAMA,
        self::FIELD_USIA,
        self::FIELD_GENDER,
        self::FIELD_ALAMAT,
        self::FIELD_EMAIL,
        self::FIELD_NO_TLP,
        self::FIELD_PEMESANAN_ID,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_USIA => 'integer',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
        self::FIELD_DELETED_AT => 'datetime',
    ];

    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, self::FIELD_PEMESANAN_ID, Pemesanan::FIELD_PEMESANAN_ID);
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(self::FIELD_NAMA, 'LIKE', "%{$search}%")
                        ->orWhere(self::FIELD_EMAIL, 'LIKE', "%{$search}%")
                        ->orWhere(self::FIELD_NO_TLP, 'LIKE', "%{$search}%");
        }
        return $query;
    }
    
    public static function emailExists($email, $excludeId = null)
    {
        $query = self::where(self::FIELD_EMAIL, $email);
        
        if ($excludeId) {
            $query->where(self::FIELD_CUSTOMER_ID, '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    public function getSourceLabel()
    {
        if ($this->{self::FIELD_PEMESANAN_ID}) {
            return 'Pemesanan';
        }
        return 'Input Manual';
    }
}