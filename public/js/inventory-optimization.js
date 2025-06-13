/**
 * INVENTORY OPTIMIZATION ANALYTICS - ZAFA POTATO
 * JavaScript Module untuk Inventory Optimization Analytics - FIXED VERSION
 */

class InventoryOptimizationAnalytics {
    constructor() {
        this.charts = {};
        this.currentData = {};
        this.colors = {
            primary: '#007bff',
            success: '#28a745',
            warning: '#ffc107',
            danger: '#dc3545',
            info: '#17a2b8',
            secondary: '#6c757d'
        };
        
        this.confidenceColors = {
            'High': '#28a745',
            'Medium': '#ffc107',
            'Low': '#fd7e14',
            'Very Low': '#dc3545'
        };
        
        this.init();
    }

    async init() {
        try {
            await this.loadData();
            this.createCharts();
            this.setupEventHandlers();
            this.updateStatistics();
            this.populateRecommendationsTable();
            this.startPeriodicUpdates();
        } catch (error) {
            console.error('Failed to initialize Inventory Optimization Analytics:', error);
            this.showNotification('Failed to load inventory optimization data', 'error');
        }
    }

    async loadData() {
        try {
            const response = await fetch('/analytics/inventory-optimization/api/data');
            const result = await response.json();
            
            if (result.success) {
                this.currentData = result.data;
                return result.data;
            } else {
                throw new Error('Failed to load data');
            }
        } catch (error) {
            console.error('Error loading inventory optimization data:', error);
            throw error;
        }
    }

    createCharts() {
        this.createInventoryTurnoverChart();
        this.createOptimalVsActualChart();
        this.createConfidenceDistributionChart();
        this.createSeasonalAdjustmentChart();
        this.createCostSavingsChart();
    }

