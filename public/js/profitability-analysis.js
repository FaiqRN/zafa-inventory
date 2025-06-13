/**
 * PROFITABILITY ANALYSIS - ZAFA POTATO
 * JavaScript Module untuk True Profitability Analysis
 */

class ProfitabilityAnalytics {
    constructor() {
        this.charts = {};
        this.currentData = {};
        this.colors = {
            excellent: '#28a745',    // ROI >= 30%
            good: '#17a2b8',        // ROI 20-29%
            average: '#ffc107',     // ROI 10-19%
            poor: '#fd7e14',        // ROI 0-9%
            loss: '#dc3545'         // ROI < 0%
        };
        
        this.costColors = {
            cogs: '#007bff',
            logistics: '#ffc107',
            opportunity: '#17a2b8',
            time_value: '#dc3545',
            operational: '#6c757d'
        };
        
        this.init();
    }

    async init() {
        try {
            await this.loadData();
            this.createCharts();
            this.setupEventHandlers();
            this.updateStatistics();
            this.startPeriodicUpdates();
        } catch (error) {
            console.error('Failed to initialize Profitability Analytics:', error);
            this.showNotification('Failed to load profitability data', 'error');
        }
    }

    async loadData() {
        try {
            const response = await fetch('/analytics/profitability-analysis/api/data');
            const result = await response.json();
            
            if (result.success) {
                this.currentData = result.data;
                return result.data;
            } else {
                throw new Error('Failed to load data');
            }
        } catch (error) {
            console.error('Error loading profitability data:', error);
            throw error;
        }
    }

    createCharts() {
        this.createProfitabilityRankingChart();
        this.createCostBreakdownChart();
        this.createROIDistributionChart();
        this.createProfitabilityTrendChart();
        this.createMarginAnalysisChart();
    }

    createProfitabilityRankingChart() {
        const ctx = document.getElementById('profitabilityRankingChart');
        if (!ctx) return;

        this.destroyChart('profitabilityRanking');

        const profitabilityData = this.currentData.profitability || [];
        const topPartners = profitabilityData.slice(0, 12);
        
        const labels = topPartners.map(item => this.truncateText(item.toko?.nama_toko || 'Unknown', 12));
        const roiData = topPartners.map(item => parseFloat(item.roi || 0));
        const colors = roiData.map(roi => this.getROIColor(roi));
        
        this.charts.profitabilityRanking = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'ROI (%)',
                    data: roiData,
                    backgroundColor: colors,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        callbacks: {
                            title: (context) => {
                                return topPartners[context[0].dataIndex].toko?.nama_toko || 'Unknown';
                            },
                            label: (context) => {
                                const partner = topPartners[context.dataIndex];
                                return [
                                    `ROI: ${context.parsed.x}%`,
                                    `Net Profit: ${this.formatCurrency(partner.net_profit)}`,
                                    `Revenue: ${this.formatCurrency(partner.revenue)}`,
                                    `Profit Margin: ${partner.profit_margin}%`,
                                    `Units Sold: ${partner.units_sold || 0}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'ROI (%)',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y: {
                        ticks: {
                            font: { size: 10 }
                        }
                    }
                },
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const partner = topPartners[index];
                        this.showPartnerProfitabilityDetail(partner);
                    }
                }
            }
        });
    }

    createCostBreakdownChart() {
        const ctx = document.getElementById('costBreakdownChart');
        if (!ctx) return;

        this.destroyChart('costBreakdown');

        const costData = this.currentData.cost_breakdown || {};
        const data = [
            costData.total_cogs || 0,
            costData.total_logistics || 0,
            costData.total_opportunity || 0,
            costData.total_time_value || 0,
            costData.total_operational || 0
        ];

        const labels = ['COGS', 'Logistics', 'Opportunity Cost', 'Time Value Cost', 'Operational'];
        const colors = [
            this.costColors.cogs,
            this.costColors.logistics,
            this.costColors.opportunity,
            this.costColors.time_value,
            this.costColors.operational
        ];

        this.charts.costBreakdown = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 4,
                    borderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, index) => {
                                    const value = data.datasets[0].data[index];
                                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return {
                                        text: `${label}: ${percentage}%`,
                                        fillStyle: data.datasets[0].backgroundColor[index],
                                        strokeStyle: data.datasets[0].backgroundColor[index],
                                        pointStyle: 'circle'
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                return `${context.label}: ${this.formatCurrency(context.parsed)} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '50%'
            }
        });
    }

