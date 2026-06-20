<?php
namespace App\Exports\Sheets;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EvaluasiMetrikSheet
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
        float  $alpha
    ) {
        $this->periodRankingStart = $periodRankingStart;
        $this->periodRankingEnd   = $periodRankingEnd;
        $this->groundTruthStart   = $groundTruthStart;
        $this->groundTruthEnd     = $groundTruthEnd;
        $this->alpha              = $alpha;
    }

    public function title(): string
    {
        return 'Evaluasi_Metrik';
    }

    public function headings(): array
    {
        return [
            'run_id', 'period_ranking_start', 'period_ranking_end',
            'ground_truth_start', 'ground_truth_end', 'generated_at',
            'hybrid_alpha', 'hybrid_beta', 'partner_id', 'partner_name',
            'wilayah', 'rank', 'hybrid_score', 'cbf_score', 'cf_score',
            'cf_user_score', 'cf_item_score', 'shipped_qty', 'returned_qty',
            'sold_qty', 'sell_through_rate', 'transaksi_count',
            'relevance_binary', 'relevance_graded',
        ];
    }

    public function collection()
    {
        $best = DB::table('partner_scores')
            ->selectRaw('period_start, period_end,
                DATEDIFF(period_end, period_start) as durasi,
                COUNT(*) as cnt')
            ->groupBy('period_start', 'period_end')
            ->orderByDesc('durasi')
            ->orderByDesc('cnt')
            ->first();

        if (!$best) return collect();

        $usedStart = $best->period_start;
        $usedEnd   = $best->period_end;

        $this->periodRankingStart = $usedStart;
        $this->periodRankingEnd   = $usedEnd;

        $rawScores = DB::table('partner_scores as ps')
            ->join('toko as t', 't.toko_id', '=', 'ps.toko_id')
            ->where('ps.period_start', $usedStart)
            ->where('ps.period_end',   $usedEnd)
            ->select(
                'ps.toko_id', 't.nama_toko',
                DB::raw("CONCAT(t.wilayah_kecamatan, ', ', t.wilayah_kota_kabupaten) as wilayah"),
                'ps.cbf_score', 'ps.cf_score', 'ps.cf_user_score',
                'ps.cf_item_score', 'ps.hybrid_beta',
            )
            ->get();

        $alpha  = $this->alpha;
        $scored = $rawScores->map(function ($row) use ($alpha) {
            $cbf = (float) ($row->cbf_score ?? 0);
            $cf  = (float) ($row->cf_score  ?? 0);
            $hybridScore = ($alpha * $cbf) + ((1 - $alpha) * $cf);
            $hybridScore = max(0.0, min(1.0, $hybridScore));
            $row->hybrid_score_computed = round($hybridScore, 8);
            return $row;
        });

        $sorted = $scored->sortBy([
            ['hybrid_score_computed', 'desc'],
            ['cbf_score',             'desc'],
            ['cf_score',              'desc'],
        ])->values();

        $rankings = $sorted->map(function ($row, $index) {
            $row->rank = $index + 1;
            return $row;
        })->keyBy('toko_id');

        $returData = DB::table('retur')
            ->whereBetween('tanggal_retur', [$this->groundTruthStart, $this->groundTruthEnd])
            ->select(
                'toko_id',
                DB::raw('SUM(jumlah_kirim)  as shipped_qty'),
                DB::raw('SUM(jumlah_retur)  as returned_qty'),
                DB::raw('SUM(total_terjual) as sold_qty'),
                DB::raw('COUNT(*)           as transaksi_count'),
            )
            ->groupBy('toko_id')
            ->get()
            ->keyBy('toko_id');

        $strValues = $returData->map(function ($r) {
            $shipped = (float) $r->shipped_qty;
            return $shipped > 0 ? (float) $r->sold_qty / $shipped : 0.0;
        })->values()->sort()->values();

        $median = $this->hitungMedian($strValues);
        $q1     = $this->hitungPersentil($strValues, 25);
        $q3     = $this->hitungPersentil($strValues, 75);

        $runId       = implode('|', [$this->periodRankingStart, $this->periodRankingEnd, $this->alpha]);
        $generatedAt = now()->format('Y-m-d H:i:s');

        return $rankings->map(function ($toko) use (
            $returData, $runId, $generatedAt, $median, $q1, $q3
        ) {
            $retur       = $returData->get($toko->toko_id);
            $shippedQty  = (float) ($retur->shipped_qty     ?? 0);
            $returnedQty = (float) ($retur->returned_qty    ?? 0);
            $soldQty     = (float) ($retur->sold_qty        ?? 0);
            $txCount     = (int)   ($retur->transaksi_count ?? 0);
            $str         = ($shippedQty > 0) ? round($soldQty / $shippedQty, 4) : 0.0;

            $fStr    = (float) $str;
            $fMedian = (float) $median;
            $fQ1     = (float) $q1;
            $fQ3     = (float) $q3;

            $binary = ($fStr >= $fMedian) ? 1 : 0;
            if      ($fStr >= $fQ3)     { $graded = 3; }
            elseif  ($fStr >= $fMedian) { $graded = 2; }
            elseif  ($fStr >= $fQ1)     { $graded = 1; }
            else                        { $graded = 0; }

            return [
                $runId,
                $this->periodRankingStart,
                $this->periodRankingEnd,
                $this->groundTruthStart,
                $this->groundTruthEnd,
                $generatedAt,
                (string) number_format((float) $this->alpha, 1),
                round((float) ($toko->hybrid_beta          ?? 0), 2),
                (string) $toko->toko_id,
                (string) $toko->nama_toko,
                (string) ($toko->wilayah                   ?? '-'),
                (int)    $toko->rank,
                round((float) ($toko->hybrid_score_computed ?? 0), 6),
                round((float) ($toko->cbf_score             ?? 0), 6),
                round((float) ($toko->cf_score              ?? 0), 6),
                round((float) ($toko->cf_user_score         ?? 0), 6),
                round((float) ($toko->cf_item_score         ?? 0), 6),
                (int)   $shippedQty,
                (int)   $returnedQty,
                (int)   $soldQty,
                (float) $str,
                (int)   $txCount,
                (int)   $binary,
                (int)   $graded,
            ];
        })->values();
    }

    private function hitungMedian($collection): float
    {
        $sorted = $collection->map(fn($v) => (float) ($v ?? 0.0))->sort()->values();
        $count  = $sorted->count();
        if ($count === 0) return 0.0;
        $mid    = intdiv($count, 2);
        $result = $count % 2 === 0
            ? ((float) $sorted[$mid - 1] + (float) $sorted[$mid]) / 2
            : (float) $sorted[$mid];
        return is_finite($result) ? $result : 0.0;
    }

    private function hitungPersentil($collection, int $pct): float
    {
        $sorted = $collection->map(fn($v) => (float) ($v ?? 0.0))->sort()->values();
        $count  = $sorted->count();
        if ($count === 0) return 0.0;
        $pct    = max(0, min(100, $pct));
        $index  = ($pct / 100) * ($count - 1);
        $lower  = (int) floor($index);
        $upper  = (int) ceil($index);
        $result = $lower === $upper
            ? (float) $sorted[$lower]
            : (float) $sorted[$lower] + ($index - $lower) * ((float) $sorted[$upper] - (float) $sorted[$lower]);
        return is_finite($result) ? $result : 0.0;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F4E79']],
            ],
        ];
    }
}