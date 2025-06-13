<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PartnerPerformanceExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $partners;
    protected $exportDate;

    public function __construct($partners)
    {
        $this->partners = $partners;
        $this->exportDate = now()->format('Y-m-d H:i:s');
    }

    public function collection()
    {
        return $this->partners->map(function ($partner, $index) {
            return [
                'rank' => $index + 1,
                'partner_name' => $partner['nama_toko'] ?? 'N/A',
                'partner_id' => $partner['toko_id'] ?? 'N/A',
                'grade' => $partner['grade'] ?? 'C',
                'performance_score' => number_format($partner['performance_score'] ?? 0, 2),
                'sell_through_rate' => number_format($partner['sell_through_rate'] ?? 0, 2) . '%',
                'total_shipped' => number_format($partner['total_shipped'] ?? 0),
                'total_sold' => number_format($partner['total_sold'] ?? 0),
                'total_returned' => number_format($partner['total_returned'] ?? 0),
                'revenue' => 'Rp ' . number_format($partner['revenue'] ?? 0, 0, ',', '.'),
                'avg_days_to_return' => $partner['avg_days_to_return'] ?? 0, // ✅ Integer, no decimals
                'trend' => $this->getTrendText($partner['trend'] ?? []),
                'risk_level' => $partner['risk_score']['level'] ?? 'Low',
                'risk_score' => $partner['risk_score']['score'] ?? 0,
                'consistency_score' => number_format($partner['consistency_score'] ?? 0, 1),
                'shipment_count' => $partner['shipment_count'] ?? 0,
                'avg_shipment_size' => number_format($partner['avg_shipment_size'] ?? 0),
                'recommendations' => $this->getRecommendations($partner),
                'last_analysis' => $this->exportDate
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Partner Name',
            'Partner ID',
            'Grade',
            'Performance Score',
            'Sell Through Rate',
            'Total Shipped',
            'Total Sold',
            'Total Returned',
            'Revenue (6 months)',
            'Avg Days to Return',
            'Performance Trend',
            'Risk Level',
            'Risk Score',
            'Consistency Score',
            'Shipment Count',
            'Avg Shipment Size',
            'Recommendations',
            'Analysis Date'
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
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E86AB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // Rank
            'B' => 25,  // Partner Name
            'C' => 15,  // Partner ID
            'D' => 10,  // Grade
            'E' => 18,  // Performance Score
            'F' => 18,  // Sell Through Rate
            'G' => 15,  // Total Shipped
            'H' => 15,  // Total Sold
            'I' => 15,  // Total Returned
            'J' => 20,  // Revenue
            'K' => 18,  // Avg Days to Return
            'L' => 15,  // Trend
            'M' => 12,  // Risk Level
            'N' => 12,  // Risk Score
            'O' => 18,  // Consistency Score
            'P' => 15,  // Shipment Count
            'Q' => 18,  // Avg Shipment Size
            'R' => 50,  // Recommendations
            'S' => 20   // Analysis Date
        ];
    }

    public function title(): string
    {
        return 'Partner Performance Report';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                
                // Apply conditional formatting for grades
                $this->applyGradeConditionalFormatting($sheet, $highestRow);
                
                // Apply conditional formatting for performance scores
                $this->applyPerformanceConditionalFormatting($sheet, $highestRow);
                
                // Apply conditional formatting for risk levels
                $this->applyRiskConditionalFormatting($sheet, $highestRow);
                
                // Auto-fit row heights
                for ($i = 1; $i <= $highestRow; $i++) {
                    $sheet->getRowDimension($i)->setRowHeight(-1);
                }
                
                // Freeze header row
                $sheet->freezePane('A2');
                
                // Add filters
                $sheet->setAutoFilter('A1:S' . $highestRow);
                
                // Add summary information at the bottom
                $this->addSummaryInfo($sheet, $highestRow);
            }
        ];
    }

    private function getTrendText($trend)
    {
        if (empty($trend) || !isset($trend['trend'])) {
            return 'Unknown';
        }
        
        $trendText = ucfirst($trend['trend']);
        if (isset($trend['direction']) && $trend['direction'] != 0) {
            $direction = $trend['direction'] > 0 ? '+' : '';
            $trendText .= " ({$direction}" . number_format($trend['direction'], 1) . "%)";
        }
        
        return $trendText;
    }

    private function getRecommendations($partner)
    {
        $recommendations = [];
        
        if (($partner['sell_through_rate'] ?? 0) < 50) {
            $recommendations[] = "Reduce allocation by 30-50%";
        }
        
        if (($partner['avg_days_to_return'] ?? 0) > 28) {
            $recommendations[] = "Implement faster collection";
        }
        
        if (($partner['grade'] ?? 'C') === 'C') {
            $recommendations[] = "Urgent partnership review required";
        }
        
        if (isset($partner['trend']['trend']) && $partner['trend']['trend'] === 'declining') {
            $recommendations[] = "Address declining performance";
        }
        
        if (isset($partner['risk_score']['level']) && $partner['risk_score']['level'] === 'High') {
            $recommendations[] = "High risk - monitor closely";
        }
        
        if (($partner['consistency_score'] ?? 0) < 60) {
            $recommendations[] = "Improve consistency";
        }
        
        if (empty($recommendations)) {
            $recommendations[] = "Continue current strategy";
        }
        
        return implode('; ', $recommendations);
    }

    private function applyGradeConditionalFormatting($sheet, $highestRow)
    {
        // Grade column (D) conditional formatting
        for ($row = 2; $row <= $highestRow; $row++) {
            $gradeCell = 'D' . $row;
            $grade = $sheet->getCell($gradeCell)->getValue();
            
            $fillColor = '6C757D'; // Default gray
            
            switch ($grade) {
                case 'A+':
                    $fillColor = '28A745'; // Green
                    break;
                case 'A':
                    $fillColor = '007BFF'; // Blue
                    break;
                case 'B+':
                    $fillColor = '17A2B8'; // Cyan
                    break;
                case 'B':
                    $fillColor = 'FFC107'; // Yellow
                    break;
                case 'C+':
                    $fillColor = 'FD7E14'; // Orange
                    break;
                case 'C':
                    $fillColor = 'DC3545'; // Red
                    break;
            }
            
            $sheet->getStyle($gradeCell)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $fillColor]
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ]);
        }
    }

    private function applyPerformanceConditionalFormatting($sheet, $highestRow)
    {
        // Performance Score column (E) conditional formatting
        for ($row = 2; $row <= $highestRow; $row++) {
            $performanceCell = 'E' . $row;
            $performance = (float) $sheet->getCell($performanceCell)->getValue();
            
            $fillColor = 'F8F9FA'; // Light gray default
            
            if ($performance >= 90) {
                $fillColor = 'D4EDDA'; // Light green
            } elseif ($performance >= 75) {
                $fillColor = 'D1ECF1'; // Light blue
            } elseif ($performance >= 60) {
                $fillColor = 'FFF3CD'; // Light yellow
            } elseif ($performance >= 40) {
                $fillColor = 'F8D7DA'; // Light red
            } else {
                $fillColor = 'F5C6CB'; // Dark red
            }
            
            $sheet->getStyle($performanceCell)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $fillColor]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ]);
        }
    }

    private function applyRiskConditionalFormatting($sheet, $highestRow)
    {
        // Risk Level column (M) conditional formatting
        for ($row = 2; $row <= $highestRow; $row++) {
            $riskCell = 'M' . $row;
            $riskLevel = $sheet->getCell($riskCell)->getValue();
            
            $fillColor = '6C757D'; // Default gray
            $fontColor = 'FFFFFF';
            
            switch ($riskLevel) {
                case 'High':
                    $fillColor = 'DC3545'; // Red
                    break;
                case 'Medium':
                    $fillColor = 'FFC107'; // Yellow
                    $fontColor = '000000';
                    break;
                case 'Low':
                    $fillColor = '28A745'; // Green
                    break;
            }
            
            $sheet->getStyle($riskCell)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $fillColor]
                ],
                'font' => [
                    'color' => ['rgb' => $fontColor],
                    'bold' => true
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ]);
        }
    }

    private function addSummaryInfo($sheet, $highestRow)
    {
        $summaryStartRow = $highestRow + 3;
        
        // Calculate summary statistics
        $totalPartners = $this->partners->count();
        $avgPerformance = $this->partners->avg('performance_score');
        $totalRevenue = $this->partners->sum('revenue');
        $totalShipped = $this->partners->sum('total_shipped');
        $totalSold = $this->partners->sum('total_sold');
        $overallSellThrough = $totalShipped > 0 ? ($totalSold / $totalShipped) * 100 : 0;
        
        // Grade distribution
        $gradeDistribution = [
            'A+' => $this->partners->where('grade', 'A+')->count(),
            'A' => $this->partners->where('grade', 'A')->count(),
            'B+' => $this->partners->where('grade', 'B+')->count(),
            'B' => $this->partners->where('grade', 'B')->count(),
            'C+' => $this->partners->where('grade', 'C+')->count(),
            'C' => $this->partners->where('grade', 'C')->count()
        ];
        
        // Add summary title
        $sheet->setCellValue('A' . $summaryStartRow, 'SUMMARY ANALYTICS');
        $sheet->getStyle('A' . $summaryStartRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF']
            ]
        ]);
        $sheet->mergeCells('A' . $summaryStartRow . ':S' . $summaryStartRow);
        
        $summaryStartRow += 2;
        
        // Add key metrics
        $summaryData = [
            ['Metric', 'Value', 'Description'],
            ['Total Partners', $totalPartners, 'Active partners in analysis'],
            ['Average Performance Score', number_format($avgPerformance, 2), 'Overall network performance'],
            ['Total Revenue (6 months)', 'Rp ' . number_format($totalRevenue, 0, ',', '.'), 'Combined partner revenue'],
            ['Overall Sell-Through Rate', number_format($overallSellThrough, 2) . '%', 'Network efficiency'],
            ['Total Units Shipped', number_format($totalShipped), 'Total inventory distributed'],
            ['Total Units Sold', number_format($totalSold), 'Actual sales performance'],
            ['', '', ''], // Empty row
            ['Grade Distribution', '', ''],
            ['A+ Partners', $gradeDistribution['A+'], 'Excellent performers (85%+ sell-through)'],
            ['A Partners', $gradeDistribution['A'], 'Very good performers (75-85% sell-through)'],
            ['B+ Partners', $gradeDistribution['B+'], 'Good performers (65-75% sell-through)'],
            ['B Partners', $gradeDistribution['B'], 'Average performers (55-65% sell-through)'],
            ['C+ Partners', $gradeDistribution['C+'], 'Below average (45-55% sell-through)'],
            ['C Partners', $gradeDistribution['C'], 'Poor performers (<45% sell-through)'],
            ['', '', ''], // Empty row
            ['Risk Analysis', '', ''],
            ['High Risk Partners', $this->partners->filter(function($p) { 
                return isset($p['risk_score']['level']) && $p['risk_score']['level'] === 'High'; 
            })->count(), 'Partners requiring immediate attention'],
            ['Medium Risk Partners', $this->partners->filter(function($p) { 
                return isset($p['risk_score']['level']) && $p['risk_score']['level'] === 'Medium'; 
            })->count(), 'Partners requiring monitoring'],
            ['Low Risk Partners', $this->partners->filter(function($p) { 
                return isset($p['risk_score']['level']) && $p['risk_score']['level'] === 'Low'; 
            })->count(), 'Stable partnerships'],
            ['', '', ''], // Empty row
            ['Trend Analysis', '', ''],
            ['Improving Partners', $this->partners->filter(function($p) { 
                return isset($p['trend']['trend']) && $p['trend']['trend'] === 'improving'; 
            })->count(), 'Partners with positive trends'],
            ['Stable Partners', $this->partners->filter(function($p) { 
                return isset($p['trend']['trend']) && $p['trend']['trend'] === 'stable'; 
            })->count(), 'Partners with consistent performance'],
            ['Declining Partners', $this->partners->filter(function($p) { 
                return isset($p['trend']['trend']) && $p['trend']['trend'] === 'declining'; 
            })->count(), 'Partners requiring intervention'],
        ];
        
        // Write summary data
        foreach ($summaryData as $rowIndex => $rowData) {
            $currentRow = $summaryStartRow + $rowIndex;
            $sheet->setCellValue('A' . $currentRow, $rowData[0]);
            $sheet->setCellValue('B' . $currentRow, $rowData[1]);
            $sheet->setCellValue('C' . $currentRow, $rowData[2]);
            
            // Style header rows
            if ($rowIndex === 0 || in_array($rowData[0], ['Grade Distribution', 'Risk Analysis', 'Trend Analysis'])) {
                $sheet->getStyle('A' . $currentRow . ':C' . $currentRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F9FA']
                    ]
                ]);
            }
        }
        
        // Add export info
        $infoRow = $summaryStartRow + count($summaryData) + 2;
        $sheet->setCellValue('A' . $infoRow, 'Report Generated: ' . $this->exportDate);
        $sheet->setCellValue('A' . ($infoRow + 1), 'Analysis Period: Last 6 months');
        $sheet->setCellValue('A' . ($infoRow + 2), 'System: Zafa Potato Analytics Platform');
        
        $sheet->getStyle('A' . $infoRow . ':A' . ($infoRow + 2))->applyFromArray([
            'font' => ['italic' => true, 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F1F3F4']
            ]
        ]);
        
        // Auto-size summary columns
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(40);
        
        // Add border to summary section
        $summaryRange = 'A' . ($summaryStartRow - 1) . ':C' . ($infoRow + 2);
        $sheet->getStyle($summaryRange)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '2E86AB']
                ]
            ]
        ]);
    }
}