    createROIDistributionChart() {
        const ctx = document.getElementById('roiDistributionChart');
        if (!ctx) return;

        this.destroyChart('roiDistribution');

        // Calculate ROI distribution from profitability data
        const profitabilityData = this.currentData.profitability || [];
        const distribution = {
            excellent: profitabilityData.filter(p => p.roi >= 30).length,
            good: profitabilityData.filter(p => p.roi >= 20 && p.roi < 30).length,
            average: profitabilityData.filter(p => p.roi >= 10 && p.roi < 20).length,
            poor: profitabilityData.filter(p => p.roi >= 0 && p.roi < 10).length,
            loss: profitabilityData.filter(p => p.roi < 0).length
        };

        const labels = ['Excellent (30%+)', 'Good (20-29%)', 'Average (10-19%)', 'Poor (0-9%)', 'Loss Making (<0%)'];
        const data = [
            distribution.excellent,
            distribution.good,
            distribution.average,
            distribution.poor,
            distribution.loss
        ];
        const colors = [
            this.colors.excellent,
            this.colors.good,
            this.colors.average,
            this.colors.poor,
            this.colors.loss
        ];

        this.charts.roiDistribution = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 4,
                    borderColor: '#ffffff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                return `${context.label}: ${context.parsed} partners (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    createProfitabilityTrendChart() {
        const ctx = document.getElementById('profitabilityTrendChart');
        if (!ctx) return;

        this.destroyChart('profitabilityTrend');

        const trendsData = this.currentData.monthly_trends || [];
        const labels = trendsData.map(item => item.month);
        const roiData = trendsData.map(item => parseFloat(item.avg_roi || 0));
        const revenueData = trendsData.map(item => parseFloat(item.total_revenue || 0) / 1000000); // Convert to millions
        const profitablePartnersData = trendsData.map(item => parseInt(item.profitable_partners || 0));

        this.charts.profitabilityTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Average ROI (%)',
                        data: roiData,
                        borderColor: this.colors.good,
                        backgroundColor: this.colors.good + '20',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue (M)',
                        data: revenueData,
                        borderColor: this.colors.excellent,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Profitable Partners',
                        data: profitablePartnersData,
                        borderColor: this.colors.average,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y2'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'ROI (%)',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Revenue (M)',
                            font: { weight: 'bold' }
                        },
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value + 'M';
                            }
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: false,
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    createMarginAnalysisChart() {
        const ctx = document.getElementById('marginAnalysisChart');
        if (!ctx) return;

        this.destroyChart('marginAnalysis');

        const profitabilityData = this.currentData.profitability || [];
        const topPartners = profitabilityData.slice(0, 10);
        
        const labels = topPartners.map(item => this.truncateText(item.toko?.nama_toko || 'Unknown', 10));
        const grossMarginData = topPartners.map(item => parseFloat(item.gross_margin || 0));
        const profitMarginData = topPartners.map(item => parseFloat(item.profit_margin || 0));

        this.charts.marginAnalysis = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Gross Margin (%)',
                        data: grossMarginData,
                        backgroundColor: this.colors.good + '80',
                        borderColor: this.colors.good,
                        borderWidth: 1
                    },
                    {
                        label: 'Profit Margin (%)',
                        data: profitMarginData,
                        backgroundColor: this.colors.excellent + '80',
                        borderColor: this.colors.excellent,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        callbacks: {
                            title: (context) => {
                                return topPartners[context[0].dataIndex].toko?.nama_toko || 'Unknown';
                            },
                            label: (context) => {
                                return `${context.dataset.label}: ${context.parsed.y}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Margin (%)',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    }

    updateStatistics() {
        const profitabilityData = this.currentData.profitability || [];
        const lossMakers = this.currentData.loss_makers?.loss_makers || [];
        
        // Update overview statistics
        this.updateStatCard('totalPartners', profitabilityData.length);
        this.updateStatCard('profitablePartners', profitabilityData.filter(p => p.roi > 0).length);
        this.updateStatCard('lossMakingPartners', profitabilityData.filter(p => p.roi < 0).length);
        
        // Calculate averages
        const avgROI = profitabilityData.length > 0 ? 
            profitabilityData.reduce((sum, p) => sum + (p.roi || 0), 0) / profitabilityData.length : 0;
        this.updateStatCard('averageROI', Math.round(avgROI * 10) / 10 + '%');
        
        const totalRevenue = profitabilityData.reduce((sum, p) => sum + (p.revenue || 0), 0);
        this.updateStatCard('totalRevenue', this.formatCurrencyShort(totalRevenue));
        
        const totalProfit = profitabilityData.reduce((sum, p) => sum + (p.net_profit || 0), 0);
        this.updateStatCard('totalProfit', this.formatCurrencyShort(totalProfit));
        
        // Update loss makers summary
        if (lossMakers.length > 0) {
            this.updateLossMakersSummary(lossMakers);
        }
    }

    updateLossMakersSummary(lossMakers) {
        const criticalCases = lossMakers.filter(lm => lm.severity === 'Critical').length;
        const highRiskCases = lossMakers.filter(lm => lm.severity === 'High').length;
        
        this.updateStatCard('criticalCases', criticalCases);
        this.updateStatCard('highRiskCases', highRiskCases);
        
        const totalLosses = lossMakers
            .filter(lm => lm.net_profit < 0)
            .reduce((sum, lm) => sum + Math.abs(lm.net_profit), 0);
        this.updateStatCard('totalLosses', this.formatCurrencyShort(totalLosses));
    }

    updateStatCard(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value;
        }
    }

    setupEventHandlers() {
        // Export button
        $(document).on('click', '[data-export="profitability"]', () => {
            this.exportData();
        });

        // Identify loss makers button
        $(document).on('click', '[data-action="identify-loss-makers"]', () => {
            this.identifyLossMakers();
        });

        // Flag partner buttons
        $(document).on('click', '[data-flag-partner]', (e) => {
            const partnerId = $(e.target).data('flag-partner');
            this.flagPartner(partnerId);
        });

        // Optimize partner buttons
        $(document).on('click', '[data-optimize-partner]', (e) => {
            const partnerId = $(e.target).data('optimize-partner');
            this.optimizePartner(partnerId);
        });

        // Refresh button
        $(document).on('click', '[data-refresh="profitability"]', () => {
            this.refreshData();
        });

        // ROI filter buttons
        $(document).on('click', '[data-filter-roi]', (e) => {
            const roiRange = $(e.target).data('filter-roi');
            this.filterByROI(roiRange);
        });

        // Partner detail buttons
        $(document).on('click', '[data-partner-profitability]', (e) => {
            const partnerId = $(e.target).data('partner-profitability');
            this.showPartnerProfitabilityDetail(partnerId);
        });

        // Cost optimization buttons
        $(document).on('click', '[data-action="cost-optimization"]', () => {
            this.showCostOptimizationModal();
        });
    }

    async identifyLossMakers() {
        try {
            this.showNotification('Identifying loss-making partners...', 'info');
            
            const response = await fetch('/analytics/profitability-analysis/identify-loss-makers');
            const result = await response.json();
            
            if (result.success) {
                this.showLossMakersModal(result.data);
                this.showNotification(`Found ${result.data.summary.total_loss_makers} loss-making partners`, 'warning');
            } else {
                throw new Error(result.message || 'Failed to identify loss makers');
            }
        } catch (error) {
            console.error('Error identifying loss makers:', error);
            this.showNotification('Failed to identify loss makers', 'error');
        }
    }

    async flagPartner(partnerId) {
        try {
            const reason = await this.promptForReason('Why are you flagging this partner?');
            if (!reason) return;
            
            const response = await fetch(`/analytics/profitability-analysis/flag-partner/${partnerId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    reason: reason,
                    priority: 'high'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(`Partner flagged successfully: ${result.flag_details.partner}`, 'success');
                this.showFlaggedPartnerModal(result.flag_details);
            } else {
                throw new Error(result.message || 'Failed to flag partner');
            }
        } catch (error) {
            console.error('Error flagging partner:', error);
            this.showNotification('Failed to flag partner', 'error');
        }
    }

    async optimizePartner(partnerId) {
        try {
            this.showNotification('Generating optimization recommendations...', 'info');
            
            const response = await fetch(`/analytics/profitability-analysis/optimize-partner/${partnerId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showOptimizationModal(result);
                this.showNotification('Optimization recommendations generated', 'success');
            } else {
                throw new Error(result.message || 'Failed to generate optimization');
            }
        } catch (error) {
            console.error('Error optimizing partner:', error);
            this.showNotification('Failed to generate optimization', 'error');
        }
    }

    showLossMakersModal(data) {
        const modal = $('#lossMakersModal');
        if (modal.length) {
            const modalBody = modal.find('.modal-body');
            modalBody.html(this.generateLossMakersContent(data));
            modal.modal('show');
        } else {
            this.createLossMakersModal(data);
        }
    }

    generateLossMakersContent(data) {
        const lossMakers = data.loss_makers || [];
        const summary = data.summary || {};
        
        let content = `
            <div class="loss-makers-summary mb-4">
                <h6 class="text-danger">⚠️ Loss Makers Summary</h6>
                <div class="row text-center">
                    <div class="col-3">
                        <strong class="text-danger">${summary.critical_cases || 0}</strong>
                        <div class="small text-muted">Critical</div>
                    </div>
                    <div class="col-3">
                        <strong class="text-warning">${summary.high_risk_cases || 0}</strong>
                        <div class="small text-muted">High Risk</div>
                    </div>
                    <div class="col-3">
                        <strong class="text-info">${summary.medium_risk_cases || 0}</strong>
                        <div class="small text-muted">Medium Risk</div>
                    </div>
                    <div class="col-3">
                        <strong class="text-success">${this.formatCurrencyShort(Math.abs(summary.total_losses || 0))}</strong>
                        <div class="small text-muted">Total Losses</div>
                    </div>
                </div>
            </div>
        `;

        if (lossMakers.length > 0) {
            content += `
                <div class="loss-makers-list">
                    <h6 class="text-primary">📋 Detailed Loss Makers Analysis</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Partner</th>
                                    <th>ROI</th>
                                    <th>Net Profit</th>
                                    <th>Severity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            lossMakers.slice(0, 10).forEach(partner => {
                const severityClass = partner.severity === 'Critical' ? 'danger' : 
                                   partner.severity === 'High' ? 'warning' : 'info';
                content += `
                    <tr>
                        <td><strong>${partner.partner_name}</strong></td>
                        <td><span class="text-${severityClass}">${partner.roi}%</span></td>
                        <td>${this.formatCurrency(partner.net_profit)}</td>
                        <td><span class="badge badge-${severityClass}">${partner.severity}</span></td>
                        <td><small class="text-muted">${partner.recommended_action}</small></td>
                    </tr>
                `;
            });
            
            content += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        return content;
    }

    showOptimizationModal(data) {
        console.log('Partner optimization:', data);
        // Implementation for optimization modal
    }

    showPartnerProfitabilityDetail(partner) {
        console.log('Partner profitability detail:', partner);
        // Implementation for partner detail modal
    }

    exportData() {
        window.location.href = '/analytics/profitability-analysis/export';
        this.showNotification('Export started...', 'success');
    }

    async refreshData() {
        try {
            this.showNotification('Refreshing profitability data...', 'info');
            await this.loadData();
            this.createCharts();
            this.updateStatistics();
            this.showNotification('Data refreshed successfully', 'success');
        } catch (error) {
            console.error('Error refreshing data:', error);
            this.showNotification('Failed to refresh data', 'error');
        }
    }

    filterByROI(roiRange) {
        // Implementation for ROI filtering
        console.log('Filtering by ROI range:', roiRange);
    }

    showCostOptimizationModal() {
        // Implementation for cost optimization modal
        console.log('Cost optimization modal');
    }

    // Utility methods
    getROIColor(roi) {
        if (roi >= 30) return this.colors.excellent;
        if (roi >= 20) return this.colors.good;
        if (roi >= 10) return this.colors.average;
        if (roi >= 0) return this.colors.poor;
        return this.colors.loss;
    }

    destroyChart(chartKey) {
        if (this.charts[chartKey] && typeof this.charts[chartKey].destroy === 'function') {
            this.charts[chartKey].destroy();
            delete this.charts[chartKey];
        }
    }

    truncateText(text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    formatCurrency(amount) {
        if (!amount || isNaN(amount)) return 'Rp 0';
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
    }

    formatCurrencyShort(amount) {
        if (!amount || isNaN(amount)) return 'Rp 0';
        
        if (amount >= 1000000000) {
            return 'Rp ' + (amount / 1000000000).toFixed(1) + 'B';
        } else if (amount >= 1000000) {
            return 'Rp ' + (amount / 1000000).toFixed(1) + 'M';
        } else if (amount >= 1000) {
            return 'Rp ' + (amount / 1000).toFixed(1) + 'K';
        }
        
        return this.formatCurrency(amount);
    }

    showNotification(message, type = 'info') {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }

    async promptForReason(message) {
        return prompt(message);
    }

    startPeriodicUpdates() {
        // Refresh data every 15 minutes
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                this.refreshData();
            }
        }, 900000);
    }

    // Resize charts on window resize
    onResize() {
        Object.keys(this.charts).forEach(chartKey => {
            if (this.charts[chartKey] && typeof this.charts[chartKey].resize === 'function') {
                try {
                    this.charts[chartKey].resize();
                } catch (error) {
                    console.warn('Error resizing chart:', chartKey, error);
                }
            }
        });
    }
}

// Initialize when DOM is ready
$(document).ready(function() {
    // Only initialize if we're on the profitability analysis page
    if (window.location.pathname.includes('profitability-analysis')) {
        window.profitabilityAnalytics = new ProfitabilityAnalytics();
        
        // Handle window resize
        $(window).on('resize', () => {
            if (window.profitabilityAnalytics) {
                window.profitabilityAnalytics.onResize();
            }
        });
    }
});

// Export for global access
window.ProfitabilityAnalytics = ProfitabilityAnalytics;