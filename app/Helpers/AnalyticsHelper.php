<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class AnalyticsHelper
{
    /**
     * Calculate growth rate between two values
     */
    public static function calculateGrowthRate($oldValue, $newValue)
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return (($newValue - $oldValue) / $oldValue) * 100;
    }

    /**
     * Calculate moving average
     */
    public static function calculateMovingAverage(array $values, int $period = 3)
    {
        if (count($values) < $period) {
            return array_sum($values) / count($values);
        }
        
        $lastValues = array_slice($values, -$period);
        return array_sum($lastValues) / $period;
    }

    /**
     * Calculate standard deviation
     */
    public static function calculateStandardDeviation(array $values)
    {
        $count = count($values);
        if ($count < 2) return 0;
        
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / ($count - 1);
        
        return sqrt($variance);
    }

    /**
     * Calculate coefficient of variation (CV)
     */
    public static function calculateCoefficientOfVariation(array $values)
    {
        $mean = array_sum($values) / count($values);
        if ($mean == 0) return 0;
        
        $stdDev = self::calculateStandardDeviation($values);
        return ($stdDev / $mean) * 100;
    }

    /**
     * Categorize performance based on score
     */
    public static function categorizePerformance($score, $thresholds = null)
    {
        $thresholds = $thresholds ?? [
            'excellent' => 90,
            'good' => 75,
            'fair' => 60,
            'poor' => 40
        ];
        
        if ($score >= $thresholds['excellent']) return 'excellent';
        if ($score >= $thresholds['good']) return 'good';
        if ($score >= $thresholds['fair']) return 'fair';
        if ($score >= $thresholds['poor']) return 'poor';
        return 'very_poor';
    }

    /**
     * Generate color based on performance
     */
    public static function getPerformanceColor($performance)
    {
        $colors = [
            'excellent' => '#28a745',
            'good' => '#007bff',
            'fair' => '#ffc107',
            'poor' => '#fd7e14',
            'very_poor' => '#dc3545'
        ];
        
        return $colors[$performance] ?? '#6c757d';
    }

    /**
     * Calculate seasonal index for Indonesian market
     */
    public static function calculateSeasonalIndex($currentMonth, $historicalData = null)
    {
        // Default seasonal patterns for Indonesian market (food/snack industry)
        $defaultSeasonalIndices = [
            1 => 1.1,   // January - New Year celebrations
            2 => 0.95,  // February - Post holiday normalization
            3 => 1.2,   // March - Ramadan preparation period
            4 => 1.15,  // April - Ramadan/Eid festivities
            5 => 1.0,   // May - Back to normal
            6 => 1.1,   // June - School holidays begin
            7 => 1.1,   // July - School holidays continue
            8 => 1.0,   // August - Back to school
            9 => 1.0,   // September - Normal period
            10 => 0.95, // October - Early rainy season
            11 => 0.95, // November - Rainy season
            12 => 1.15  // December - Year end holidays
        ];
        
        if ($historicalData && count($historicalData) >= 12) {
            return self::calculateActualSeasonalIndex($currentMonth, $historicalData);
        }
        
        return $defaultSeasonalIndices[$currentMonth] ?? 1.0;
    }

    /**
     * Calculate actual seasonal index from historical data
     */
    private static function calculateActualSeasonalIndex($targetMonth, $historicalData)
    {
        $monthlyAverages = [];
        $overallAverage = array_sum($historicalData) / count($historicalData);
        
        // Group data by month
        foreach ($historicalData as $index => $value) {
            $month = ($index % 12) + 1;
            if (!isset($monthlyAverages[$month])) {
                $monthlyAverages[$month] = [];
            }
            $monthlyAverages[$month][] = $value;
        }
        
        // Calculate average for target month
        if (isset($monthlyAverages[$targetMonth])) {
            $monthAverage = array_sum($monthlyAverages[$targetMonth]) / count($monthlyAverages[$targetMonth]);
            return $overallAverage > 0 ? $monthAverage / $overallAverage : 1.0;
        }
        
        return 1.0;
    }

    /**
     * Calculate trend direction and strength
     */
    public static function calculateTrend(array $values, $periods = 3)
    {
        if (count($values) < $periods + 1) {
            return ['direction' => 'stable', 'strength' => 0, 'change_percent' => 0];
        }
        
        $recent = array_slice($values, -$periods);
        $previous = array_slice($values, -($periods * 2), $periods);
        
        $recentAvg = array_sum($recent) / count($recent);
        $previousAvg = array_sum($previous) / count($previous);
        
        if ($previousAvg == 0) {
            return ['direction' => 'stable', 'strength' => 0, 'change_percent' => 0];
        }
        
        $change = (($recentAvg - $previousAvg) / $previousAvg) * 100;
        
        $direction = 'stable';
        if ($change > 5) $direction = 'increasing';
        elseif ($change < -5) $direction = 'decreasing';
        
        return [
            'direction' => $direction,
            'strength' => abs($change),
            'change_percent' => round($change, 2)
        ];
    }

    /**
     * Normalize value to 0-100 scale
     */
    public static function normalizeValue($value, $min, $max)
    {
        if ($max == $min) return 50; // Default middle value
        
        return (($value - $min) / ($max - $min)) * 100;
    }

    /**
     * Calculate weighted score
     */
    public static function calculateWeightedScore(array $values, array $weights)
    {
        if (count($values) !== count($weights)) {
            throw new \InvalidArgumentException('Values and weights arrays must have the same length');
        }
        
        $weightedSum = 0;
        $totalWeight = array_sum($weights);
        
        for ($i = 0; $i < count($values); $i++) {
            $weightedSum += $values[$i] * $weights[$i];
        }
        
        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Generate prediction confidence based on data quality
     */
    public static function calculatePredictionConfidence($dataPoints, $dataVariability, $trendConsistency)
    {
        // Base confidence on data points (more data = higher confidence)
        $dataPointsScore = min(100, ($dataPoints / 24) * 100); // 24 months = 100%
        
        // Reduce confidence based on variability (lower CV = higher confidence)
        $variabilityScore = max(0, 100 - $dataVariability);
        
        // Boost confidence for consistent trends
        $trendScore = $trendConsistency * 100;
        
        // Weighted average with emphasis on data quality
        $confidence = self::calculateWeightedScore(
            [$dataPointsScore, $variabilityScore, $trendScore],
            [0.4, 0.4, 0.2]
        );
        
        return round(max(50, min(95, $confidence)), 0);
    }

    /**
     * Calculate inventory turnover rate
     */
    public static function calculateInventoryTurnover($soldUnits, $averageInventory)
    {
        if ($averageInventory == 0) return 0;
        
        return $soldUnits / $averageInventory;
    }

    /**
     * Calculate ABC classification for products/partners
     */
    public static function calculateABCClassification(Collection $items, $valueField = 'revenue')
    {
        $sorted = $items->sortByDesc($valueField);
        $totalValue = $sorted->sum($valueField);
        
        if ($totalValue == 0) {
            return [];
        }
        
        $runningTotal = 0;
        $classifications = [];
        
        foreach ($sorted as $index => $item) {
            $itemValue = $item[$valueField] ?? 0;
            $runningTotal += $itemValue;
            $percentage = ($runningTotal / $totalValue) * 100;
            
            if ($percentage <= 80) {
                $classification = 'A';
            } elseif ($percentage <= 95) {
                $classification = 'B';
            } else {
                $classification = 'C';
            }
            
            $itemId = $item['id'] ?? $item['toko_id'] ?? $item['barang_id'] ?? $index;
            
            $classifications[$itemId] = [
                'classification' => $classification,
                'cumulative_percentage' => round($percentage, 2),
                'value_percentage' => round(($itemValue / $totalValue) * 100, 2)
            ];
        }
        
        return $classifications;
    }

    /**
     * Generate Monte Carlo simulation for demand forecasting
     */
    public static function monteCarloForecast(array $historicalDemand, $periods = 6, $iterations = 1000)
    {
        if (empty($historicalDemand)) {
            return [];
        }
        
        $mean = array_sum($historicalDemand) / count($historicalDemand);
        $stdDev = self::calculateStandardDeviation($historicalDemand);
        
        $forecasts = [];
        
        for ($period = 1; $period <= $periods; $period++) {
            $periodForecasts = [];
            
            for ($i = 0; $i < $iterations; $i++) {
                // Generate random normal distribution
                $random = self::generateNormalRandom($mean, $stdDev);
                $periodForecasts[] = max(0, $random); // Ensure non-negative
            }
            
            $forecasts[$period] = [
                'mean' => array_sum($periodForecasts) / count($periodForecasts),
                'median' => self::calculateMedian($periodForecasts),
                'percentile_10' => self::calculatePercentile($periodForecasts, 10),
                'percentile_90' => self::calculatePercentile($periodForecasts, 90),
                'std_dev' => self::calculateStandardDeviation($periodForecasts)
            ];
        }
        
        return $forecasts;
    }

    /**
     * Generate random number from normal distribution using Box-Muller transform
     */
    private static function generateNormalRandom($mean, $stdDev)
    {
        static $has_spare = false;
        static $spare;
        
        if ($has_spare) {
            $has_spare = false;
            return $spare * $stdDev + $mean;
        }
        
        $has_spare = true;
        
        do {
            $u = (mt_rand() / mt_getrandmax()) * 2 - 1;
            $v = (mt_rand() / mt_getrandmax()) * 2 - 1;
            $s = $u * $u + $v * $v;
        } while ($s >= 1 || $s == 0);
        
        $s = sqrt(-2 * log($s) / $s);
        $spare = $v * $s;
        
        return $u * $s * $stdDev + $mean;
    }

    /**
     * Calculate median of array
     */
    private static function calculateMedian(array $values)
    {
        sort($values);
        $count = count($values);
        $middle = intval($count / 2);
        
        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            return $values[$middle];
        }
    }

    /**
     * Calculate percentile of array
     */
    private static function calculatePercentile(array $values, $percentile)
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower == $upper) {
            return $values[$lower];
        }
        
        $weight = $index - $lower;
        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }

    /**
     * Calculate Pareto analysis (80/20 rule)
     */
    public static function paretoAnalysis(Collection $items, $valueField = 'value')
    {
        $sorted = $items->sortByDesc($valueField);
        $totalValue = $sorted->sum($valueField);
        $totalCount = $sorted->count();
        
        if ($totalValue == 0 || $totalCount == 0) {
            return [
                'items' => [],
                'pareto_point' => null,
                'vital_few_count' => 0,
                'vital_few_percent' => 0
            ];
        }
        
        $runningValue = 0;
        $runningCount = 0;
        $paretoPoint = null;
        $results = [];
        
        foreach ($sorted as $index => $item) {
            $itemValue = $item[$valueField] ?? 0;
            $runningValue += $itemValue;
            $runningCount++;
            
            $valuePercent = ($runningValue / $totalValue) * 100;
            $countPercent = ($runningCount / $totalCount) * 100;
            
            $results[] = [
                'item' => $item,
                'cumulative_value_percent' => round($valuePercent, 2),
                'cumulative_count_percent' => round($countPercent, 2),
                'is_vital_few' => $valuePercent <= 80
            ];
            
            // Find 80/20 point
            if ($paretoPoint === null && $valuePercent >= 80) {
                $paretoPoint = [
                    'value_percent' => round($valuePercent, 2),
                    'count_percent' => round($countPercent, 2),
                    'items_count' => $runningCount
                ];
            }
        }
        
        $vitalFewCount = collect($results)->where('is_vital_few', true)->count();
        
        return [
            'items' => $results,
            'pareto_point' => $paretoPoint,
            'vital_few_count' => $vitalFewCount,
            'vital_few_percent' => $totalCount > 0 ? round(($vitalFewCount / $totalCount) * 100, 2) : 0
        ];
    }

    /**
     * Calculate correlation coefficient between two datasets
     */
    public static function calculateCorrelation(array $x, array $y)
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return 0;
        }
        
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumXX = 0;
        $sumYY = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumXX += $x[$i] * $x[$i];
            $sumYY += $y[$i] * $y[$i];
        }
        
        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumXX - $sumX * $sumX) * ($n * $sumYY - $sumY * $sumY));
        
        return $denominator != 0 ? $numerator / $denominator : 0;
    }

    /**
     * Format large numbers with appropriate units (K, M, B)
     */
    public static function formatLargeNumber($number, $precision = 1)
    {
        $abs = abs($number);
        $sign = $number < 0 ? '-' : '';
        
        if ($abs >= 1000000000) {
            return $sign . round($abs / 1000000000, $precision) . 'B';
        } elseif ($abs >= 1000000) {
            return $sign . round($abs / 1000000, $precision) . 'M';
        } elseif ($abs >= 1000) {
            return $sign . round($abs / 1000, $precision) . 'K';
        }
        
        return number_format($number, 0);
    }

    /**
     * Calculate business days between two dates (excluding weekends)
     */
    public static function getBusinessDays(Carbon $startDate, Carbon $endDate)
    {
        $businessDays = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekday()) {
                $businessDays++;
            }
            $currentDate->addDay();
        }
        
        return $businessDays;
    }

    /**
     * Generate color palette for charts
     */
    public static function generateColorPalette($count, $type = 'default')
    {
        $palettes = [
            'default' => ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1', '#fd7e14', '#20c997'],
            'performance' => ['#28a745', '#007bff', '#ffc107', '#fd7e14', '#dc3545'],
            'risk' => ['#dc3545', '#fd7e14', '#ffc107', '#28a745'],
            'seasonal' => ['#e3f2fd', '#bbdefb', '#90caf9', '#64b5f6', '#42a5f5', '#2196f3'],
            'velocity' => ['#dc3545', '#28a745', '#ffc107', '#6c757d'] // Hot, Good, Slow, Dead
        ];
        
        $colors = $palettes[$type] ?? $palettes['default'];
        $result = [];
        
        for ($i = 0; $i < $count; $i++) {
            $result[] = $colors[$i % count($colors)];
        }
        
        return $result;
    }

    /**
     * Calculate heat map intensity (0 to 1)
     */
    public static function calculateHeatMapIntensity($value, $min, $max)
    {
        if ($max == $min) return 0.5;
        
        $normalized = ($value - $min) / ($max - $min);
        return max(0, min(1, $normalized));
    }

    /**
     * Convert RGB to HEX color
     */
    public static function rgbToHex($r, $g, $b)
    {
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Generate gradient color between two colors
     */
    public static function generateGradientColor($startColor, $endColor, $ratio)
    {
        // Convert hex to RGB
        $startRgb = [
            hexdec(substr($startColor, 1, 2)),
            hexdec(substr($startColor, 3, 2)),
            hexdec(substr($startColor, 5, 2))
        ];
        
        $endRgb = [
            hexdec(substr($endColor, 1, 2)),
            hexdec(substr($endColor, 3, 2)),
            hexdec(substr($endColor, 5, 2))
        ];
        
        // Interpolate
        $r = round($startRgb[0] + ($endRgb[0] - $startRgb[0]) * $ratio);
        $g = round($startRgb[1] + ($endRgb[1] - $startRgb[1]) * $ratio);
        $b = round($startRgb[2] + ($endRgb[2] - $startRgb[2]) * $ratio);
        
        return self::rgbToHex($r, $g, $b);
    }

    /**
     * Calculate compound annual growth rate (CAGR)
     */
    public static function calculateCAGR($beginningValue, $endingValue, $numberOfPeriods)
    {
        if ($beginningValue <= 0 || $numberOfPeriods <= 0) {
            return 0;
        }
        
        return (pow($endingValue / $beginningValue, 1 / $numberOfPeriods) - 1) * 100;
    }

    /**
     * Generate insights summary based on data analysis
     */
    public static function generateInsightsSummary($data, $type = 'performance')
    {
        $insights = [];
        
        switch ($type) {
            case 'performance':
                $insights = self::generatePerformanceInsights($data);
                break;
            case 'inventory':
                $insights = self::generateInventoryInsights($data);
                break;
            case 'velocity':
                $insights = self::generateVelocityInsights($data);
                break;
            case 'profitability':
                $insights = self::generateProfitabilityInsights($data);
                break;
        }
        
        return $insights;
    }

    /**
     * Generate performance insights
     */
    private static function generatePerformanceInsights($data)
    {
        $insights = [];
        $dataCollection = collect($data);
        
        // Top performers analysis
        $topPerformers = $dataCollection->where('performance_score', '>=', 80);
        if ($topPerformers->isNotEmpty()) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Strong Performance Detected',
                'description' => "Found {$topPerformers->count()} high-performing partners with excellent sell-through rates.",
                'action' => 'Consider increasing allocation to these partners.',
                'impact' => 'high'
            ];
        }
        
        // Underperformers analysis
        $underperformers = $dataCollection->where('performance_score', '<', 60);
        if ($underperformers->isNotEmpty()) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Performance Issues Identified',
                'description' => "{$underperformers->count()} partners showing concerning performance metrics.",
                'action' => 'Immediate review and optimization required.',
                'impact' => 'high'
            ];
        }
        
        // Consistency analysis
        $inconsistentPartners = $dataCollection->where('consistency_score', '<', 70);
        if ($inconsistentPartners->isNotEmpty()) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Consistency Opportunities',
                'description' => "{$inconsistentPartners->count()} partners show inconsistent performance patterns.",
                'action' => 'Implement standardized processes and training.',
                'impact' => 'medium'
            ];
        }
        
        return $insights;
    }

    /**
     * Generate inventory insights
     */
    private static function generateInventoryInsights($data)
    {
        $insights = [];
        $dataCollection = collect($data);
        
        // High waste opportunities
        $highWaste = $dataCollection->where('improvement_percentage', '>', 30);
        if ($highWaste->isNotEmpty()) {
            $totalSavings = $highWaste->sum('potential_savings');
            $insights[] = [
                'type' => 'opportunity',
                'title' => 'Significant Waste Reduction Potential',
                'description' => "Potential savings of Rp " . number_format($totalSavings) . " identified across {$highWaste->count()} product-store combinations.",
                'action' => 'Implement optimized allocation immediately.',
                'impact' => 'high'
            ];
        }
        
        return $insights;
    }

    /**
     * Generate velocity insights
     */
    private static function generateVelocityInsights($data)
    {
        $insights = [];
        
        if (isset($data['Hot Seller']) && $data['Hot Seller']->isNotEmpty()) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Hot Sellers Identified',
                'description' => "Found {$data['Hot Seller']->count()} products with exceptional velocity.",
                'action' => 'Increase production and marketing focus on these products.',
                'impact' => 'high'
            ];
        }
        
        if (isset($data['Dead Stock']) && $data['Dead Stock']->isNotEmpty()) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Dead Stock Alert',
                'description' => "{$data['Dead Stock']->count()} products showing poor velocity metrics.",
                'action' => 'Consider discontinuation or repositioning strategies.',
                'impact' => 'medium'
            ];
        }
        
        return $insights;
    }

    /**
     * Generate profitability insights
     */
    private static function generateProfitabilityInsights($data)
    {
        $insights = [];
        $dataCollection = collect($data);
        
        // Loss makers
        $lossMakers = $dataCollection->where('roi', '<', 0);
        if ($lossMakers->isNotEmpty()) {
            $insights[] = [
                'type' => 'critical',
                'title' => 'Loss-Making Partnerships Detected',
                'description' => "{$lossMakers->count()} partnerships currently generating negative ROI.",
                'action' => 'Immediate review and potential termination required.',
                'impact' => 'critical'
            ];
        }
        
        // High performers
        $highROI = $dataCollection->where('roi', '>', 25);
        if ($highROI->isNotEmpty()) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Highly Profitable Partnerships',
                'description' => "{$highROI->count()} partnerships delivering excellent ROI above 25%.",
                'action' => 'Expand relationships with these high-value partners.',
                'impact' => 'high'
            ];
        }
        
        return $insights;
    }

    /**
     * Calculate Z-score for outlier detection
     */
    public static function calculateZScore($value, $mean, $stdDev)
    {
        if ($stdDev == 0) return 0;
        return ($value - $mean) / $stdDev;
    }

    /**
     * Detect outliers using Z-score method
     */
    public static function detectOutliers(array $values, $threshold = 2.5)
    {
        if (count($values) < 3) return [];
        
        $mean = array_sum($values) / count($values);
        $stdDev = self::calculateStandardDeviation($values);
        
        $outliers = [];
        foreach ($values as $index => $value) {
            $zScore = self::calculateZScore($value, $mean, $stdDev);
            if (abs($zScore) > $threshold) {
                $outliers[$index] = [
                    'value' => $value,
                    'z_score' => $zScore,
                    'type' => $zScore > 0 ? 'high' : 'low'
                ];
            }
        }
        
        return $outliers;
    }

    /**
     * Calculate simple linear regression
     */
    public static function calculateLinearRegression(array $x, array $y)
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return null;
        }
        
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumXX = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumXX += $x[$i] * $x[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'equation' => "y = {$slope}x + {$intercept}"
        ];
    }
}