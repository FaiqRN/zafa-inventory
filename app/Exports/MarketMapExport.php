<?php

namespace App\Exports;

use App\Models\Toko;
use App\Models\Retur;
use App\Models\BarangToko;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MarketMapExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    protected $type;
    protected $filters;

    public function __construct($type = 'stores', $filters = [])
    {
        $this->type = $type;
        $this->filters = $filters;
    }

    /**
     * Return collection of data to export
     */
    public function collection()
    {
        switch ($this->type) {
            case 'stores':
                return $this->getStoreData();
            case 'clusters':
                return $this->getClusterData();
            case 'recommendations':
                return $this->getRecommendationData();
            default:
                return collect([]);
        }
    }

    /**
     * Return the headings for the spreadsheet
     */
    public function headings(): array
    {
        switch ($this->type) {
            case 'stores':
                return [
                    'Toko ID',
                    'Nama Toko',
                    'Pemilik',
                    'Alamat',
                    'Kelurahan',
                    'Kecamatan',
                    'Koordinat GPS',
                    'Latitude',
                    'Longitude',
                    'No. Telepon',
                    'Harga Awal (Rp)',
                    'Harga Jual (Rp)',
                    'Total Terjual',
                    'Revenue (Rp)',
                    'Profit per Unit (Rp)',
                    'Margin (%)',
                    'Total Profit (Rp)',
                    'ROI (%)',
                    'Status Profitabilitas',
                    'Kategori Performa'
                ];
            case 'clusters':
                return [
                    'Cluster ID',
                    'Total Toko',
                    'Center Latitude',
                    'Center Longitude',
                    'Area Coverage',
                    'Avg Margin (%)',
                    'Total Revenue (Rp)',
                    'Total Profit (Rp)',
                    'Expansion Potential',
                    'Status Saturasi',
                    'Rekomendasi',
                    'Daftar Toko'
                ];
            case 'recommendations':
                return [
                    'Rank',
                    'Cluster ID',
                    'Prioritas',
                    'Score',
                    'Area',
                    'Current Stores',
                    'Expansion Target',
                    'Avg Margin (%)',
                    'Total Revenue (Rp)',
                    'Projected Monthly Profit (Rp)',
                    'Investment Needed (Rp)',
                    'Payback Period (Months)',
                    'ROI Projection (%)',
                    'Risk Level',
                    'Action Plan'
                ];
            default:
                return [];
        }
    }

    /**
     * Map each row of data
     */
    public function map($row): array
    {
        switch ($this->type) {
            case 'stores':
                return $this->mapStoreData($row);
            case 'clusters':
                return $this->mapClusterData($row);
            case 'recommendations':
                return $this->mapRecommendationData($row);
            default:
                return [];
        }
    }

    /**
     * Get store data for export
     */
    protected function getStoreData()
    {
        $query = Toko::with(['barangToko.barang', 'retur'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_active', true);

        // Apply date filters
        if (isset($this->filters['date_from']) && isset($this->filters['date_to'])) {
            $query->whereHas('retur', function($q) {
                $q->whereBetween('tanggal_retur', [
                    $this->filters['date_from'],
                    $this->filters['date_to']
                ]);
            });
        }

        return $query->get()->map(function($toko) {
            $profitData = $this->calculateStoreProfit($toko);
            return (object) array_merge($toko->toArray(), $profitData);
        });
    }

    /**
     * Get cluster data for export
     */
    protected function getClusterData()
    {
        // Since clusters are generated dynamically, we need to create them here
        $stores = $this->getStoreData();
        $clusters = $this->createGeographicClusters($stores, 1.5);
        
        return collect($clusters)->map(function($cluster) {
            return (object) $cluster;
        });
    }

    /**
     * Get recommendation data for export
     */
    protected function getRecommendationData()
    {
        // Get clusters first, then generate recommendations
        $clusters = $this->getClusterData();
        $recommendations = [];
        
        foreach ($clusters as $cluster) {
            if ($cluster->avg_margin >= 10 && $cluster->expansion_potential > 0) {
                $score = $this->calculateExpansionScore($cluster);
                if ($score >= 50) {
                    $recommendations[] = $this->buildRecommendation($cluster, $score);
                }
            }
        }
        
        // Sort by score
        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return collect($recommendations)->map(function($rec, $index) {
            $rec['rank'] = $index + 1;
            return (object) $rec;
        });
    }

    /**
     * Map store data for Excel row
     */
    protected function mapStoreData($store): array
    {
        $coordinates = $store->latitude . ',' . $store->longitude;
        $performanceCategory = $this->getPerformanceCategory($store->margin_percent);
        $profitabilityStatus = $this->getProfitabilityStatus($store->margin_percent);
        
        return [
            $store->toko_id,
            $store->nama_toko,
            $store->pemilik ?? 'N/A',
            $store->alamat,
            $store->wilayah_kelurahan,
            $store->wilayah_kecamatan,
            $coordinates,
            $store->latitude,
            $store->longitude,
            $store->nomer_telpon ?? 'N/A',
            $store->harga_awal,
            $store->harga_jual,
            $store->total_terjual,
            $store->revenue,
            $store->profit_per_unit,
            round($store->margin_percent, 2),
            $store->total_profit,
            round($store->roi, 2),
            $profitabilityStatus,
            $performanceCategory
        ];
    }

    /**
     * Map cluster data for Excel row
     */
    protected function mapClusterData($cluster): array
    {
        $saturationStatus = $this->getSaturationStatus($cluster->total_stores);
        $recommendation = $this->getClusterRecommendation($cluster);
        $storeList = implode(', ', array_column($cluster->stores, 'nama_toko'));
        
        return [
            $cluster->id,
            $cluster->total_stores,
            $cluster->center_lat,
            $cluster->center_lng,
            implode(', ', $cluster->areas),
            round($cluster->avg_margin, 2),
            $cluster->total_revenue,
            $cluster->total_profit ?? 0,
            $cluster->expansion_potential,
            $saturationStatus,
            $recommendation,
            $storeList
        ];
    }

    /**
     * Map recommendation data for Excel row
     */
    protected function mapRecommendationData($rec): array
    {
        $riskLevel = $this->getRiskLevel($rec->score);
        $actionPlan = $this->getActionPlan($rec);
        $roiProjection = $rec->investment_needed > 0 ? 
            round(($rec->projected_monthly_profit * 12) / $rec->investment_needed * 100, 2) : 0;
        
        return [
            $rec->rank,
            $rec->cluster_id,
            $rec->priority,
            round($rec->score, 1),
            $rec->areas,
            $rec->current_stores ?? 0,
            $rec->expansion_count,
            round($rec->avg_margin, 2),
            $rec->total_revenue,
            $rec->projected_monthly_profit,
            $rec->investment_needed,
            $rec->payback_months,
            $roiProjection,
            $riskLevel,
            $actionPlan
        ];
    }

    /**
     * Calculate profit data for a store
     */
    protected function calculateStoreProfit($toko)
    {
        $returData = $toko->retur()->with('barang')->orderBy('tanggal_retur', 'desc')->first();

        if (!$returData) {
            return [
                'harga_awal' => 0,
                'harga_jual' => 0,
                'total_terjual' => 0,
                'revenue' => 0,
                'profit_per_unit' => 0,
                'margin_percent' => 0,
                'total_profit' => 0,
                'roi' => 0
            ];
        }

        $avgSellingPrice = $toko->barangToko()
            ->where('barang_id', $returData->barang_id)
            ->value('harga_barang_toko') ?? 0;

        $hargaAwal = $returData->harga_awal_barang ?? 0;
        $totalTerjual = $returData->total_terjual ?? 0;
        $revenue = $returData->hasil ?? 0;

        $profitPerUnit = $avgSellingPrice - $hargaAwal;
        $marginPercent = $avgSellingPrice > 0 ? ($profitPerUnit / $avgSellingPrice) * 100 : 0;
        $totalProfit = $profitPerUnit * $totalTerjual;
        $roi = ($hargaAwal * $totalTerjual) > 0 ? ($totalProfit / ($hargaAwal * $totalTerjual)) * 100 : 0;

        return [
            'harga_awal' => $hargaAwal,
            'harga_jual' => $avgSellingPrice,
            'total_terjual' => $totalTerjual,
            'revenue' => $revenue,
            'profit_per_unit' => $profitPerUnit,
            'margin_percent' => $marginPercent,
            'total_profit' => $totalProfit,
            'roi' => $roi
        ];
    }

    /**
     * Create geographic clusters
     */
    protected function createGeographicClusters($stores, $radiusKm)
    {
        $clusters = [];
        $processed = [];
        $clusterIndex = 0;

        foreach ($stores as $store) {
            if (in_array($store->toko_id, $processed)) {
                continue;
            }

            $cluster = [
                'id' => 'CLUSTER_' . chr(65 + $clusterIndex),
                'stores' => [$store],
                'center_lat' => (float) $store->latitude,
                'center_lng' => (float) $store->longitude
            ];

            foreach ($stores as $otherStore) {
                if ($otherStore->toko_id === $store->toko_id || 
                    in_array($otherStore->toko_id, $processed)) {
                    continue;
                }

                $distance = $this->calculateDistance(
                    $store->latitude, $store->longitude,
                    $otherStore->latitude, $otherStore->longitude
                );

                if ($distance <= $radiusKm) {
                    $cluster['stores'][] = $otherStore;
                    $processed[] = $otherStore->toko_id;
                }
            }

            $processed[] = $store->toko_id;
            $cluster = $this->calculateClusterMetrics($cluster);
            $clusters[] = $cluster;
            $clusterIndex++;
        }

        return $clusters;
    }

    /**
     * Calculate cluster metrics
     */
    protected function calculateClusterMetrics($cluster)
    {
        $storeCount = count($cluster['stores']);
        $totalLat = 0;
        $totalLng = 0;
        $totalMargin = 0;
        $totalRevenue = 0;
        $totalProfit = 0;
        $areas = [];

        foreach ($cluster['stores'] as $store) {
            $totalLat += $store->latitude;
            $totalLng += $store->longitude;
            $totalMargin += $store->margin_percent ?? 0;
            $totalRevenue += $store->revenue ?? 0;
            $totalProfit += $store->total_profit ?? 0;
            
            if (!in_array($store->wilayah_kelurahan, $areas)) {
                $areas[] = $store->wilayah_kelurahan;
            }
        }

        $cluster['center_lat'] = $totalLat / $storeCount;
        $cluster['center_lng'] = $totalLng / $storeCount;
        $cluster['total_stores'] = $storeCount;
        $cluster['avg_margin'] = $totalMargin / $storeCount;
        $cluster['total_revenue'] = $totalRevenue;
        $cluster['total_profit'] = $totalProfit;
        $cluster['areas'] = $areas;
        $cluster['expansion_potential'] = max(0, 5 - $storeCount);

        return $cluster;
    }

    /**
     * Calculate distance between coordinates
     */
    protected function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    /**
     * Calculate expansion score
     */
    protected function calculateExpansionScore($cluster)
    {
        $storeCount = $cluster->total_stores;
        $saturationScore = 0;
        
        if ($storeCount === 1) $saturationScore = 85;
        elseif ($storeCount <= 2) $saturationScore = 75;
        elseif ($storeCount <= 4) $saturationScore = 60;
        else $saturationScore = 20;

        $profitScore = min(100, max(0, $cluster->avg_margin * 3));
        $potentialScore = $cluster->expansion_potential * 20;

        return ($saturationScore * 0.4) + ($profitScore * 0.4) + ($potentialScore * 0.2);
    }

    /**
     * Build recommendation
     */
    protected function buildRecommendation($cluster, $score)
    {
        $priority = $cluster->avg_margin >= 20 ? 'TINGGI' : 
                   ($cluster->avg_margin >= 15 ? 'SEDANG' : 'RENDAH');
        
        $avgRevenue = $cluster->total_revenue / $cluster->total_stores;
        $projectedProfit = ($avgRevenue * 0.25) * $cluster->expansion_potential;
        $investmentNeeded = $cluster->expansion_potential * 1200000;
        $paybackMonths = $projectedProfit > 0 ? ceil($investmentNeeded / ($projectedProfit / 12)) : 999;

        return [
            'cluster_id' => $cluster->id,
            'priority' => $priority,
            'score' => $score,
            'expansion_count' => $cluster->expansion_potential,
            'avg_margin' => $cluster->avg_margin,
            'total_revenue' => $cluster->total_revenue,
            'areas' => implode(', ', $cluster->areas),
            'projected_monthly_profit' => round($projectedProfit / 12),
            'investment_needed' => $investmentNeeded,
            'payback_months' => $paybackMonths,
            'center_lat' => $cluster->center_lat,
            'center_lng' => $cluster->center_lng,
            'current_stores' => $cluster->total_stores
        ];
    }

    /**
     * Helper methods for mapping data
     */
    protected function getPerformanceCategory($margin)
    {
        if ($margin >= 20) return 'Excellent';
        if ($margin >= 15) return 'Good';
        if ($margin >= 10) return 'Fair';
        return 'Poor';
    }

    protected function getProfitabilityStatus($margin)
    {
        if ($margin >= 20) return 'Sangat Profitable';
        if ($margin >= 10) return 'Profitable';
        return 'Perlu Evaluasi';
    }

    protected function getSaturationStatus($storeCount)
    {
        if ($storeCount >= 5) return 'Saturated';
        if ($storeCount >= 3) return 'High Density';
        if ($storeCount >= 2) return 'Medium Density';
        return 'Low Density';
    }

    protected function getClusterRecommendation($cluster)
    {
        if ($cluster->expansion_potential === 0) return 'No Expansion - Saturated';
        if ($cluster->avg_margin >= 20) return 'Highly Recommended for Expansion';
        if ($cluster->avg_margin >= 15) return 'Recommended for Expansion';
        if ($cluster->avg_margin >= 10) return 'Consider Expansion with Caution';
        return 'Not Recommended for Expansion';
    }

    protected function getRiskLevel($score)
    {
        if ($score >= 80) return 'Low Risk';
        if ($score >= 60) return 'Medium Risk';
        if ($score >= 40) return 'High Risk';
        return 'Very High Risk';
    }

    protected function getActionPlan($rec)
    {
        $actions = [];
        
        if ($rec->priority === 'TINGGI') {
            $actions[] = 'Priority implementation';
            $actions[] = 'Immediate market survey';
            $actions[] = 'Fast-track approval process';
        } elseif ($rec->priority === 'SEDANG') {
            $actions[] = 'Detailed feasibility study';
            $actions[] = 'Competitor analysis';
            $actions[] = 'Phased implementation';
        } else {
            $actions[] = 'Extended evaluation period';
            $actions[] = 'Market condition monitoring';
            $actions[] = 'Risk assessment';
        }
        
        return implode('; ', $actions);
    }

    /**
     * Style the spreadsheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Data rows styling
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle('A2:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
        }

        // Freeze first row
        $sheet->freezePane('A2');

        return $sheet;
    }

    /**
     * Set column widths
     */
    public function columnWidths(): array
    {
        switch ($this->type) {
            case 'stores':
                return [
                    'A' => 12, 'B' => 20, 'C' => 15, 'D' => 25, 'E' => 15,
                    'F' => 15, 'G' => 18, 'H' => 12, 'I' => 12, 'J' => 15,
                    'K' => 15, 'L' => 15, 'M' => 12, 'N' => 15, 'O' => 15,
                    'P' => 12, 'Q' => 15, 'R' => 12, 'S' => 18, 'T' => 18
                ];
            case 'clusters':
                return [
                    'A' => 12, 'B' => 12, 'C' => 15, 'D' => 15, 'E' => 20,
                    'F' => 12, 'G' => 15, 'H' => 15, 'I' => 15, 'J' => 15,
                    'K' => 25, 'L' => 30
                ];
            case 'recommendations':
                return [
                    'A' => 8, 'B' => 12, 'C' => 12, 'D' => 10, 'E' => 20,
                    'F' => 12, 'G' => 15, 'H' => 12, 'I' => 15, 'J' => 18,
                    'K' => 18, 'L' => 18, 'M' => 15, 'N' => 12, 'O' => 25
                ];
            default:
                return [];
        }
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        $titles = [
            'stores' => 'Data Toko Market Map',
            'clusters' => 'Analisis Cluster Geografis',
            'recommendations' => 'Rekomendasi Ekspansi'
        ];

        return $titles[$this->type] ?? 'Market Map Export';
    }
}