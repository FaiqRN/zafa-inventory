<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PartnerPerformanceScore — maps to the `partner_scores` table (Layer 3 / Hybrid Output).
 *
 * This model was updated to match the new layered schema:
 *   - Legacy KPI raw columns (sales, return_rate, freq, consistency, efficiency) → REMOVED (now in partner_kpi_scores)
 *   - Legacy JSON columns (kpi_normalized, kpi_vector, top_neighbors, calculation_meta) → REMOVED (now in partner_kpi_scores / partner_cf_scores)
 *   - FK columns (kpi_score_id, cf_score_id) → references partner_kpi_scores and partner_cf_scores
 *   - Score columns (cbf_score, cf_user_score, cf_item_score, cf_score, hybrid_score) → denormalized copies for fast dashboard queries
 *   - Contribution columns (contribution_cbf, contribution_cf, contribution_cf_user, contribution_cf_item)
 *   - Parameters (hybrid_alpha, hybrid_beta)
 *   - category and rank
 */
class PartnerPerformanceScore extends Model
{
    use HasFactory;

    public const TABLE = 'partner_scores';
    public const FIELD_ID = 'id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_PERIOD_START = 'period_start';
    public const FIELD_PERIOD_END = 'period_end';
    public const FIELD_KPI_SCORE_ID = 'kpi_score_id';
    public const FIELD_CF_SCORE_ID = 'cf_score_id';
    public const FIELD_CBF_SCORE = 'cbf_score';
    public const FIELD_CF_USER_SCORE = 'cf_user_score';
    public const FIELD_CF_ITEM_SCORE = 'cf_item_score';
    public const FIELD_CF_SCORE = 'cf_score';
    public const FIELD_HYBRID_SCORE = 'hybrid_score';
    public const FIELD_HYBRID_ALPHA = 'hybrid_alpha';
    public const FIELD_HYBRID_BETA = 'hybrid_beta';
    public const FIELD_CONTRIBUTION_CBF = 'contribution_cbf';
    public const FIELD_CONTRIBUTION_CF = 'contribution_cf';
    public const FIELD_CONTRIBUTION_CF_USER = 'contribution_cf_user';
    public const FIELD_CONTRIBUTION_CF_ITEM = 'contribution_cf_item';
    public const FIELD_CATEGORY = 'category';
    public const FIELD_RANK = 'rank';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_TOKO_ID,
        self::FIELD_PERIOD_START,
        self::FIELD_PERIOD_END,
        self::FIELD_KPI_SCORE_ID,
        self::FIELD_CF_SCORE_ID,
        self::FIELD_CBF_SCORE,
        self::FIELD_CF_USER_SCORE,
        self::FIELD_CF_ITEM_SCORE,
        self::FIELD_CF_SCORE,
        self::FIELD_HYBRID_SCORE,
        self::FIELD_HYBRID_ALPHA,
        self::FIELD_HYBRID_BETA,
        self::FIELD_CONTRIBUTION_CBF,
        self::FIELD_CONTRIBUTION_CF,
        self::FIELD_CONTRIBUTION_CF_USER,
        self::FIELD_CONTRIBUTION_CF_ITEM,
        self::FIELD_CATEGORY,
        self::FIELD_RANK,
    ];

    protected $casts = [
        self::FIELD_PERIOD_START         => 'date',
        self::FIELD_PERIOD_END           => 'date',
        self::FIELD_KPI_SCORE_ID         => 'integer',
        self::FIELD_CF_SCORE_ID          => 'integer',
        self::FIELD_CBF_SCORE            => 'decimal:8',
        self::FIELD_CF_USER_SCORE        => 'decimal:8',
        self::FIELD_CF_ITEM_SCORE        => 'decimal:8',
        self::FIELD_CF_SCORE             => 'decimal:8',
        self::FIELD_HYBRID_SCORE         => 'decimal:8',
        self::FIELD_HYBRID_ALPHA         => 'decimal:6',
        self::FIELD_HYBRID_BETA          => 'decimal:6',
        self::FIELD_CONTRIBUTION_CBF     => 'decimal:8',
        self::FIELD_CONTRIBUTION_CF      => 'decimal:8',
        self::FIELD_CONTRIBUTION_CF_USER => 'decimal:8',
        self::FIELD_CONTRIBUTION_CF_ITEM => 'decimal:8',
        self::FIELD_RANK                 => 'integer',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }

    public function kpiScore()
    {
        return $this->belongsTo(PartnerKpiScore::class, self::FIELD_KPI_SCORE_ID, PartnerKpiScore::FIELD_ID);
    }

    public function cfScore()
    {
        return $this->belongsTo(PartnerCfScore::class, self::FIELD_CF_SCORE_ID, PartnerCfScore::FIELD_ID);
    }
}