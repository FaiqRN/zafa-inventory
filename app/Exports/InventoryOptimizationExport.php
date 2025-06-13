<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class InventoryOptimizationExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithMapping, WithTitle, WithEvents
{
    protected $recommendations;

    public function __construct($recommendations)
    {
        $this->recommendations = collect($recommendations);
    }

    public function collection()
    {
        return $this->recommendations;
    }

    public function map($recommendation): array
    {
        // Handle both array and object recommendation data - FIXED FOR OBJECT
        $rec = is_object($recommendation) ? $recommendation : (object) $recommendation;
        
        return [
            $rec->id ?? 'N/A',
            $rec->toko_nama ?? ($rec->toko->nama_toko ?? 'N/A'),
            $rec->barang_nama ?? ($rec->barang->nama_barang ?? 'N/A'), 
            $rec->barang_kode ?? ($rec->barang->barang_kode ?? 'N/A'),
            $rec->historical_avg_shipped ?? 0,
            $rec->historical_avg_sold ?? 0,
            $rec->recommended_quantity ?? 0,
            $rec->seasonal_multiplier ?? 1.0,
            $rec->trend_multiplier ?? 1.0,
            $rec->confidence_level ?? 'Medium',
            'Rp ' . number_format($rec->potential_savings ?? 0, 0, ',', '.'),
            ($rec->improvement_percentage ?? 0) . '%',
            ($rec->historical_avg_shipped ?? 0) - ($rec->recommended_quantity ?? 0),
            $this->getStatus($rec),
            $this->getPriority($rec),
            $this->getActionNeeded($rec),
            $this->getRecommendation($rec),
            $rec->applied_quantity ?? '-',
            $rec->applied_by ?? '-',
            $this->formatAppliedAt($rec),
            $rec->notes ?? '-'
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Toko',
            'Nama Produk',
            'Kode Produk',
            'Rata-rata Kirim',
            'Rata-rata Terjual',
            'Qty Rekomendasi',
            'Multiplier Seasonal',
            'Multiplier Trend',
            'Tingkat Confidence',
            'Potensi Penghematan',
            'Persentase Perbaikan',
            'Pengurangan Waste',
            'Status',
            'Prioritas',
            'Tindakan Diperlukan',
            'Rekomendasi Sistem',
            'Qty Diterapkan',
            'Diterapkan Oleh',
            'Waktu Diterapkan',
            'Catatan'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '28a745']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            // Data rows
            'A2:U' . ($this->recommendations->count() + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 25,  // Nama Toko
            'C' => 25,  // Nama Produk
            'D' => 15,  // Kode Produk
            'E' => 15,  // Rata-rata Kirim
            'F' => 15,  // Rata-rata Terjual
            'G' => 18,  // Qty Rekomendasi
            'H' => 18,  // Multiplier Seasonal
            'I' => 16,  // Multiplier Trend
            'J' => 16,  // Tingkat Confidence
            'K' => 20,  // Potensi Penghematan
            'L' => 18,  // Persentase Perbaikan
            'M' => 16,  // Pengurangan Waste
            'N' => 12,  // Status
            'O' => 12,  // Prioritas
            'P' => 30,  // Tindakan Diperlukan
            'Q' => 35,  // Rekomendasi Sistem
            'R' => 15,  // Qty Diterapkan
            'S' => 15,  // Diterapkan Oleh
            'T' => 18,  // Waktu Diterapkan
            'U' => 25   // Catatan
        ];
    }

    public function title(): string
    {
        return 'Inventory Optimization';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Add summary section
                $lastRow = $this->recommendations->count() + 3;
                
                $sheet->setCellValue('A' . $lastRow, 'RINGKASAN ANALISIS');
                $sheet->mergeCells('A' . $lastRow . ':F' . $lastRow);
                $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A' . $lastRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E3F2FD');
                
                $summaryRow = $lastRow + 1;
                $totalSavings = $this->recommendations->sum(function($rec) {
                    return is_object($rec) ? ($rec->potential_savings ?? 0) : ($rec['potential_savings'] ?? 0);
                });
                $avgImprovement = $this->recommendations->avg(function($rec) {
                    return is_object($rec) ? ($rec->improvement_percentage ?? 0) : ($rec['improvement_percentage'] ?? 0);
                });
                $highConfidenceCount = $this->recommendations->filter(function($rec) {
                    $confidence = is_object($rec) ? ($rec->confidence_level ?? '') : ($rec['confidence_level'] ?? '');
                    return $confidence === 'High';
                })->count();
                
                $sheet->setCellValue('A' . $summaryRow, 'Total Potensi Penghematan:');
                $sheet->setCellValue('B' . $summaryRow, 'Rp ' . number_format($totalSavings, 0, ',', '.'));
                
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'Rata-rata Perbaikan:');
                $sheet->setCellValue('B' . $summaryRow, round($avgImprovement, 1) . '%');
                
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'Rekomendasi High Confidence:');
                $sheet->setCellValue('B' . $summaryRow, $highConfidenceCount . ' dari ' . $this->recommendations->count());
                
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'Tanggal Export:');
                $sheet->setCellValue('B' . $summaryRow, now()->format('d/m/Y H:i:s'));
                
                // Freeze header row
                $sheet->freezePane('A2');
            }
        ];
    }

    private function formatAppliedAt($rec)
    {
        $appliedAt = is_object($rec) ? ($rec->applied_at ?? null) : ($rec['applied_at'] ?? null);
        
        if (!$appliedAt) {
            return '-';
        }
        
        try {
            if ($appliedAt instanceof Carbon) {
                return $appliedAt->format('d/m/Y H:i');
            } elseif (is_string($appliedAt)) {
                return Carbon::parse($appliedAt)->format('d/m/Y H:i');
            }
        } catch (\Exception $e) {
            return '-';
        }
        
        return '-';
    }

    private function getStatus($rec)
    {
        $status = is_object($rec) ? ($rec->status ?? 'pending') : ($rec['status'] ?? 'pending');
        
        $statusLabels = [
            'pending' => 'Menunggu',
            'applied' => 'Diterapkan',
            'customized' => 'Dikustomisasi',
            'rejected' => 'Ditolak'
        ];
        
        return $statusLabels[$status] ?? ucfirst($status);
    }

    private function getPriority($rec)
    {
        $potentialSavings = is_object($rec) ? ($rec->potential_savings ?? 0) : ($rec['potential_savings'] ?? 0);
        
        if ($potentialSavings > 1000000) {
            return 'Tinggi';
        } elseif ($potentialSavings > 500000) {
            return 'Sedang';
        } else {
            return 'Rendah';
        }
    }

    private function getActionNeeded($rec)
    {
        $actions = [];
        $improvementPercentage = is_object($rec) ? ($rec->improvement_percentage ?? 0) : ($rec['improvement_percentage'] ?? 0);
        $confidenceLevel = is_object($rec) ? ($rec->confidence_level ?? 'Medium') : ($rec['confidence_level'] ?? 'Medium');
        $seasonalMultiplier = is_object($rec) ? ($rec->seasonal_multiplier ?? 1.0) : ($rec['seasonal_multiplier'] ?? 1.0);
        
        if ($improvementPercentage > 30) {
            $actions[] = "Kurangi alokasi signifikan";
        } elseif ($improvementPercentage > 15) {
            $actions[] = "Optimasi alokasi";
        }
        
        if (in_array($confidenceLevel, ['Low', 'Very Low'])) {
            $actions[] = "Kumpulkan data lebih banyak";
        }
        
        if ($seasonalMultiplier < 0.8 || $seasonalMultiplier > 1.3) {
            $actions[] = "Verifikasi pola seasonal";
        }
        
        return implode('; ', $actions) ?: 'Terapkan rekomendasi';
    }

    private function getRecommendation($rec)
    {
        $recommendations = [];
        $confidenceLevel = is_object($rec) ? ($rec->confidence_level ?? 'Medium') : ($rec['confidence_level'] ?? 'Medium');
        $potentialSavings = is_object($rec) ? ($rec->potential_savings ?? 0) : ($rec['potential_savings'] ?? 0);
        $improvementPercentage = is_object($rec) ? ($rec->improvement_percentage ?? 0) : ($rec['improvement_percentage'] ?? 0);
        $seasonalMultiplier = is_object($rec) ? ($rec->seasonal_multiplier ?? 1.0) : ($rec['seasonal_multiplier'] ?? 1.0);
        
        if ($confidenceLevel === 'High' && $potentialSavings > 500000) {
            $recommendations[] = "Prioritas implementasi - confidence tinggi dengan potensi saving besar";
        } elseif ($confidenceLevel === 'Medium') {
            $recommendations[] = "Review dan implementasi bertahap";
        } elseif (in_array($confidenceLevel, ['Low', 'Very Low'])) {
            $recommendations[] = "Monitor performa sebelum implementasi penuh";
        }
        
        if ($improvementPercentage > 40) {
            $recommendations[] = "Waste sangat tinggi - implementasi segera";
        }
        
        if ($seasonalMultiplier > 1.2) {
            $recommendations[] = "Periode peak demand - tingkatkan stok";
        } elseif ($seasonalMultiplier < 0.9) {
            $recommendations[] = "Periode low demand - kurangi stok";
        }
        
        return implode('. ', $recommendations) ?: 'Implementasi standar sesuai rekomendasi sistem';
    }
}