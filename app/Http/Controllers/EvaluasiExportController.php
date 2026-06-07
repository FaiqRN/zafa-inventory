<?php

namespace App\Http\Controllers;

use App\Exports\PartnerPerformanceExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluasiExportController extends Controller
{
    public function export(Request $request)
    {
        $alpha = (float) $request->input('alpha', 0.5);

        // Otomatis ambil periode terpanjang untuk alpha ini
        $periode = DB::table('partner_scores')
            ->where('hybrid_alpha', $alpha)
            ->selectRaw('period_start, period_end,
                DATEDIFF(period_end, period_start) as durasi,
                COUNT(*) as jumlah_toko')
            ->groupBy('period_start', 'period_end')
            ->orderByDesc('durasi')
            ->orderByDesc('jumlah_toko')
            ->first();

        // Fallback ke alpha 0.5 jika alpha lain belum ada datanya
        if (!$periode) {
            $periode = DB::table('partner_scores')
                ->selectRaw('period_start, period_end,
                    DATEDIFF(period_end, period_start) as durasi,
                    COUNT(*) as jumlah_toko')
                ->groupBy('period_start', 'period_end')
                ->orderByDesc('durasi')
                ->orderByDesc('jumlah_toko')
                ->first();
        }

        if (!$periode) {
            return response()->json([
                'error' => 'Tidak ada data partner scores tersedia'
            ], 404);
        }

        $periodRankingStart = $periode->period_start;
        $periodRankingEnd   = $periode->period_end;

        // Ground truth = 3 bulan terakhir dari period_end
        $groundTruthEnd   = date('Y-m-t', strtotime($periodRankingEnd));
        $groundTruthStart = date('Y-m-01', strtotime('-2 months',
                            strtotime($groundTruthEnd)));

        $alphaLabel = number_format($alpha, 1);
        $alphaStr   = str_replace('.', '', $alphaLabel);
        $filename   = "evaluasi_alpha{$alphaStr}_" .
                       date('Ymd_His') . '.xlsx';

        return Excel::download(
            new PartnerPerformanceExport(
                $periodRankingStart,
                $periodRankingEnd,
                $groundTruthStart,
                $groundTruthEnd,
                $alpha
            ),
            $filename
        );
    }
}
