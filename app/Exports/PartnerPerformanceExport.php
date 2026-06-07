<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PartnerPerformanceExport implements WithMultipleSheets
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
            new Sheets\EvaluasiMetrikSheet(
                $this->periodRankingStart,
                $this->periodRankingEnd,
                $this->groundTruthStart,
                $this->groundTruthEnd,
                $this->alpha
            )
        ];
    }
}