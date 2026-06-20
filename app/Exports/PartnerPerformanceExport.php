<?php
namespace App\Exports;

use App\Exports\Sheets\EvaluasiMetrikSheet;

class PartnerPerformanceExport
{
    protected string $periodRankingStart;
    protected string $periodRankingEnd;
    protected string $groundTruthStart;
    protected string $groundTruthEnd;
    protected float  $alpha;

    public function __construct(
        string $periodRankingStart,
        string $periodRankingEnd,
        string $groundTruthStart,
        string $groundTruthEnd,
        float  $alpha = 0.5
    ) {
        $this->periodRankingStart = $periodRankingStart;
        $this->periodRankingEnd   = $periodRankingEnd;
        $this->groundTruthStart   = $groundTruthStart;
        $this->groundTruthEnd     = $groundTruthEnd;
        $this->alpha              = $alpha;
    }

    public function sheets(): array
    {
        return [
            new EvaluasiMetrikSheet(
                $this->periodRankingStart,
                $this->periodRankingEnd,
                $this->groundTruthStart,
                $this->groundTruthEnd,
                $this->alpha
            )
        ];
    }
}