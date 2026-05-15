<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerKpiScore extends Model
{
    use HasFactory;

    public const TABLE = 'partner_kpi_scores';
    public const FIELD_ID = 'id';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_PERIOD_START = 'period_start';
    public const FIELD_PERIOD_END = 'period_end';
    public const FIELD_KPI_RAW_SALES = 'kpi_raw_sales';
    public const FIELD_KPI_RAW_RETURN_RATE = 'kpi_raw_return_rate';
    public const FIELD_KPI_RAW_FREQ = 'kpi_raw_freq';
    public const FIELD_KPI_RAW_CONSISTENCY = 'kpi_raw_consistency';
    public const FIELD_KPI_RAW_EFFICIENCY = 'kpi_raw_efficiency';
    public const FIELD_KPI_NORM_SALES = 'kpi_norm_sales';
    public const FIELD_KPI_NORM_RETURN_RATE = 'kpi_norm_return_rate';
    public const FIELD_KPI_NORM_FREQ = 'kpi_norm_freq';
    public const FIELD_KPI_NORM_CONSISTENCY = 'kpi_norm_consistency';
    public const FIELD_KPI_NORM_EFFICIENCY = 'kpi_norm_efficiency';
    public const FIELD_CBF_SCORE = 'cbf_score';
    public const FIELD_CBF_WEIGHTS = 'cbf_weights';
    public const FIELD_KPI_VECTOR = 'kpi_vector';
    public const FIELD_TIME_SERIES_VECTOR = 'time_series_vector';
    public const FIELD_CALCULATION_META = 'calculation_meta';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_TOKO_ID,
        self::FIELD_PERIOD_START,
        self::FIELD_PERIOD_END,
        self::FIELD_KPI_RAW_SALES,
        self::FIELD_KPI_RAW_RETURN_RATE,
        self::FIELD_KPI_RAW_FREQ,
        self::FIELD_KPI_RAW_CONSISTENCY,
        self::FIELD_KPI_RAW_EFFICIENCY,
        self::FIELD_KPI_NORM_SALES,
        self::FIELD_KPI_NORM_RETURN_RATE,
        self::FIELD_KPI_NORM_FREQ,
        self::FIELD_KPI_NORM_CONSISTENCY,
        self::FIELD_KPI_NORM_EFFICIENCY,
        self::FIELD_CBF_SCORE,
        self::FIELD_CBF_WEIGHTS,
        self::FIELD_KPI_VECTOR,
        self::FIELD_TIME_SERIES_VECTOR,
        self::FIELD_CALCULATION_META,
    ];

    protected $casts = [
        self::FIELD_PERIOD_START => 'date',
        self::FIELD_PERIOD_END => 'date',
        self::FIELD_KPI_RAW_SALES => 'integer',
        self::FIELD_KPI_RAW_RETURN_RATE => 'decimal:6',
        self::FIELD_KPI_RAW_FREQ => 'integer',
        self::FIELD_KPI_RAW_CONSISTENCY => 'decimal:6',
        self::FIELD_KPI_RAW_EFFICIENCY => 'decimal:6',
        self::FIELD_KPI_NORM_SALES => 'decimal:8',
        self::FIELD_KPI_NORM_RETURN_RATE => 'decimal:8',
        self::FIELD_KPI_NORM_FREQ => 'decimal:8',
        self::FIELD_KPI_NORM_CONSISTENCY => 'decimal:8',
        self::FIELD_KPI_NORM_EFFICIENCY => 'decimal:8',
        self::FIELD_CBF_SCORE => 'decimal:8',
        self::FIELD_CBF_WEIGHTS => 'array',
        self::FIELD_KPI_VECTOR => 'array',
        self::FIELD_TIME_SERIES_VECTOR => 'array',
        self::FIELD_CALCULATION_META => 'array',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, self::FIELD_TOKO_ID, Toko::FIELD_TOKO_ID);
    }

    public function partnerScores()
    {
        return $this->hasMany(PartnerPerformanceScore::class, PartnerPerformanceScore::FIELD_KPI_SCORE_ID, self::FIELD_ID);
    }
}