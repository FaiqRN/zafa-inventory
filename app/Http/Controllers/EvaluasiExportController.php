<?php
namespace App\Http\Controllers;

use App\Exports\Sheets\EvaluasiMetrikSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EvaluasiExportController extends Controller
{
    public function export(Request $request)
    {
        $alpha = (float) $request->input('alpha', 0.5);

        $periode = DB::table('partner_scores')
            ->where('hybrid_alpha', $alpha)
            ->selectRaw('period_start, period_end,
                DATEDIFF(period_end, period_start) as durasi,
                COUNT(*) as jumlah_toko')
            ->groupBy('period_start', 'period_end')
            ->orderByDesc('durasi')
            ->orderByDesc('jumlah_toko')
            ->first();

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

        $groundTruthEnd   = date('Y-m-t', strtotime($periodRankingEnd));
        $groundTruthStart = date('Y-m-01', strtotime('-2 months', strtotime($groundTruthEnd)));

        $alphaLabel = number_format($alpha, 1);
        $alphaStr   = str_replace('.', '', $alphaLabel);
        $filename   = "evaluasi_alpha{$alphaStr}_" . date('Ymd_His') . '.xlsx';

        $sheet = new EvaluasiMetrikSheet(
            $periodRankingStart,
            $periodRankingEnd,
            $groundTruthStart,
            $groundTruthEnd,
            $alpha
        );

        $headings   = $sheet->headings();
        $collection = $sheet->collection();
        $styles     = $sheet->styles(new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet());

        $spreadsheet = new Spreadsheet();
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle($sheet->title());

    // Tulis heading di baris 1
    foreach ($headings as $colIndex => $heading) {
        $ws->getCell([$colIndex + 1, 1])->setValue($heading);
    }

    // Terapkan style heading
    $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headings));
    $ws->getStyle('A1:' . $lastCol . '1')->applyFromArray($styles[1]);

    // Tulis data mulai baris 2
    $rowNum = 2;
    foreach ($collection as $row) {
        $colNum = 1;
        foreach ($row as $value) {
            $ws->getCell([$colNum, $rowNum])->setValue($value);
            $colNum++;
        }
        $rowNum++;
    }

    // Auto-width semua kolom
    foreach (range(1, count($headings)) as $colIndex) {
        $ws->getColumnDimensionByColumn($colIndex)->setAutoSize(true);
    }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}