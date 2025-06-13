<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProfitabilityAnalysisExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $profitability;

    public function __construct($profitability)
    {
        $this->profitability = $profitability;
    }

    public function collection()
    {
        return $this->profitability->map(function ($partner, $index) {
            return [
                'rank' => $index + 1,
                'partner_name' => $partner['toko']->nama_toko,
                'partner_id' => $partner['toko']->toko_id,
                'revenue' => number_format($partner['revenue']),
                'cogs' => number_format($partner['cogs']),
                'logistics_cost' => number_format($partner['logistics_cost']),
                'opportunity_cost' => number_format($partner['opportunity_cost']),
                'time_value_cost' => number_format($partner['time_value_cost']),
                'total_costs' => number_format($partner['total_costs']),
                'net_profit' => number_format($partner['net_profit']),
                'roi' => $partner['roi'] . '%',
                'profit_margin' => $partner['profit_margin'] . '%',
                'cogs_percentage' => $partner['cost_breakdown']['cogs_percentage'] . '%',
                'logistics_percentage' => $partner['cost_breakdown']['logistics_percentage'] . '%',
                'opportunity_percentage' => $partner['cost_breakdown']['opportunity_percentage'] . '%',
                'time_value_percentage' => $partner['cost_breakdown']['time_value_percentage'] . '%',
                'profitability_grade' => $this->getProfitabilityGrade($partner['roi']),
                'risk_level' => $this->getRiskLevel($partner['roi']),
                'action_required' => $this->getActionRequired($partner['roi']),
                'optimization_potential' => $this->getOptimizationPotential($partner)
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Partner Name',
            'Partner ID',
            'Revenue (Rp)',
            'COGS (Rp)',
            'Logistics Cost (Rp)',
            'Opportunity Cost (Rp)',
            'Time Value Cost (Rp)',
            'Total Costs (Rp)',
            'Net Profit (Rp)',
            'ROI (%)',
            'Profit Margin (%)',
            'COGS %',
            'Logistics %',
            'Opportunity %',
            'Time Value %',
            'Profitability Grade',
            'Risk Level',
            'Action Required',
            'Optimization Potential'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '17a2b8']
                ]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 25,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 12,
            'L' => 15,
            'M' => 12,
            'N' => 12,
            'O' => 12,
            'P' => 12,
            'Q' => 18,
            'R' => 12,
            'S' => 20,
            'T' => 20
        ];
    }

    private function getProfitabilityGrade($roi)
    {
        if ($roi >= 30) return 'A+';
        if ($roi >= 25) return 'A';
        if ($roi >= 20) return 'B+';
        if ($roi >= 15) return 'B';
        if ($roi >= 10) return 'C+';
        if ($roi >= 5) return 'C';
        return 'D';
    }

    private function getRiskLevel($roi)
    {
        if ($roi < 5) return 'High Risk';
        if ($roi < 15) return 'Medium Risk';
        if ($roi < 25) return 'Low Risk';
        return 'Very Low Risk';
    }

    private function getActionRequired($roi)
    {
        if ($roi < 5) return 'Consider termination';
        if ($roi < 15) return 'Cost optimization needed';
        if ($roi < 25) return 'Performance monitoring';
        return 'Maintain/Expand';
    }

    private function getOptimizationPotential($partner)
    {
        $potential = [];
        
        if ($partner['cost_breakdown']['logistics_percentage'] > 15) {
            $potential[] = 'Reduce logistics costs';
        }
        
        if ($partner['cost_breakdown']['opportunity_percentage'] > 10) {
            $potential[] = 'Faster inventory turnover';
        }
        
        if ($partner['cost_breakdown']['time_value_percentage'] > 8) {
            $potential[] = 'Improve payment terms';
        }
        
        return implode('; ', $potential) ?: 'Maintain current efficiency';
    }
}