<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_customer';
    protected $primaryKey = 'customer_id';
    
    protected $fillable = [
        'nama',
        'usia',
        'gender',
        'alamat',
        'email',
        'no_tlp',
        'pemesanan_id',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Get the pemesanan associated with the customer
     */
    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, 'pemesanan_id', 'pemesanan_id');
    }

    /**
     * Scope a query to search by name or email
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('nama', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('no_tlp', 'LIKE', "%{$search}%");
        }
        return $query;
    }
    
    /**
     * Check if a customer with this email already exists
     */
    public static function emailExists($email, $excludeId = null)
    {
        $query = self::where('email', $email);
        
        if ($excludeId) {
            $query->where('customer_id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    /**
     * Generate source data label
     */
    public function getSourceLabel()
    {
        if ($this->pemesanan_id) {
            return 'Pemesanan';
        }
        return 'Input Manual';
    }
}