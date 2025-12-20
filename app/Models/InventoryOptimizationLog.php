<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryOptimizationLog extends Model
{
    use HasFactory;

    public const TABLE = 'inventory_optimization_logs';
    public const FIELD_ID = 'id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_ACTION_TYPE = 'action_type';
    public const FIELD_OLD_QUANTITY = 'old_quantity';
    public const FIELD_NEW_QUANTITY = 'new_quantity';
    public const FIELD_METADATA = 'metadata';
    public const FIELD_PERFORMED_BY = 'performed_by';
    public const FIELD_PERFORMED_AT = 'performed_at';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_TOKO_ID,
        self::FIELD_BARANG_ID,
        self::FIELD_ACTION_TYPE,
        self::FIELD_OLD_QUANTITY,
        self::FIELD_NEW_QUANTITY,
        self::FIELD_METADATA,
        self::FIELD_PERFORMED_BY,
        self::FIELD_PERFORMED_AT,
    ];

    protected $casts = [
        self::FIELD_METADATA => 'array',
        self::FIELD_PERFORMED_AT => 'datetime',
        self::FIELD_OLD_QUANTITY => 'integer',
        self::FIELD_NEW_QUANTITY => 'integer',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, self::FIELD_BARANG_ID, Barang::FIELD_BARANG_ID);
    }
}
