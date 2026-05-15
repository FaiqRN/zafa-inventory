<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerCfSimilarity extends Model
{
    use HasFactory;

    public const TABLE = 'partner_cf_similarity';
    public const FIELD_ID = 'id';
    public const FIELD_PERIOD_START = 'period_start';
    public const FIELD_PERIOD_END = 'period_end';
    public const FIELD_TOKO_ID_A = 'toko_id_a';
    public const FIELD_TOKO_ID_B = 'toko_id_b';
    public const FIELD_SIM_TOTAL = 'sim_total';
    public const FIELD_SIM_LOCATION = 'sim_location';
    public const FIELD_SIM_DISTRICT = 'sim_district';
    public const FIELD_SIM_PATTERN = 'sim_pattern';
    public const FIELD_WEIGHTS_USED = 'weights_used';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_PERIOD_START,
        self::FIELD_PERIOD_END,
        self::FIELD_TOKO_ID_A,
        self::FIELD_TOKO_ID_B,
        self::FIELD_SIM_TOTAL,
        self::FIELD_SIM_LOCATION,
        self::FIELD_SIM_DISTRICT,
        self::FIELD_SIM_PATTERN,
        self::FIELD_WEIGHTS_USED,
    ];

    protected $casts = [
        self::FIELD_PERIOD_START => 'date',
        self::FIELD_PERIOD_END => 'date',
        self::FIELD_SIM_TOTAL => 'decimal:8',
        self::FIELD_SIM_LOCATION => 'decimal:8',
        self::FIELD_SIM_DISTRICT => 'decimal:8',
        self::FIELD_SIM_PATTERN => 'decimal:8',
        self::FIELD_WEIGHTS_USED => 'array',
    ];

    public function tokoA()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID_A, Toko::FIELD_TOKO_ID);
    }

    public function tokoB()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID_B, Toko::FIELD_TOKO_ID);
    }
}