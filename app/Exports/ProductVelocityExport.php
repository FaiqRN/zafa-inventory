<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProductVelocityExport implements WithMultipleSheets
{
    protected $productCategories;
    protected $velocityTrends;
    protected $locationDemand;
    protected $strategicRecommendations;

    public function __construct($productCategories, $velocityTrends = null, $locationDemand = null, $strategicRecommendations = null)
    {
        $this->productCategories = $productCategories;
        $this->velocityTrends = $velocityTrends;
        $this->locationDemand = $locationDemand;
        $this->strategicRecommendations = $strategicRecommendations;
    }

    public function sheets(): array
    {
        $sheets = [
            new ProductVelocityOverviewSheet($this->productCategories),
            new ProductVelocityDetailSheet($this->productCategories),
            new VelocityCategorySheet($this->productCategories),
        ];

        // Add conditional sheets
        if ($this->productCategories->has('Hot Seller') && $this->productCategories->get('Hot Seller')->count() > 0) {
            $sheets[] = new HotSellersSheet($this->productCategories);
        }

        if ($this->productCategories->has('Dead Stock') && $this->productCategories->get('Dead Stock')->count() > 0) {
            $sheets[] = new DeadStockSheet($this->productCategories);
        }

        if ($this->strategicRecommendations && !empty($this->strategicRecommendations)) {
            $sheets[] = new StrategicRecommendationsSheet($this->strategicRecommendations);
        }

        if ($this->velocityTrends && !empty($this->velocityTrends)) {
            $sheets[] = new VelocityTrendsSheet($this->velocityTrends);
        }

        if ($this->locationDemand && !empty($this->locationDemand)) {
            $sheets[] = new LocationDemandSheet($this->locationDemand);
        }

        return $sheets;
    }
}

class ProductVelocityOverviewSheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $productCategories;

    public function __construct($productCategories)
    {
        $this->productCategories = $productCategories;
    }

    public function collection()
    {
        $summary = [];
        
        try {
            if (!$this->productCategories || !is_iterable($this->productCategories)) {
                return collect([]);
            }

            foreach ($this->productCategories as $category => $products) {
                if (!$products || !is_iterable($products)) {
                    continue;
                }

                $productsCollection = is_array($products) ? collect($products) : $products;
                
                $totalProducts = $productsCollection->count();
                $avgVelocityScore = $productsCollection->avg('velocity_score') ?: 0;
                $avgSellThrough = $productsCollection->avg('avg_sell_through') ?: 0;
                $avgDaysToSell = $productsCollection->avg('avg_days_to_sell') ?: 0;
                $totalShipped = $productsCollection->sum('total_shipped') ?: 0;
                $totalSold = $productsCollection->sum('total_sold') ?: 0;
                $avgReturnRate = $productsCollection->avg('return_rate') ?: 0;
                
                $summary[] = [
                    'category' => $category,
                    'total_products' => $totalProducts,
                    'avg_velocity_score' => round($avgVelocityScore, 2),
                    'avg_sell_through' => round($avgSellThrough, 2) . '%',
                    'avg_days_to_sell' => round($avgDaysToSell, 1),
                    'avg_return_rate' => round($avgReturnRate, 2) . '%',
                    'total_shipped' => number_format($totalShipped),
                    'total_sold' => number_format($totalSold),
                    'sell_through_rate' => $totalShipped > 0 ? round(($totalSold / $totalShipped) * 100, 2) . '%' : '0%'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error in ProductVelocityOverviewSheet: ' . $e->getMessage());
            return collect([]);
        }

        return collect($summary);
    }

    public function headings(): array
    {
        return [
            'Velocity Category',
            'Total Products',
            'Avg Velocity Score',
            'Avg Sell-Through',
            'Avg Days to Sell',
            'Avg Return Rate',
            'Total Shipped',
            'Total Sold',
            'Overall Sell-Through'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply header styles
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Apply data styles
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:I' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, 'B' => 15, 'C' => 18, 'D' => 18, 'E' => 18,
            'F' => 15, 'G' => 15, 'H' => 15, 'I' => 18
        ];
    }

    public function title(): string
    {
        return 'Overview';
    }
}

class ProductVelocityDetailSheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $productCategories;

    public function __construct($productCategories)
    {
        $this->productCategories = $productCategories;
    }

    public function collection()
    {
        $data = [];
        
        try {
            if (!$this->productCategories || !is_iterable($this->productCategories)) {
                return collect([]);
            }

            foreach ($this->productCategories as $category => $products) {
                if (!$products || !is_iterable($products)) {
                    continue;
                }

                $productsCollection = is_array($products) ? collect($products) : $products;

                foreach ($productsCollection as $product) {
                    $productArray = is_array($product) ? $product : (is_object($product) ? $product->toArray() : []);
                    
                    $data[] = [
                        'product_code' => $this->getNestedValue($productArray, 'barang.barang_kode', ''),
                        'product_name' => $this->getNestedValue($productArray, 'barang.nama_barang', ''),
                        'velocity_category' => $productArray['velocity_category'] ?? '',
                        'velocity_score' => $productArray['velocity_score'] ?? 0,
                        'sell_through_rate' => ($productArray['avg_sell_through'] ?? 0) . '%',
                        'days_to_sell' => $productArray['avg_days_to_sell'] ?? 0,
                        'return_rate' => ($productArray['return_rate'] ?? 0) . '%',
                        'total_shipped' => number_format($productArray['total_shipped'] ?? 0),
                        'total_sold' => number_format($productArray['total_sold'] ?? 0),
                        'monthly_trend' => $productArray['monthly_trend'] ?? 'stable',
                        'recommendation' => $this->getRecommendation($productArray['velocity_category'] ?? ''),
                        'priority' => $this->getPriority($productArray['velocity_category'] ?? ''),
                        'potential_impact' => $this->getPotentialImpact($productArray),
                        'last_updated' => Carbon::now()->format('Y-m-d H:i:s')
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in ProductVelocityDetailSheet: ' . $e->getMessage());
            return collect([]);
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Product Code', 'Product Name', 'Velocity Category', 'Velocity Score',
            'Sell-Through Rate', 'Days to Sell', 'Return Rate', 'Total Shipped',
            'Total Sold', 'Monthly Trend', 'Recommendation', 'Priority',
            'Potential Impact', 'Last Updated'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply header styles
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '70AD47']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Apply data styles
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:N' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, 'B' => 25, 'C' => 15, 'D' => 12, 'E' => 15, 'F' => 12,
            'G' => 12, 'H' => 15, 'I' => 15, 'J' => 12, 'K' => 25, 'L' => 10,
            'M' => 20, 'N' => 18
        ];
    }

    public function title(): string
    {
        return 'Detailed Analysis';
    }

    private function getNestedValue($array, $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } elseif (is_object($value) && isset($value->$k)) {
                $value = $value->$k;
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    private function getRecommendation($category)
    {
        switch ($category) {
            case 'Hot Seller':
                return 'Increase production by 30-50%';
            case 'Good Mover':
                return 'Maintain current levels';
            case 'Slow Mover':
                return 'Reduce production by 20-30%';
            case 'Dead Stock':
                return 'Consider discontinuing';
            default:
                return 'Monitor performance';
        }
    }

    private function getPriority($category)
    {
        switch ($category) {
            case 'Hot Seller':
            case 'Dead Stock':
                return 'High';
            case 'Slow Mover':
                return 'Medium';
            case 'Good Mover':
            default:
                return 'Low';
        }
    }

    private function getPotentialImpact($product)
    {
        $baseRevenue = ($product['total_sold'] ?? 0) * 15000;
        $category = $product['velocity_category'] ?? '';
        
        switch ($category) {
            case 'Hot Seller':
                return 'Revenue increase: Rp ' . number_format($baseRevenue * 0.35);
            case 'Dead Stock':
                return 'Cost savings: Rp ' . number_format($baseRevenue * 0.15);
            case 'Slow Mover':
                return 'Optimization: Rp ' . number_format($baseRevenue * 0.10);
            case 'Good Mover':
                return 'Stable: Rp ' . number_format($baseRevenue);
            default:
                return 'To be determined';
        }
    }
}

class VelocityCategorySheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $productCategories;

    public function __construct($productCategories)
    {
        $this->productCategories = $productCategories;
    }

    public function collection()
    {
        $data = [];
        
        try {
            if (!$this->productCategories || !is_iterable($this->productCategories)) {
                return collect([]);
            }

            // Calculate total products first
            $totalProducts = 0;
            foreach ($this->productCategories as $products) {
                if ($products && is_iterable($products)) {
                    $productsCollection = is_array($products) ? collect($products) : $products;
                    $totalProducts += $productsCollection->count();
                }
            }

            foreach ($this->productCategories as $category => $products) {
                if (!$products || !is_iterable($products)) {
                    continue;
                }

                $productsCollection = is_array($products) ? collect($products) : $products;
                $count = $productsCollection->count();
                $percentage = $totalProducts > 0 ? round(($count / $totalProducts) * 100, 2) : 0;
                
                $data[] = [
                    'category' => $category,
                    'count' => $count,
                    'percentage' => $percentage . '%',
                    'description' => $this->getCategoryDescription($category),
                    'action_needed' => $this->getCategoryAction($category),
                    'business_impact' => $this->getBusinessImpact($category)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error in VelocityCategorySheet: ' . $e->getMessage());
            return collect([]);
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Velocity Category', 'Product Count', 'Percentage',
            'Description', 'Action Needed', 'Business Impact'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply header styles
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '000000']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'FFC000']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Apply data styles
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:F' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ]
            ]);

            // Center align for count and percentage columns
            $sheet->getStyle('B2:C' . $highestRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 12, 'C' => 12, 'D' => 30, 'E' => 25, 'F' => 25
        ];
    }

    public function title(): string
    {
        return 'Category Analysis';
    }

    private function getCategoryDescription($category)
    {
        switch ($category) {
            case 'Hot Seller':
                return '>80% sell-through, <14 days to sell. High demand products with excellent performance.';
            case 'Good Mover':
                return '60-80% sell-through, 14-30 days to sell. Stable products with good market acceptance.';
            case 'Slow Mover':
                return '30-60% sell-through, 30-60 days to sell. Products with moderate performance.';
            case 'Dead Stock':
                return '<30% sell-through, >60 days to sell. Poor performing products with low demand.';
            case 'No Data':
                return 'Insufficient data for analysis. New products or products with limited sales history.';
            default:
                return 'Category description not available.';
        }
    }

    private function getCategoryAction($category)
    {
        switch ($category) {
            case 'Hot Seller':
                return 'Increase production, expand distribution, prioritize marketing';
            case 'Good Mover':
                return 'Maintain current levels, monitor trends, optimize where possible';
            case 'Slow Mover':
                return 'Reduce production, investigate market fit, consider promotions';
            case 'Dead Stock':
                return 'Discontinue or liquidate, analyze failure factors';
            case 'No Data':
                return 'Continue monitoring, collect more performance data';
            default:
                return 'Action plan to be determined.';
        }
    }

    private function getBusinessImpact($category)
    {
        switch ($category) {
            case 'Hot Seller':
                return 'High revenue potential, resource priority, growth opportunity';
            case 'Good Mover':
                return 'Stable revenue, efficient operations, benchmark standard';
            case 'Slow Mover':
                return 'Resource optimization needed, moderate revenue contribution';
            case 'Dead Stock':
                return 'Cost burden, resource waste, negative cash flow impact';
            case 'No Data':
                return 'Impact assessment pending, monitoring required';
            default:
                return 'Business impact to be assessed.';
        }
    }
}

class HotSellersSheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $productCategories;

    public function __construct($productCategories)
    {
        $this->productCategories = $productCategories;
    }

    public function collection()
    {
        $data = [];
        
        try {
            $hotSellers = $this->productCategories->get('Hot Seller', collect());
            if (!$hotSellers || !is_iterable($hotSellers)) {
                return collect([]);
            }

            $hotSellersCollection = is_array($hotSellers) ? collect($hotSellers) : $hotSellers;
            $sortedHotSellers = $hotSellersCollection->sortByDesc('velocity_score');
            
            foreach ($sortedHotSellers as $product) {
                $productArray = is_array($product) ? $product : (is_object($product) ? $product->toArray() : []);
                
                $data[] = [
                    'ranking' => count($data) + 1,
                    'product_code' => $this->getNestedValue($productArray, 'barang.barang_kode', ''),
                    'product_name' => $this->getNestedValue($productArray, 'barang.nama_barang', ''),
                    'velocity_score' => $productArray['velocity_score'] ?? 0,
                    'sell_through_rate' => ($productArray['avg_sell_through'] ?? 0) . '%',
                    'days_to_sell' => $productArray['avg_days_to_sell'] ?? 0,
                    'total_sold' => number_format($productArray['total_sold'] ?? 0),
                    'return_rate' => ($productArray['return_rate'] ?? 0) . '%',
                    'recommended_increase' => $this->getRecommendedIncrease($productArray),
                    'target_markets' => 'All regions - high demand',
                    'expected_roi' => $this->calculateExpectedROI($productArray),
                    'implementation_timeline' => '2-4 weeks',
                    'risk_level' => 'Low',
                    'notes' => 'Monitor stock levels to prevent stockouts'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error in HotSellersSheet: ' . $e->getMessage());
            return collect([]);
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Ranking', 'Product Code', 'Product Name', 'Velocity Score',
            'Sell-Through Rate', 'Days to Sell', 'Total Sold', 'Return Rate',
            'Recommended Increase', 'Target Markets', 'Expected ROI',
            'Timeline', 'Risk Level', 'Notes'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply header styles
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'DC3545']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Apply data styles
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:N' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ]
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8, 'B' => 15, 'C' => 25, 'D' => 12, 'E' => 15, 'F' => 12,
            'G' => 12, 'H' => 12, 'I' => 18, 'J' => 20, 'K' => 15, 'L' => 12,
            'M' => 10, 'N' => 30
        ];
    }

    public function title(): string
    {
        return 'Hot Sellers';
    }

    private function getNestedValue($array, $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } elseif (is_object($value) && isset($value->$k)) {
                $value = $value->$k;
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    private function getRecommendedIncrease($product)
    {
        $sellThrough = $product['avg_sell_through'] ?? 0;
        $daysToSell = $product['avg_days_to_sell'] ?? 0;
        
        if ($sellThrough >= 90 && $daysToSell <= 7) {
            return '40-50%';
        } elseif ($sellThrough >= 85 && $daysToSell <= 10) {
            return '35-45%';
        } elseif ($sellThrough >= 80 && $daysToSell <= 14) {
            return '30-40%';
        } else {
            return '25-35%';
        }
    }

    private function calculateExpectedROI($product)
    {
        $baseRevenue = ($product['total_sold'] ?? 0) * 15000;
        $expectedIncrease = 0.30; // 30% average increase
        $additionalRevenue = $baseRevenue * $expectedIncrease;
        
        return 'Rp ' . number_format($additionalRevenue);
    }
}

class DeadStockSheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $productCategories;

    public function __construct($productCategories)
    {
        $this->productCategories = $productCategories;
    }

    public function collection()
    {
        $data = [];
        
        try {
            $deadStock = $this->productCategories->get('Dead Stock', collect());
            if (!$deadStock || !is_iterable($deadStock)) {
                return collect([]);
            }

            $deadStockCollection = is_array($deadStock) ? collect($deadStock) : $deadStock;
            $sortedDeadStock = $deadStockCollection->sortBy('velocity_score');
            
            foreach ($sortedDeadStock as $product) {
                $productArray = is_array($product) ? $product : (is_object($product) ? $product->toArray() : []);
                
                $data[] = [
                    'product_code' => $this->getNestedValue($productArray, 'barang.barang_kode', ''),
                    'product_name' => $this->getNestedValue($productArray, 'barang.nama_barang', ''),
                    'velocity_score' => $productArray['velocity_score'] ?? 0,
                    'sell_through_rate' => ($productArray['avg_sell_through'] ?? 0) . '%',
                    'days_to_sell' => $productArray['avg_days_to_sell'] ?? 0,
                    'return_rate' => ($productArray['return_rate'] ?? 0) . '%',
                    'total_shipped' => number_format($productArray['total_shipped'] ?? 0),
                    'total_sold' => number_format($productArray['total_sold'] ?? 0),
                    'estimated_inventory_value' => $this->estimateInventoryValue($productArray),
                    'holding_cost_monthly' => $this->calculateHoldingCost($productArray),
                    'liquidation_value' => $this->calculateLiquidationValue($productArray),
                    'recommendation' => 'Discontinue or heavy promotion',
                    'phase_out_timeline' => '6-12 weeks',
                    'alternative_products' => 'Review high-performing substitutes',
                    'action_priority' => 'High',
                    'notes' => 'Monitor for final sales opportunity before discontinuation'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error in DeadStockSheet: ' . $e->getMessage());
            return collect([]);
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Product Code', 'Product Name', 'Velocity Score', 'Sell-Through Rate',
            'Days to Sell', 'Return Rate', 'Total Shipped', 'Total Sold',
            'Est. Inventory Value', 'Holding Cost/Month', 'Liquidation Value',
            'Recommendation', 'Phase-Out Timeline', 'Alternative Products',
            'Priority', 'Notes'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply header styles
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '6C757D']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Apply data styles
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:P' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ]
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, 'B' => 25, 'C' => 12, 'D' => 15, 'E' => 12, 'F' => 12,
            'G' => 15, 'H' => 15, 'I' => 18, 'J' => 18, 'K' => 18, 'L' => 20,
            'M' => 15, 'N' => 20, 'O' => 10, 'P' => 30
        ];
    }

    public function title(): string
    {
        return 'Dead Stock Analysis';
    }

    private function getNestedValue($array, $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } elseif (is_object($value) && isset($value->$k)) {
                $value = $value->$k;
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    private function estimateInventoryValue($product)
    {
        $remainingStock = max(0, ($product['total_shipped'] ?? 0) - ($product['total_sold'] ?? 0));
        $avgPrice = 15000; // Estimated average price
        return 'Rp ' . number_format($remainingStock * $avgPrice);
    }

    private function calculateHoldingCost($product)
    {
        $remainingStock = max(0, ($product['total_shipped'] ?? 0) - ($product['total_sold'] ?? 0));
        $avgPrice = 15000;
        $holdingCostRate = 0.02; // 2% per month
        return 'Rp ' . number_format($remainingStock * $avgPrice * $holdingCostRate);
    }

    private function calculateLiquidationValue($product)
    {
        $remainingStock = max(0, ($product['total_shipped'] ?? 0) - ($product['total_sold'] ?? 0));
        $avgPrice = 15000;
        $liquidationRate = 0.6; // 60% of original value
        return 'Rp ' . number_format($remainingStock * $avgPrice * $liquidationRate);
    }
}

class StrategicRecommendationsSheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $strategicRecommendations;

    public function __construct($strategicRecommendations)
    {
        $this->strategicRecommendations = $strategicRecommendations;
    }

    public function collection()
    {
        $data = [];
        
        try {
            if (!$this->strategicRecommendations || !is_array($this->strategicRecommendations)) {
                return collect([]);
            }

            foreach ($this->strategicRecommendations as $type => $recommendations) {
                if (!is_array($recommendations)) {
                    continue;
                }

                foreach ($recommendations as $recommendation) {
                    $data[] = [
                        'recommendation_type' => ucwords(str_replace('_', ' ', $type)),
                        'product_name' => $recommendation['product'] ?? '',
                        'action' => $recommendation['action'] ?? '',
                        'reason' => $recommendation['reason'] ?? '',
                        'priority' => $recommendation['priority'] ?? '',
                        'velocity_score' => $recommendation['velocity_score'] ?? 0,
                        'sell_through' => ($recommendation['sell_through'] ?? 0) . '%',
                        'days_to_sell' => $recommendation['days_to_sell'] ?? 0,
                        'implementation_timeline' => $this->getImplementationTimeline($type),
                        'expected_outcome' => $this->getExpectedOutcome($type),
                        'success_metrics' => $this->getSuccessMetrics($type),
                        'assigned_to' => 'Production Manager',
                        'review_date' => Carbon::now()->addWeeks(4)->format('Y-m-d'),
                        'status' => 'Pending Review'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in StrategicRecommendationsSheet: ' . $e->getMessage());
            return collect([]);
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Recommendation Type', 'Product Name', 'Action', 'Reason', 'Priority',
            'Velocity Score', 'Sell-Through', 'Days to Sell', 'Timeline',
            'Expected Outcome', 'Success Metrics', 'Assigned To', 'Review Date', 'Status'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply header styles
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '8B5CF6']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Apply data styles
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:N' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ]
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 25, 'C' => 30, 'D' => 35, 'E' => 10, 'F' => 12,
            'G' => 12, 'H' => 12, 'I' => 15, 'J' => 25, 'K' => 25, 'L' => 18,
            'M' => 12, 'N' => 15
        ];
    }

    public function title(): string
    {
        return 'Strategic Recommendations';
    }

    private function getImplementationTimeline($type)
    {
        switch ($type) {
            case 'focus_increase':
                return '2-4 weeks';
            case 'reduce_discontinue':
                return '6-12 weeks';
            case 'optimize_improve':
                return '4-8 weeks';
            case 'monitor_analyze':
                return 'Ongoing';
            default:
                return '4-6 weeks';
        }
    }

    private function getExpectedOutcome($type)
    {
        switch ($type) {
            case 'focus_increase':
                return 'Increased revenue and market share';
            case 'reduce_discontinue':
                return 'Cost reduction and resource optimization';
            case 'optimize_improve':
                return 'Improved efficiency and performance';
            case 'monitor_analyze':
                return 'Better data for future decisions';
            default:
                return 'Performance improvement';
        }
    }

    private function getSuccessMetrics($type)
    {
        switch ($type) {
            case 'focus_increase':
                return 'Revenue increase >25%, stock turnover improvement';
            case 'reduce_discontinue':
                return 'Cost savings >15%, inventory reduction';
            case 'optimize_improve':
                return 'Sell-through rate improvement >10%';
            case 'monitor_analyze':
                return 'Data quality improvement, trend identification';
            default:
                return 'KPI improvement as defined';
        }
    }
}

class VelocityTrendsSheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $velocityTrends;

    public function __construct($velocityTrends)
    {
        $this->velocityTrends = $velocityTrends;
    }

    public function collection()
    {
        $data = [];
        
        try {
            if (!$this->velocityTrends || !is_array($this->velocityTrends)) {
                return collect([]);
            }

            $months = $this->generateMonthLabels(6);

            for ($index = 0; $index < count($months); $index++) {
                $month = $months[$index] ?? '';
                $hotSellers = $this->velocityTrends['hot_sellers'][$index] ?? 0;
                $goodMovers = $this->velocityTrends['good_movers'][$index] ?? 0;
                $slowMovers = $this->velocityTrends['slow_movers'][$index] ?? 0;
                $deadStock = $this->velocityTrends['dead_stock'][$index] ?? 0;
                
                $totalProducts = $hotSellers + $goodMovers + $slowMovers + $deadStock;
                
                $data[] = [
                    'month' => $month,
                    'hot_sellers' => $hotSellers,
                    'good_movers' => $goodMovers,
                    'slow_movers' => $slowMovers,
                    'dead_stock' => $deadStock,
                    'total_products' => $totalProducts,
                    'hot_seller_percentage' => $this->calculatePercentage('hot_sellers', $index),
                    'performance_trend' => $this->calculateTrend($index),
                    'notes' => $this->getTrendNotes($this->calculateTrend($index))
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error in VelocityTrendsSheet: ' . $e->getMessage());
            return collect([]);
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Month', 'Hot Sellers', 'Good Movers', 'Slow Movers', 'Dead Stock',
            'Total Products', 'Hot Seller %', 'Performance Trend', 'Notes'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply header styles
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '17A2B8']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Apply data styles
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:I' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ]
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, 'B' => 12, 'C' => 12, 'D' => 12, 'E' => 12,
            'F' => 12, 'G' => 12, 'H' => 15, 'I' => 30
        ];
    }

    public function title(): string
    {
        return 'Velocity Trends';
    }

    private function generateMonthLabels($count)
    {
        $months = [];
        for ($i = $count - 1; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $months[] = $date->format('M Y');
        }
        return $months;
    }

    private function calculatePercentage($category, $index)
    {
        if (!isset($this->velocityTrends[$category][$index])) {
            return '0%';
        }
        
        $total = ($this->velocityTrends['hot_sellers'][$index] ?? 0) + 
                ($this->velocityTrends['good_movers'][$index] ?? 0) + 
                ($this->velocityTrends['slow_movers'][$index] ?? 0) + 
                ($this->velocityTrends['dead_stock'][$index] ?? 0);
        
        if ($total == 0) {
            return '0%';
        }
        
        return round(($this->velocityTrends[$category][$index] / $total) * 100, 1) . '%';
    }

    private function calculateTrend($index)
    {
        if ($index == 0) {
            return 'Baseline';
        }
        
        $current = $this->velocityTrends['hot_sellers'][$index] ?? 0;
        $previous = $this->velocityTrends['hot_sellers'][$index - 1] ?? 0;
        
        if ($previous == 0) {
            return $current > 0 ? 'Improving' : 'Stable';
        }
        
        $change = (($current - $previous) / $previous) * 100;
        
        if ($change > 10) return 'Improving';
        if ($change < -10) return 'Declining';
        return 'Stable';
    }

    private function getTrendNotes($trend)
    {
        switch ($trend) {
            case 'Improving':
                return 'Positive momentum in hot sellers';
            case 'Declining':
                return 'Review product performance and market conditions';
            case 'Stable':
                return 'Consistent performance, monitor for changes';
            default:
                return 'Continue monitoring trends';
        }
    }
}

class LocationDemandSheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $locationDemand;

    public function __construct($locationDemand)
    {
        $this->locationDemand = $locationDemand;
    }

    public function collection()
    {
        $data = [];
        
        try {
            if (!$this->locationDemand || !is_array($this->locationDemand)) {
                return collect([]);
            }

            foreach ($this->locationDemand as $location => $percentage) {
                $data[] = [
                    'location' => $location,
                    'demand_percentage' => $percentage . '%',
                    'market_size' => $this->getMarketSize($location),
                    'growth_potential' => $this->getGrowthPotential($location),
                    'competition_level' => $this->getCompetitionLevel($location),
                    'recommended_strategy' => $this->getRecommendedStrategy($location, $percentage),
                    'priority_products' => $this->getPriorityProducts($location),
                    'expansion_opportunity' => $this->getExpansionOpportunity($location),
                    'notes' => $this->getLocationNotes($location)
                ];
            }

            // Sort by demand percentage descending
            return collect($data)->sortByDesc(function($item) {
                return (float)str_replace('%', '', $item['demand_percentage']);
            });
        } catch (\Exception $e) {
            Log::error('Error in LocationDemandSheet: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function headings(): array
    {
        return [
            'Location', 'Demand %', 'Market Size', 'Growth Potential',
            'Competition Level', 'Recommended Strategy', 'Priority Products',
            'Expansion Opportunity', 'Notes'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply header styles
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '20C997']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Apply data styles
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:I' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ]
            ]);

            // Center align for demand percentage
            $sheet->getStyle('B2:B' . $highestRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 12, 'C' => 15, 'D' => 15, 'E' => 15,
            'F' => 25, 'G' => 20, 'H' => 20, 'I' => 30
        ];
    }

    public function title(): string
    {
        return 'Location Demand Analysis';
    }

    private function getMarketSize($location)
    {
        switch ($location) {
            case 'Malang Kota':
                return 'Large';
            case 'Malang Kabupaten':
                return 'Medium';
            case 'Kota Batu':
                return 'Small-Medium';
            default:
                return 'Small';
        }
    }

    private function getGrowthPotential($location)
    {
        switch ($location) {
            case 'Malang Kota':
            case 'Kota Batu':
                return 'High';
            case 'Malang Kabupaten':
                return 'Medium';
            default:
                return 'Low-Medium';
        }
    }

    private function getCompetitionLevel($location)
    {
        switch ($location) {
            case 'Malang Kota':
                return 'High';
            case 'Malang Kabupaten':
            case 'Kota Batu':
                return 'Medium';
            default:
                return 'Low';
        }
    }

    private function getRecommendedStrategy($location, $percentage)
    {
        if ($percentage >= 40) {
            return 'Maintain dominance, premium positioning';
        } elseif ($percentage >= 25) {
            return 'Strengthen market share, competitive pricing';
        } elseif ($percentage >= 15) {
            return 'Growth strategy, increase distribution';
        } else {
            return 'Market development, strategic partnerships';
        }
    }

    private function getPriorityProducts($location)
    {
        switch ($location) {
            case 'Malang Kota':
                return 'Premium products, variety packs';
            case 'Malang Kabupaten':
                return 'Value products, bulk sizes';
            case 'Kota Batu':
                return 'Tourist-friendly, gift packs';
            default:
                return 'Basic products, competitive pricing';
        }
    }

    private function getExpansionOpportunity($location)
    {
        switch ($location) {
            case 'Malang Kota':
                return 'New product lines, premium segments';
            case 'Kota Batu':
                return 'Tourism market, seasonal products';
            case 'Malang Kabupaten':
                return 'Rural distribution, value segments';
            default:
                return 'Market entry, partnership opportunities';
        }
    }

    private function getLocationNotes($location)
    {
        switch ($location) {
            case 'Malang Kota':
                return 'Largest market with highest competition. Focus on brand differentiation and quality.';
            case 'Malang Kabupaten':
                return 'Price-sensitive market with good growth potential. Value proposition important.';
            case 'Kota Batu':
                return 'Tourism-driven demand with seasonal variations. Consider tourist-friendly packaging.';
            default:
                return 'Emerging market with development potential. Monitor for growth opportunities.';
        }
    }
}