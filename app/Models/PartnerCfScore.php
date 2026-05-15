<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerCfScore extends Model
{
    use HasFactory;

    public const TABLE = 'partner_cf_scores';
    public const FIELD_ID = 'id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_PERIOD_START = 'period_start';
    public const FIELD_PERIOD_END = 'period_end';
    public const FIELD_CF_USER_SCORE = 'cf_user_score';
    public const FIELD_CF_USER_AVG_SIMILARITY = 'cf_user_avg_similarity';
    public const FIELD_CF_USER_NEIGHBOR_COUNT = 'cf_user_neighbor_count';
    public const FIELD_CF_USER_TOP_NEIGHBORS = 'cf_user_top_neighbors';
    public const FIELD_CF_ITEM_SCORE = 'cf_item_score';
    public const FIELD_CF_ITEM_RAW_SCORE = 'cf_item_raw_score';
    public const FIELD_CF_ITEM_RELATION_SCORE = 'cf_item_relation_score';
    public const FIELD_CF_ITEM_DIVERSITY_FACTOR = 'cf_item_diversity_factor';
    public const FIELD_CF_ITEM_BALANCE_FACTOR = 'cf_item_balance_factor';
    public const FIELD_CF_ITEM_AVG_SALES_NORM = 'cf_item_avg_sales_norm';
    public const FIELD_CF_ITEM_ACTIVE_PRODUCTS = 'cf_item_active_products';
    public const FIELD_CF_ITEM_TOTAL_PRODUCTS = 'cf_item_total_products';
    public const FIELD_CF_SCORE = 'cf_score';
    public const FIELD_CF_BETA = 'cf_beta';
    public const FIELD_SIMILARITY_WEIGHTS = 'similarity_weights';
    public const FIELD_SIMILARITY_CACHE_KEY = 'similarity_cache_key';
    public const FIELD_CALCULATION_META = 'calculation_meta';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_TOKO_ID,
        self::FIELD_PERIOD_START,
        self::FIELD_PERIOD_END,
        self::FIELD_CF_USER_SCORE,
        self::FIELD_CF_USER_AVG_SIMILARITY,
        self::FIELD_CF_USER_NEIGHBOR_COUNT,
        self::FIELD_CF_USER_TOP_NEIGHBORS,
        self::FIELD_CF_ITEM_SCORE,
        self::FIELD_CF_ITEM_RAW_SCORE,
        self::FIELD_CF_ITEM_RELATION_SCORE,
        self::FIELD_CF_ITEM_DIVERSITY_FACTOR,
        self::FIELD_CF_ITEM_BALANCE_FACTOR,
        self::FIELD_CF_ITEM_AVG_SALES_NORM,
        self::FIELD_CF_ITEM_ACTIVE_PRODUCTS,
        self::FIELD_CF_ITEM_TOTAL_PRODUCTS,
        self::FIELD_CF_SCORE,
        self::FIELD_CF_BETA,
        self::FIELD_SIMILARITY_WEIGHTS,
        self::FIELD_SIMILARITY_CACHE_KEY,
        self::FIELD_CALCULATION_META,
    ];

    protected $casts = [
        self::FIELD_PERIOD_START => 'date',
        self::FIELD_PERIOD_END => 'date',
        self::FIELD_CF_USER_SCORE => 'decimal:8',
        self::FIELD_CF_USER_AVG_SIMILARITY => 'decimal:8',
        self::FIELD_CF_USER_NEIGHBOR_COUNT => 'integer',
        self::FIELD_CF_USER_TOP_NEIGHBORS => 'array',
        self::FIELD_CF_ITEM_SCORE => 'decimal:8',
        self::FIELD_CF_ITEM_RAW_SCORE => 'decimal:8',
        self::FIELD_CF_ITEM_RELATION_SCORE => 'decimal:8',
        self::FIELD_CF_ITEM_DIVERSITY_FACTOR => 'decimal:8',
        self::FIELD_CF_ITEM_BALANCE_FACTOR => 'decimal:8',
        self::FIELD_CF_ITEM_AVG_SALES_NORM => 'decimal:8',
        self::FIELD_CF_ITEM_ACTIVE_PRODUCTS => 'integer',
        self::FIELD_CF_ITEM_TOTAL_PRODUCTS => 'integer',
        self::FIELD_CF_SCORE => 'decimal:8',
        self::FIELD_CF_BETA => 'decimal:6',
        self::FIELD_SIMILARITY_WEIGHTS => 'array',
        self::FIELD_CALCULATION_META => 'array',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }

    public function partnerScores()
    {
        return $this->hasMany(PartnerPerformanceScore::class, PartnerPerformanceScore::FIELD_CF_SCORE_ID, self::FIELD_ID);
    }
}