    createInventoryTurnoverChart() {
        const ctx = document.getElementById('inventoryTurnoverChart');
        if (!ctx) return;

        this.destroyChart('inventoryTurnover');

        const turnoverData = this.currentData.turnover_data || this.getDefaultTurnoverData();
        const labels = turnoverData.map(item => item.month);
        const actualData = turnoverData.map(item => parseFloat(item.actual_turnover || 0));
        const targetData = turnoverData.map(item => parseFloat(item.target_turnover || 4.0));

        this.charts.inventoryTurnover = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Actual Turnover Rate',
                        data: actualData,
                        borderColor: this.colors.primary,
                        backgroundColor: this.createGradient(ctx, this.colors.primary, 0.1),
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: this.colors.primary,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Target Turnover Rate',
                        data: targetData,
                        borderColor: this.colors.success,
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: this.colors.success
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        callbacks: {
                            label: (context) => {
                                return `${context.dataset.label}: ${context.parsed.y.toFixed(2)}x`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Turnover Rate (times/month)',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            callback: function(value) {
                                return value + 'x';
                            }
                        }
                    },
                    x: {
                        title: { 
                            display: true, 
                            text: 'Month',
                            font: { weight: 'bold' }
                        }
                    }
                }
            }
        });
    }

    createOptimalVsActualChart() {
        const ctx = document.getElementById('optimalVsActualChart');
        if (!ctx) return;

        this.destroyChart('optimalVsActual');

        const recommendations = this.currentData.recommendations || [];
        const topRecommendations = recommendations.slice(0, 8);
        
        const labels = topRecommendations.map(item => this.truncateText(this.safeGetProperty(item, 'barang_nama') || 'Unknown', 12));
        const actualData = topRecommendations.map(item => parseFloat(this.safeGetProperty(item, 'historical_avg_shipped') || 0));
        const optimalData = topRecommendations.map(item => parseFloat(this.safeGetProperty(item, 'recommended_quantity') || 0));

        this.charts.optimalVsActual = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Current Average',
                        data: actualData,
                        backgroundColor: this.colors.warning + '80',
                        borderColor: this.colors.warning,
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
                    },
                    {
                        label: 'Recommended Optimal',
                        data: optimalData,
                        backgroundColor: this.colors.success + '80',
                        borderColor: this.colors.success,
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
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
                                return this.safeGetProperty(topRecommendations[context[0].dataIndex], 'barang_nama') || 'Unknown';
                            },
                            label: (context) => {
                                const item = topRecommendations[context.dataIndex];
                                if (context.datasetIndex === 0) {
                                    return `Current: ${context.parsed.y} units`;
                                } else {
                                    return [
                                        `Optimal: ${context.parsed.y} units`,
                                        `Potential Savings: ${this.formatCurrency(this.safeGetProperty(item, 'potential_savings') || 0)}`,
                                        `Confidence: ${this.safeGetProperty(item, 'confidence_level') || 'Unknown'}`
                                    ];
                                }
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Quantity (units)',
                            font: { weight: 'bold' }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const recommendation = topRecommendations[index];
                        this.showRecommendationDetail(recommendation);
                    }
                }
            }
        });
    }

    createConfidenceDistributionChart() {
        const ctx = document.getElementById('confidenceDistributionChart');
        if (!ctx) return;

        this.destroyChart('confidenceDistribution');

        const recommendations = this.currentData.recommendations || [];
        const confidenceData = {
            'High': recommendations.filter(r => this.safeGetProperty(r, 'confidence_level') === 'High').length,
            'Medium': recommendations.filter(r => this.safeGetProperty(r, 'confidence_level') === 'Medium').length,
            'Low': recommendations.filter(r => this.safeGetProperty(r, 'confidence_level') === 'Low').length,
            'Very Low': recommendations.filter(r => this.safeGetProperty(r, 'confidence_level') === 'Very Low').length
        };

        const labels = Object.keys(confidenceData);
        const data = Object.values(confidenceData);
        const colors = labels.map(level => this.confidenceColors[level]);

        this.charts.confidenceDistribution = new Chart(ctx, {
            type: 'doughnut',
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
                            usePointStyle: true,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, index) => ({
                                    text: `${label}: ${data.datasets[0].data[index]} recommendations`,
                                    fillStyle: data.datasets[0].backgroundColor[index],
                                    strokeStyle: data.datasets[0].backgroundColor[index],
                                    pointStyle: 'circle'
                                }));
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                return `${context.label}: ${context.parsed} recommendations (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '50%'
            }
        });
    }

    createSeasonalAdjustmentChart() {
        const ctx = document.getElementById('seasonalAdjustmentChart');
        if (!ctx) return;

        this.destroyChart('seasonalAdjustment');

        // Create seasonal multiplier data for 12 months
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const seasonalMultipliers = [1.15, 0.95, 1.25, 1.35, 1.0, 1.1, 1.1, 1.05, 1.0, 0.9, 0.9, 1.4]; // Sample data

        this.charts.seasonalAdjustment = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Seasonal Multiplier',
                    data: seasonalMultipliers,
                    borderColor: this.colors.info,
                    backgroundColor: this.createGradient(ctx, this.colors.info, 0.1),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: this.colors.info,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
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
                            label: (context) => {
                                const multiplier = context.parsed.y;
                                const impact = multiplier > 1 ? 'Increase' : 'Decrease';
                                const percentage = Math.abs((multiplier - 1) * 100).toFixed(1);
                                return [
                                    `Multiplier: ${multiplier.toFixed(2)}x`,
                                    `${impact} by ${percentage}%`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0.8,
                        max: 1.5,
                        title: { 
                            display: true, 
                            text: 'Seasonal Multiplier',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + 'x';
                            }
                        }
                    },
                    x: {
                        title: { 
                            display: true, 
                            text: 'Month',
                            font: { weight: 'bold' }
                        }
                    }
                }
            }
        });
    }

    createCostSavingsChart() {
        const ctx = document.getElementById('costSavingsChart');
        if (!ctx) return;

        this.destroyChart('costSavings');

        const recommendations = this.currentData.recommendations || [];
        const topSavings = recommendations
            .sort((a, b) => (this.safeGetProperty(b, 'potential_savings') || 0) - (this.safeGetProperty(a, 'potential_savings') || 0))
            .slice(0, 6);

        const labels = topSavings.map(item => this.truncateText(this.safeGetProperty(item, 'toko_nama') || 'Unknown', 10));
        const savingsData = topSavings.map(item => parseFloat(this.safeGetProperty(item, 'potential_savings') || 0));
        const colors = topSavings.map((_, index) => {
            const hue = (index * 60) % 360;
            return `hsl(${hue}, 70%, 60%)`;
        });

        this.charts.costSavings = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Potential Savings',
                    data: savingsData,
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
                                return this.safeGetProperty(topSavings[context[0].dataIndex], 'toko_nama') || 'Unknown';
                            },
                            label: (context) => {
                                const item = topSavings[context.dataIndex];
                                return [
                                    `Potential Savings: ${this.formatCurrency(context.parsed.x)}`,
                                    `Product: ${this.safeGetProperty(item, 'barang_nama') || 'Unknown'}`,
                                    `Confidence: ${this.safeGetProperty(item, 'confidence_level') || 'Unknown'}`
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
                            text: 'Potential Savings (Rp)',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            callback: (value) => {
                                return this.formatCurrencyShort(value);
                            }
                        }
                    }
                }
            }
        });
    }

    populateRecommendationsTable() {
        const tableBody = document.getElementById('recommendationsTableBody');
        if (!tableBody) return;

        const recommendations = this.currentData.recommendations || [];
        
        if (recommendations.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No recommendations available</td></tr>';
            return;
        }

        let tableHtml = '';
        recommendations.slice(0, 15).forEach((rec, index) => {
            const confidenceLevel = this.safeGetProperty(rec, 'confidence_level') || 'Medium';
            const confidenceClass = this.getConfidenceClass(confidenceLevel);
            const savingsFormatted = this.formatCurrency(this.safeGetProperty(rec, 'potential_savings') || 0);
            const recId = this.safeGetProperty(rec, 'id') || `rec_${index}`;
            
            tableHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${this.truncateText(this.safeGetProperty(rec, 'toko_nama') || 'Unknown', 20)}</td>
                    <td>${this.truncateText(this.safeGetProperty(rec, 'barang_nama') || 'Unknown', 25)}</td>
                    <td class="text-center">${this.safeGetProperty(rec, 'historical_avg_shipped') || 0}</td>
                    <td class="text-center"><strong>${this.safeGetProperty(rec, 'recommended_quantity') || 0}</strong></td>
                    <td class="text-center">
                        <span class="badge ${confidenceClass}">${confidenceLevel}</span>
                    </td>
                    <td class="text-right">${savingsFormatted}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" 
                                    data-action="view-detail" 
                                    data-rec-id="${recId}"
                                    data-rec-data='${JSON.stringify(rec).replace(/'/g, "&apos;")}'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-success" 
                                    data-action="apply-recommendation"
                                    data-rec-id="${recId}"
                                    data-toko-id="${this.safeGetProperty(rec, 'toko_id')}"
                                    data-barang-id="${this.safeGetProperty(rec, 'barang_id')}"
                                    data-quantity="${this.safeGetProperty(rec, 'recommended_quantity')}">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = tableHtml;
    }

    updateStatistics() {
        const recommendations = this.currentData.recommendations || [];
        const turnoverStats = this.currentData.turnover_stats || {};
        
        // Update summary statistics
        this.updateStatCard('totalRecommendations', recommendations.length);
        this.updateStatCard('highConfidenceCount', recommendations.filter(r => this.safeGetProperty(r, 'confidence_level') === 'High').length);
        this.updateStatCard('totalPotentialSavings', this.formatCurrencyShort(recommendations.reduce((sum, r) => sum + (this.safeGetProperty(r, 'potential_savings') || 0), 0)));
        this.updateStatCard('avgTurnoverRate', (turnoverStats.current_turnover_rate || 0).toFixed(2) + 'x');
        this.updateStatCard('targetTurnoverRate', (turnoverStats.target_turnover_rate || 4.0).toFixed(1) + 'x');
        this.updateStatCard('inventoryEfficiency', Math.round(turnoverStats.inventory_efficiency || 0) + '%');
        
        // Update improvement metrics
        const improvementNeeded = turnoverStats.improvement_needed || 0;
        this.updateStatCard('improvementNeeded', improvementNeeded.toFixed(2) + 'x');
        
        // Update cash cycle
        this.updateStatCard('cashCycleDays', Math.round(turnoverStats.cash_cycle_days || 21) + ' days');
    }

    updateStatCard(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value;
        }
    }

    setupEventHandlers() {
        // Apply single recommendation
        $(document).on('click', '[data-action="apply-recommendation"]', (e) => {
            const recId = $(e.target).closest('button').data('rec-id');
            const tokoId = $(e.target).closest('button').data('toko-id');
            const barangId = $(e.target).closest('button').data('barang-id');
            const quantity = $(e.target).closest('button').data('quantity');
            this.applyRecommendation(recId, tokoId, barangId, quantity);
        });

        // Apply all high-confidence recommendations
        $(document).on('click', '[data-action="apply-all-recommendations"]', () => {
            this.applyAllRecommendations();
        });

        // View recommendation detail
        $(document).on('click', '[data-action="view-detail"]', (e) => {
            const recData = $(e.target).closest('button').data('rec-data');
            this.showRecommendationDetail(recData);
        });

        // Export recommendations
        $(document).on('click', '[data-export="inventory-optimization"]', () => {
            this.exportData();
        });

        // Refresh data
        $(document).on('click', '[data-refresh="inventory-optimization"]', () => {
            this.refreshData();
        });

        // Generate new recommendations
        $(document).on('click', '[data-action="generate-recommendations"]', () => {
            this.generateRecommendations();
        });

        // Seasonal configuration
        $(document).on('click', '[data-action="seasonal-config"]', () => {
            this.showSeasonalConfigModal();
        });

        // Filter recommendations by confidence
        $(document).on('change', '#confidenceFilter', (e) => {
            this.filterRecommendationsByConfidence($(e.target).val());
        });

        // Search recommendations
        $(document).on('input', '#recommendationSearch', (e) => {
            this.searchRecommendations($(e.target).val());
        });
    }

    async applyRecommendation(recId, tokoId, barangId, quantity) {
        try {
            if (!confirm(`Apply recommendation: ${quantity} units for this product?`)) {
                return;
            }

            this.showNotification('Applying recommendation...', 'info');
            
            const response = await fetch('/analytics/inventory-optimization/apply', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    recommendation_id: recId,
                    toko_id: tokoId,
                    barang_id: barangId,
                    recommended_quantity: quantity
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Recommendation applied successfully', 'success');
                this.refreshData(); // Refresh to update the table
            } else {
                throw new Error(result.message || 'Failed to apply recommendation');
            }
        } catch (error) {
            console.error('Error applying recommendation:', error);
            this.showNotification('Failed to apply recommendation', 'error');
        }
    }

    async applyAllRecommendations() {
        try {
            const recommendations = this.currentData.recommendations || [];
            const highConfidenceCount = recommendations.filter(r => this.safeGetProperty(r, 'confidence_level') === 'High').length;
            
            if (!confirm(`Apply all ${highConfidenceCount} high-confidence recommendations?`)) {
                return;
            }

            this.showNotification('Applying all high-confidence recommendations...', 'info');
            
            const response = await fetch('/analytics/inventory-optimization/apply-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(`Applied ${result.details.applied_count} recommendations successfully`, 'success');
                this.refreshData();
            } else {
                throw new Error(result.message || 'Failed to apply recommendations');
            }
        } catch (error) {
            console.error('Error applying all recommendations:', error);
            this.showNotification('Failed to apply recommendations', 'error');
        }
    }

    showRecommendationDetail(recommendation) {
        // Handle both string (from data attribute) and object
        if (typeof recommendation === 'string') {
            try {
                recommendation = JSON.parse(recommendation);
            } catch (e) {
                console.error('Error parsing recommendation data:', e);
                this.showNotification('Error loading recommendation details', 'error');
                return;
            }
        }
        
        const modal = $('#recommendationDetailModal');
        if (modal.length) {
            // Populate modal with recommendation details
            modal.find('.modal-title').text(`Recommendation Detail - ${this.safeGetProperty(recommendation, 'toko_nama') || 'Unknown Store'}`);
            modal.find('.modal-body').html(this.generateRecommendationDetailContent(recommendation));
            modal.modal('show');
        } else {
            // Create modal if it doesn't exist
            this.createRecommendationDetailModal(recommendation);
        }
    }

    generateRecommendationDetailContent(rec) {
        // Ensure rec is an object, not a string
        if (typeof rec === 'string') {
            try {
                rec = JSON.parse(rec);
            } catch (e) {
                console.error('Error parsing recommendation:', e);
                return '<div class="alert alert-danger">Error loading recommendation data</div>';
            }
        }

        return `
            <div class="recommendation-detail">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Store Information</h6>
                        <p><strong>Store:</strong> ${this.safeGetProperty(rec, 'toko_nama') || 'Unknown'}</p>
                        <p><strong>Product:</strong> ${this.safeGetProperty(rec, 'barang_nama') || 'Unknown'}</p>
                        <p><strong>Current Average:</strong> ${this.safeGetProperty(rec, 'historical_avg_shipped') || 0} units</p>
                        <p><strong>Recommended:</strong> <span class="text-success">${this.safeGetProperty(rec, 'recommended_quantity') || 0} units</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Analysis</h6>
                        <p><strong>Confidence Level:</strong> 
                            <span class="badge ${this.getConfidenceClass(this.safeGetProperty(rec, 'confidence_level') || 'Medium')}">${this.safeGetProperty(rec, 'confidence_level') || 'Medium'}</span>
                        </p>
                        <p><strong>Seasonal Factor:</strong> ${this.safeGetProperty(rec, 'seasonal_multiplier') || 1.0}x</p>
                        <p><strong>Trend Factor:</strong> ${this.safeGetProperty(rec, 'trend_multiplier') || 1.0}x</p>
                        <p><strong>Potential Savings:</strong> 
                            <span class="text-success">${this.formatCurrency(this.safeGetProperty(rec, 'potential_savings') || 0)}</span>
                        </p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary">Expected Impact</h6>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-success" 
                                 style="width: ${this.safeGetProperty(rec, 'improvement_percentage') || 0}%">
                                ${this.safeGetProperty(rec, 'improvement_percentage') || 0}% improvement
                            </div>
                        </div>
                        <small class="text-muted">
                            This recommendation is expected to improve inventory efficiency by ${this.safeGetProperty(rec, 'improvement_percentage') || 0}%
                        </small>
                    </div>
                </div>
            </div>
        `;
    }

    createRecommendationDetailModal(recommendation) {
        const modalHtml = `
            <div class="modal fade" id="recommendationDetailModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Recommendation Detail - ${this.safeGetProperty(recommendation, 'toko_nama') || 'Unknown Store'}</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            ${this.generateRecommendationDetailContent(recommendation)}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#recommendationDetailModal').modal('show');
    }

    async generateRecommendations() {
        try {
            this.showNotification('Generating new recommendations...', 'info');
            
            const response = await fetch('/analytics/inventory-optimization/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('New recommendations generated successfully', 'success');
                this.refreshData();
            } else {
                throw new Error(result.message || 'Failed to generate recommendations');
            }
        } catch (error) {
            console.error('Error generating recommendations:', error);
            this.showNotification('Failed to generate recommendations', 'error');
        }
    }

    exportData() {
        window.location.href = '/analytics/inventory-optimization/export';
        this.showNotification('Export started...', 'success');
    }

    async refreshData() {
        try {
            this.showNotification('Refreshing inventory optimization data...', 'info');
            await this.loadData();
            this.createCharts();
            this.updateStatistics();
            this.populateRecommendationsTable();
            this.showNotification('Data refreshed successfully', 'success');
        } catch (error) {
            console.error('Error refreshing data:', error);
            this.showNotification('Failed to refresh data', 'error');
        }
    }

    filterRecommendationsByConfidence(confidence) {
        const recommendations = this.currentData.recommendations || [];
        let filteredRecs = recommendations;
        
        if (confidence && confidence !== 'all') {
            filteredRecs = recommendations.filter(r => this.safeGetProperty(r, 'confidence_level') === confidence);
        }
        
        // Update table with filtered data
        this.updateRecommendationsTable(filteredRecs);
    }

    searchRecommendations(query) {
        const recommendations = this.currentData.recommendations || [];
        let filteredRecs = recommendations;
        
        if (query) {
            filteredRecs = recommendations.filter(r => {
                const tokoNama = this.safeGetProperty(r, 'toko_nama') || '';
                const barangNama = this.safeGetProperty(r, 'barang_nama') || '';
                return tokoNama.toLowerCase().includes(query.toLowerCase()) ||
                       barangNama.toLowerCase().includes(query.toLowerCase());
            });
        }
        
        this.updateRecommendationsTable(filteredRecs);
    }

    updateRecommendationsTable(recommendations) {
        const tableBody = document.getElementById('recommendationsTableBody');
        if (!tableBody) return;

        if (recommendations.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No recommendations found</td></tr>';
            return;
        }

        let tableHtml = '';
        recommendations.slice(0, 15).forEach((rec, index) => {
            const confidenceLevel = this.safeGetProperty(rec, 'confidence_level') || 'Medium';
            const confidenceClass = this.getConfidenceClass(confidenceLevel);
            const savingsFormatted = this.formatCurrency(this.safeGetProperty(rec, 'potential_savings') || 0);
            const recId = this.safeGetProperty(rec, 'id') || `rec_${index}`;
            
            tableHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${this.truncateText(this.safeGetProperty(rec, 'toko_nama') || 'Unknown', 20)}</td>
                    <td>${this.truncateText(this.safeGetProperty(rec, 'barang_nama') || 'Unknown', 25)}</td>
                    <td class="text-center">${this.safeGetProperty(rec, 'historical_avg_shipped') || 0}</td>
                    <td class="text-center"><strong>${this.safeGetProperty(rec, 'recommended_quantity') || 0}</strong></td>
                    <td class="text-center">
                        <span class="badge ${confidenceClass}">${confidenceLevel}</span>
                    </td>
                    <td class="text-right">${savingsFormatted}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" 
                                    data-action="view-detail" 
                                    data-rec-id="${recId}"
                                    data-rec-data='${JSON.stringify(rec).replace(/'/g, "&apos;")}'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-success" 
                                    data-action="apply-recommendation"
                                    data-rec-id="${recId}"
                                    data-toko-id="${this.safeGetProperty(rec, 'toko_id')}"
                                    data-barang-id="${this.safeGetProperty(rec, 'barang_id')}"
                                    data-quantity="${this.safeGetProperty(rec, 'recommended_quantity')}">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = tableHtml;
    }

    // =====================================
    // UTILITY METHODS - FIXED FOR SAFE PROPERTY ACCESS
    // =====================================

    /**
     * Safely get property from object or array
     */
    safeGetProperty(obj, property, defaultValue = null) {
        if (!obj) return defaultValue;
        
        // Handle both object and array access
        if (typeof obj === 'object') {
            return obj[property] !== undefined ? obj[property] : defaultValue;
        }
        
        return defaultValue;
    }

    getConfidenceClass(confidence) {
        switch (confidence) {
            case 'High': return 'badge-success';
            case 'Medium': return 'badge-warning';
            case 'Low': return 'badge-info';
            case 'Very Low': return 'badge-danger';
            default: return 'badge-secondary';
        }
    }

    destroyChart(chartKey) {
        if (this.charts[chartKey] && typeof this.charts[chartKey].destroy === 'function') {
            this.charts[chartKey].destroy();
            delete this.charts[chartKey];
        }
    }

    createGradient(ctx, color, opacity = 0.2) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, color + Math.round(opacity * 255).toString(16).padStart(2, '0'));
        gradient.addColorStop(1, color + '00');
        return gradient;
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
            // Fallback notification
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[type] || 'alert-info';
            
            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : (type === 'warning' ? 'exclamation-triangle' : 'info-circle'))}"></i>
                    ${message}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
        }
    }

    startPeriodicUpdates() {
        // Refresh data every 10 minutes
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                this.refreshData();
            }
        }, 600000);
    }

    // Default data for fallback
    getDefaultTurnoverData() {
        return [
            { month: 'Jan 25', actual_turnover: 2.8, target_turnover: 4.0 },
            { month: 'Feb 25', actual_turnover: 3.1, target_turnover: 4.0 },
            { month: 'Mar 25', actual_turnover: 2.9, target_turnover: 4.0 },
            { month: 'Apr 25', actual_turnover: 3.4, target_turnover: 4.0 },
            { month: 'May 25', actual_turnover: 3.2, target_turnover: 4.0 },
            { month: 'Jun 25', actual_turnover: 3.6, target_turnover: 4.0 }
        ];
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
    // Only initialize if we're on the inventory optimization page
    if (window.location.pathname.includes('inventory-optimization')) {
        window.inventoryOptimizationAnalytics = new InventoryOptimizationAnalytics();
        
        // Handle window resize
        $(window).on('resize', () => {
            if (window.inventoryOptimizationAnalytics) {
                window.inventoryOptimizationAnalytics.onResize();
            }
        });
    }
});

// Export for global access
window.InventoryOptimizationAnalytics = InventoryOptimizationAnalytics;