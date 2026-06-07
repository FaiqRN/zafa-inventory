<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EvaluasiMetrikSheet implements
    FromCollection, WithHeadings, WithTitle, WithStyles
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
            // Identitas eksperimen
            'run_id',
            'period_ranking_start',
            'period_ranking_end',
            'ground_truth_start',
            'ground_truth_end',
            'generated_at',
            'hybrid_alpha',
            'hybrid_beta',
            // Identitas toko
            'partner_id',
            'partner_name',
            'wilayah',
            // Hasil prediksi model
            'rank',
            'hybrid_score',
            'cbf_score',
            'cf_score',
            'cf_user_score',
            'cf_item_score',
            // KPI aktual dari data transaksi
            'shipped_qty',
            'returned_qty',
            'sold_qty',
            'sell_through_rate',
            'transaksi_count',
            // Ground truth
            'relevance_binary',
            'relevance_graded',
        ];
    }

    public function collection()
    {
        // ── 1. Cari snapshot terbaik dari partner_scores ──
        //    Tidak filter by hybrid_alpha karena hanya alpha=0.5 yang tersimpan.
        //    cbf_score dan cf_score adalah skor DASAR yang sama untuk semua alpha.
        //    hybrid_score akan dihitung ulang menggunakan alpha yang diminta.

        // Cari snapshot dengan jumlah toko terbanyak dan durasi terpanjang
        $best = DB::table('partner_scores')
            ->selectRaw('period_start, period_end,
                DATEDIFF(period_end, period_start) as durasi,
                COUNT(*) as cnt')
            ->groupBy('period_start', 'period_end')
            ->orderByDesc('durasi')
            ->orderByDesc('cnt')
            ->first();

        if (!$best) {
            return collect(); // tidak ada data sama sekali
        }

        $usedStart = $best->period_start;
        $usedEnd   = $best->period_end;

        // Simpan periode aktual yang digunakan (untuk kolom Excel)
        $this->periodRankingStart = $usedStart;
        $this->periodRankingEnd   = $usedEnd;

        // ── 2. Ambil semua toko dari snapshot terbaik (tanpa filter alpha) ──
        //    Ambil cbf_score, cf_score, cf_user_score, cf_item_score sebagai skor dasar.
        //    hybrid_score akan dihitung ulang dengan alpha yang diminta.
        $rawScores = DB::table('partner_scores as ps')
            ->join('toko as t', 't.toko_id', '=', 'ps.toko_id')
            ->where('ps.period_start', $usedStart)
            ->where('ps.period_end',   $usedEnd)
            ->select(
                'ps.toko_id',
                't.nama_toko',
                DB::raw("CONCAT(t.wilayah_kecamatan, ', ',
                         t.wilayah_kota_kabupaten) as wilayah"),
                'ps.cbf_score',
                'ps.cf_score',
                'ps.cf_user_score',
                'ps.cf_item_score',
                'ps.hybrid_beta',
            )
            ->get();

        // ── 3. Hitung ulang hybrid_score dan rank menggunakan alpha yang diminta ──
        //    Rumus: hybrid_score = (α × cbf_score) + ((1 - α) × cf_score)
        //    Sesuai HybridRecommendationService::calculateHybridScore()
        $alpha = $this->alpha;

        $scored = $rawScores->map(function ($row) use ($alpha) {
            $cbf = (float) ($row->cbf_score ?? 0);
            $cf  = (float) ($row->cf_score  ?? 0);

            // Rumus hybrid persis sama dengan HybridRecommendationService
            $hybridScore = ($alpha * $cbf) + ((1 - $alpha) * $cf);
            $hybridScore = max(0.0, min(1.0, $hybridScore)); // clamp 0–1

            $row->hybrid_score_computed = round($hybridScore, 8);
            return $row;
        });

        // Urutkan descending by hybrid_score, tie-break by cbf_score lalu cf_score
        $sorted = $scored->sortBy([
            ['hybrid_score_computed', 'desc'],
            ['cbf_score',             'desc'],
            ['cf_score',              'desc'],
        ])->values();

        // Assign rank (1 = teratas)
        $rankings = $sorted->map(function ($row, $index) {
            $row->rank = $index + 1;
            return $row;
        })->keyBy('toko_id');


        // ── 2. Ambil data aktual dari retur (ground truth) ──
        $returData = DB::table('retur')
            ->whereBetween('tanggal_retur', [
                $this->groundTruthStart,
                $this->groundTruthEnd
            ])
            ->select(
                'toko_id',
                DB::raw('SUM(jumlah_kirim)   as shipped_qty'),
                DB::raw('SUM(jumlah_retur)   as returned_qty'),
                DB::raw('SUM(total_terjual)  as sold_qty'),
                DB::raw('COUNT(*)            as transaksi_count'),
            )
            ->groupBy('toko_id')
            ->get()
            ->keyBy('toko_id');

        // ── 3. Hitung sell_through_rate semua toko ──
        $strValues = $returData->map(function ($r) {
            $shipped = (float) $r->shipped_qty;
            return $shipped > 0
                ? (float) $r->sold_qty / $shipped
                : 0.0;
        })->values()->sort()->values();

        // ── 4. Hitung median dan kuartil ──
        $count  = $strValues->count();
        $median = $this->hitungMedian($strValues);
        $q1     = $this->hitungPersentil($strValues, 25);
        $q3     = $this->hitungPersentil($strValues, 75);

        // ── 5. Gabungkan dan buat output ──
        $runId = implode('|', [
            $this->periodRankingStart,
            $this->periodRankingEnd,
            $this->alpha,
        ]);

        $generatedAt = now()->format('Y-m-d H:i:s');

        // Gunakan semua toko dari ranking sebagai basis
        return $rankings->map(function ($toko) use (
            $returData, $runId, $generatedAt,
            $median, $q1, $q3
        ) {
            $retur = $returData->get($toko->toko_id);

            // Cast eksplisit — pastikan tidak pernah null meski toko tidak ada di retur
            $shippedQty  = (float) ($retur->shipped_qty   ?? 0);
            $returnedQty = (float) ($retur->returned_qty  ?? 0);
            $soldQty     = (float) ($retur->sold_qty      ?? 0);
            $txCount     = (int)   ($retur->transaksi_count ?? 0);

            // STR: selalu float 0.0–1.0, tidak pernah NaN
            $str = ($shippedQty > 0)
                ? round($soldQty / $shippedQty, 4)
                : 0.0;

            // Cast semua threshold ke float eksplisit — cegah type mismatch
            $fMedian = (float) $median;
            $fQ1     = (float) $q1;
            $fQ3     = (float) $q3;
            $fStr    = (float) $str;

            // Binary: paksa selalu 0 atau 1, tidak pernah null/NaN
            $binary = ($fStr >= $fMedian) ? 1 : 0;

            // Graded: paksa selalu 0/1/2/3, tidak pernah null/NaN
            if      ($fStr >= $fQ3)     { $graded = 3; }
            elseif  ($fStr >= $fMedian) { $graded = 2; }
            elseif  ($fStr >= $fQ1)     { $graded = 1; }
            else                        { $graded = 0; }

            // Safeguard tambahan — pastikan tidak ada null yang lolos ke Excel
            $binary = is_null($binary) ? 0 : (int) $binary;
            $graded = is_null($graded) ? 0 : (int) $graded;

            return [
                $runId,
                $this->periodRankingStart,
                $this->periodRankingEnd,
                $this->groundTruthStart,
                $this->groundTruthEnd,
                $generatedAt,
                (string) number_format((float) $this->alpha, 1),
                round((float) ($toko->hybrid_beta ?? 0), 2),
                (string) $toko->toko_id,
                (string) $toko->nama_toko,
                (string) ($toko->wilayah ?? '-'),
                (int) $toko->rank,
                round((float) ($toko->hybrid_score_computed ?? 0), 6),
                round((float) ($toko->cbf_score     ?? 0), 6),
                round((float) ($toko->cf_score      ?? 0), 6),
                round((float) ($toko->cf_user_score ?? 0), 6),
                round((float) ($toko->cf_item_score ?? 0), 6),
                (int) $shippedQty,
                (int) $returnedQty,
                (int) $soldQty,
                (float) $str,
                (int) $txCount,
                (int) $binary,
                (int) $graded,
            ];
        })->values();
    }

    // ── Helper: hitung median — selalu return float, tidak pernah NaN ──
    private function hitungMedian($collection): float
    {
        // Cast semua nilai ke float sebelum operasi
        $sorted = $collection
            ->map(fn($v) => (float) ($v ?? 0.0))
            ->sort()
            ->values();

        $count = $sorted->count();
        if ($count === 0) return 0.0;

        $mid = intdiv($count, 2);
        $result = $count % 2 === 0
            ? ((float) $sorted[$mid - 1] + (float) $sorted[$mid]) / 2
            : (float) $sorted[$mid];

        // Safeguard: jika NaN/INF karena alasan apapun, return 0.0
        return is_finite($result) ? $result : 0.0;
    }

    // ── Helper: hitung persentil — selalu return float, tidak pernah NaN ──
    private function hitungPersentil($collection, int $pct): float
    {
        // Cast semua nilai ke float sebelum operasi
        $sorted = $collection
            ->map(fn($v) => (float) ($v ?? 0.0))
            ->sort()
            ->values();

        $count = $sorted->count();
        if ($count === 0) return 0.0;

        // Clamp pct ke range valid
        $pct = max(0, min(100, $pct));
        $index = ($pct / 100) * ($count - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        $lowerVal = (float) $sorted[$lower];
        $upperVal = (float) $sorted[$upper];

        $result = $lower === $upper
            ? $lowerVal
            : $lowerVal + ($index - $lower) * ($upperVal - $lowerVal);

        // Safeguard: jika NaN/INF karena alasan apapun, return 0.0
        return is_finite($result) ? $result : 0.0;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => 'solid',
                    'startColor' => ['rgb' => '1F4E79'],
                ],
            ],
        ];
    }
}