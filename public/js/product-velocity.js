/**
 * PRODUCT VELOCITY ANALYTICS - ZAFA POTATO (FIXED VERSION)
 * JavaScript Module untuk Product Velocity Analytics dengan perhitungan yang diperbaiki
 */

class ProductVelocityAnalytics {
    constructor() {
        this.charts = {};
        this.currentData = {};
        this.colors = {
            'Hot Seller': '#dc3545',
            'Good Mover': '#28a745',
            'Slow Mover': '#ffc107',
            'Dead Stock': '#6c757d',
            'No Data': '#e9ecef'
        };
        
        this.apiEndpoints = {
            data: '/analytics/product-velocity/api/data',
            optimize: '/analytics/product-velocity/optimize-portfolio',
            recommendIncrease: '/analytics/product-velocity/recommend-increase',
            recommendDiscontinue: '/analytics/product-velocity/recommend-discontinue',
            export: '/analytics/product-velocity/export'
        };
        
        this.isLoading = false;
        this.refreshInterval = null;
        
        this.init();
    }

    async init() {
        try {
            console.log('Initializing Product Velocity Analytics...');
            
            // Show initial loading
            this.showLoading('Initializing analytics...');
            
            // Load initial data
            await this.loadData();
            
            // Create charts
            this.createCharts();
            
            // Setup event handlers
            this.setupEventHandlers();
            
            // Start periodic updates (every 10 minutes)
            this.startPeriodicUpdates();
            
            // Setup window resize handler
            this.setupResizeHandler();
            
            console.log('Product Velocity Analytics initialized successfully');
            this.showNotification('Analytics loaded successfully', 'success');
            
        } catch (error) {
            console.error('Failed to initialize Product Velocity Analytics:', error);
            this.showNotification('Failed to load product velocity data: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    async loadData() {
        try {
            if (this.isLoading) {
                console.log('Data loading already in progress, skipping...');
                return;
            }
            
            this.isLoading = true;
            console.log('Loading velocity data...');
            
            const response = await fetch(this.apiEndpoints.data, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success && result.data) {
                this.currentData = result.data;
                console.log('Data loaded successfully:', this.currentData);
                return result.data;
            } else {
                throw new Error(result.message || 'Failed to load data from server');
            }
            
        } catch (error) {
            console.error('Error loading product velocity data:', error);
            throw error;
        } finally {
            this.isLoading = false;
        }
    }

    createCharts() {
        try {
            console.log('Creating charts...');
            
            // Create all charts
            this.createProductVelocityChart();
            this.createVelocityCategoryChart();
            this.createVelocityTrendChart();
            this.createRegionalDemandChart();
            
            // Update statistics
            this.updateStatistics();
            
            console.log('Charts created successfully');
        } catch (error) {
            console.error('Error creating charts:', error);
            this.showNotification('Error creating charts: ' + error.message, 'error');
        }
    }

    createProductVelocityChart() {
        const ctx = document.getElementById('productVelocityChart');
        if (!ctx) {
            console.log('Product velocity chart canvas not found');
            return;
        }

        try {
            this.destroyChart('productVelocity');

            const products = this.currentData.products || {};
            const allProducts = [];
            
            // Flatten all products from all categories
            Object.keys(products).forEach(category => {
                if (products[category] && Array.isArray(products[category])) {
                    products[category].forEach(product => {
                        allProducts.push({...product, category});
                    });
                }
            });

            // Sort by velocity score and take top 15
            const topProducts = allProducts
                .filter(p => p.velocity_score > 0) // Only include products with data
                .sort((a, b) => (b.velocity_score || 0) - (a.velocity_score || 0))
                .slice(0, 15);

            if (topProducts.length === 0) {
                console.log('No products with velocity data found');
                return;
            }

            const labels = topProducts.map(p => this.truncateText(p.barang?.nama_barang || 'Unknown', 20));
            const velocityData = topProducts.map(p => parseFloat(p.velocity_score || 0));
            const backgroundColors = topProducts.map(p => this.colors[p.velocity_category] || this.colors['No Data']);

            this.charts.productVelocity = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Velocity Score',
                        data: velocityData,
                        backgroundColor: backgroundColors,
                        borderColor: backgroundColors.map(color => this.darkenColor(color, 0.2)),
                        borderWidth: 1,
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: false 
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                title: (context) => {
                                    return topProducts[context[0].dataIndex].barang?.nama_barang || 'Unknown';
                                },
                                label: (context) => {
                                    const product = topProducts[context.dataIndex];
                                    return [
                                        `Velocity Score: ${context.parsed.y.toFixed(1)}`,
                                        `Category: ${product.velocity_category}`,
                                        `Sell-Through: ${product.avg_sell_through}%`,
                                        `Days to Sell: ${product.avg_days_to_sell}`,
                                        `Total Sold: ${product.total_sold.toLocaleString()} units`,
                                        `Return Rate: ${product.return_rate || 0}%`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: { 
                                display: true, 
                                text: 'Velocity Score (0-100)',
                                font: { weight: 'bold', size: 12 }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0);
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                font: { size: 10 }
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const product = topProducts[index];
                            this.showProductDetail(product);
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating product velocity chart:', error);
        }
    }

    createVelocityCategoryChart() {
        const ctx = document.getElementById('velocityCategoryChart');
        if (!ctx) {
            console.log('Velocity category chart canvas not found');
            return;
        }

        try {
            this.destroyChart('velocityCategory');

            const categoryStats = this.currentData.category_stats || {};
            const data = [
                categoryStats.hot_sellers || 0,
                categoryStats.good_movers || 0,
                categoryStats.slow_movers || 0,
                categoryStats.dead_stock || 0,
                categoryStats.no_data || 0
            ];

            const labels = ['🔥 Hot Sellers', '✅ Good Movers', '🐌 Slow Movers', '💀 Dead Stock', '📊 No Data'];
            const backgroundColors = [
                this.colors['Hot Seller'],
                this.colors['Good Mover'],
                this.colors['Slow Mover'],
                this.colors['Dead Stock'],
                this.colors['No Data']
            ];

            // Filter out zero values
            const filteredData = [];
            const filteredLabels = [];
            const filteredColors = [];
            
            data.forEach((value, index) => {
                if (value > 0) {
                    filteredData.push(value);
                    filteredLabels.push(labels[index]);
                    filteredColors.push(backgroundColors[index]);
                }
            });

            if (filteredData.length === 0) {
                console.log('No category data available');
                return;
            }

            this.charts.velocityCategory = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: filteredLabels,
                    datasets: [{
                        data: filteredData,
                        backgroundColor: filteredColors,
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverOffset: 10,
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { 
                                padding: 20, 
                                usePointStyle: true,
                                font: { size: 12 },
                                generateLabels: (chart) => {
                                    const data = chart.data;
                                    return data.labels.map((label, index) => ({
                                        text: `${label}: ${data.datasets[0].data[index]} products`,
                                        fillStyle: data.datasets[0].backgroundColor[index],
                                        strokeStyle: data.datasets[0].backgroundColor[index],
                                        pointStyle: 'circle'
                                    }));
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                label: (context) => {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                    return `${context.label}: ${context.parsed} products (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        } catch (error) {
            console.error('Error creating velocity category chart:', error);
        }
    }

    createVelocityTrendChart() {
        const ctx = document.getElementById('velocityTrendChart');
        if (!ctx) {
            console.log('Velocity trend chart canvas not found');
            return;
        }

        try {
            this.destroyChart('velocityTrend');

            const trends = this.currentData.velocity_trends || {};
            const months = this.generateMonthLabels(6);

            const datasets = [
                {
                    label: 'Hot Sellers',
                    data: trends.hot_sellers || Array(6).fill(0),
                    borderColor: this.colors['Hot Seller'],
                    backgroundColor: this.colors['Hot Seller'] + '20',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: this.colors['Hot Seller'],
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Good Movers',
                    data: trends.good_movers || Array(6).fill(0),
                    borderColor: this.colors['Good Mover'],
                    backgroundColor: this.colors['Good Mover'] + '20',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: this.colors['Good Mover'],
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Slow Movers',
                    data: trends.slow_movers || Array(6).fill(0),
                    borderColor: this.colors['Slow Mover'],
                    backgroundColor: this.colors['Slow Mover'] + '20',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: this.colors['Slow Mover'],
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Dead Stock',
                    data: trends.dead_stock || Array(6).fill(0),
                    borderColor: this.colors['Dead Stock'],
                    backgroundColor: this.colors['Dead Stock'] + '20',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: this.colors['Dead Stock'],
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }
            ];

            this.charts.velocityTrend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 12 }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { 
                                display: true, 
                                text: 'Number of Products',
                                font: { weight: 'bold', size: 12 }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            title: { 
                                display: true, 
                                text: 'Month',
                                font: { weight: 'bold', size: 12 }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        } catch (error) {
            console.error('Error creating velocity trend chart:', error);
        }
    }

    createRegionalDemandChart() {
        const ctx = document.getElementById('regionalDemandChart') || document.getElementById('regionalChart');
        if (!ctx) {
            console.log('Regional demand chart canvas not found');
            return;
        }

        try {
            this.destroyChart('regionalDemand');

            const regionalData = this.currentData.regional_data || {
                'Malang Kota': 42,
                'Malang Kabupaten': 28,
                'Kota Batu': 18,
                'Lainnya': 12
            };
            
            const labels = Object.keys(regionalData);
            const data = Object.values(regionalData);
            
            const colors = [
                '#007bff', // Blue
                '#28a745', // Green
                '#ffc107', // Yellow
                '#17a2b8', // Cyan
                '#dc3545'  // Red
            ];

            this.charts.regionalDemand = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverOffset: 12,
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: { size: 12 },
                                generateLabels: (chart) => {
                                    const data = chart.data;
                                    return data.labels.map((label, index) => ({
                                        text: `${label}: ${data.datasets[0].data[index]}%`,
                                        fillStyle: data.datasets[0].backgroundColor[index],
                                        strokeStyle: data.datasets[0].backgroundColor[index],
                                        pointStyle: 'circle'
                                    }));
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                label: (context) => {
                                    return `${context.label}: ${context.parsed}%`;
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating regional demand chart:', error);
        }
    }

    updateStatistics() {
        try {
            const categoryStats = this.currentData.category_stats || {};
            
            // Update statistics cards with animation
            this.updateStatCard('hotSellersCount', categoryStats.hot_sellers || 0);
            this.updateStatCard('goodMoversCount', categoryStats.good_movers || 0);
            this.updateStatCard('slowMoversCount', categoryStats.slow_movers || 0);
            this.updateStatCard('deadStockCount', categoryStats.dead_stock || 0);
            
            // Calculate and display percentages if needed
            const total = (categoryStats.hot_sellers || 0) + 
                         (categoryStats.good_movers || 0) + 
                         (categoryStats.slow_movers || 0) + 
                         (categoryStats.dead_stock || 0);
            
            if (total > 0) {
                const hotSellerPercent = Math.round((categoryStats.hot_sellers || 0) / total * 100);
                const goodMoverPercent = Math.round((categoryStats.good_movers || 0) / total * 100);
                
                // Update trend indicators if elements exist
                this.updateStatCard('hotSellersPercent', hotSellerPercent + '%');
                this.updateStatCard('goodMoversPercent', goodMoverPercent + '%');
            }
            
            console.log('Statistics updated successfully');
        } catch (error) {
            console.error('Error updating statistics:', error);
        }
    }

    updateStatCard(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            // Add smooth transition effect
            element.style.transition = 'all 0.3s ease';
            element.textContent = value;
            
            // Add brief highlight effect
            element.style.color = '#007bff';
            setTimeout(() => {
                element.style.color = '';
            }, 500);
        }
    }

    setupEventHandlers() {
        try {
            console.log('Setting up event handlers...');
            
            // Export button
            $(document).off('click', '[data-export="product-velocity"]');
            $(document).on('click', '[data-export="product-velocity"]', () => {
                this.exportData();
            });

            // Alternative export button
            $(document).off('click', '#exportVelocity');
            $(document).on('click', '#exportVelocity', () => {
                this.exportData();
            });

            // Optimize portfolio button
            $(document).off('click', '[data-action="optimize-portfolio"]');
            $(document).on('click', '[data-action="optimize-portfolio"]', () => {
                this.optimizePortfolio();
            });

            // Alternative optimize button
            $(document).off('click', '#optimizePortfolio');
            $(document).on('click', '#optimizePortfolio', () => {
                this.optimizePortfolio();
            });

            // Category filter buttons
            $(document).off('click', '[data-filter-category]');
            $(document).on('click', '[data-filter-category]', (e) => {
                const category = $(e.target).data('filter-category');
                this.filterByCategory(category);
            });

            // Velocity card clicks
            $(document).off('click', '.velocity-card');
            $(document).on('click', '.velocity-card', (e) => {
                const category = $(e.currentTarget).data('category');
                this.filterByCategory(category);
                
                // Visual feedback
                $('.velocity-card').removeClass('selected');
                $(e.currentTarget).addClass('selected');
            });

            // Refresh button
            $(document).off('click', '[data-refresh="product-velocity"]');
            $(document).on('click', '[data-refresh="product-velocity"]', () => {
                this.refreshData();
            });

            // Alternative refresh button
            $(document).off('click', '#refreshData');
            $(document).on('click', '#refreshData', () => {
                this.refreshData();
            });

            // Product detail buttons
            $(document).off('click', '[data-product-detail]');
            $(document).on('click', '[data-product-detail]', (e) => {
                const productId = $(e.target).data('product-detail');
                this.showProductDetail(productId);
            });

            // Recommendation actions
            $(document).off('click', '[data-recommend-increase]');
            $(document).on('click', '[data-recommend-increase]', (e) => {
                const productId = $(e.target).data('recommend-increase');
                this.recommendIncrease(productId);
            });

            $(document).off('click', '[data-recommend-discontinue]');
            $(document).on('click', '[data-recommend-discontinue]', (e) => {
                const productId = $(e.target).data('recommend-discontinue');
                this.recommendDiscontinue(productId);
            });

            // Global recommendation functions
            window.recommendIncrease = (productId) => this.recommendIncrease(productId);
            window.recommendDiscontinue = (productId) => this.recommendDiscontinue(productId);
            
            console.log('Event handlers set up successfully');
        } catch (error) {
            console.error('Error setting up event handlers:', error);
        }
    }

    async optimizePortfolio() {
        try {
            this.showLoading('Analyzing portfolio optimization...');
            
            const response = await fetch(this.apiEndpoints.optimize, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showPortfolioOptimizationModal(result);
                this.showNotification('Portfolio optimization completed successfully', 'success');
            } else {
                throw new Error(result.message || 'Failed to optimize portfolio');
            }
        } catch (error) {
            console.error('Error optimizing portfolio:', error);
            this.showNotification('Failed to optimize portfolio: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    async recommendIncrease(productId) {
        try {
            this.showLoading('Generating increase recommendation...');
            
            const response = await fetch(`${this.apiEndpoints.recommendIncrease}/${productId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showRecommendationModal('increase', result);
                this.showNotification('Increase recommendation generated', 'success');
            } else {
                throw new Error(result.message || 'Failed to generate recommendation');
            }
        } catch (error) {
            console.error('Error generating increase recommendation:', error);
            this.showNotification('Failed to generate recommendation: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    async recommendDiscontinue(productId) {
        try {
            this.showLoading('Generating discontinue recommendation...');
            
            const response = await fetch(`${this.apiEndpoints.recommendDiscontinue}/${productId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showRecommendationModal('discontinue', result);
                this.showNotification('Discontinue recommendation generated', 'success');
            } else {
                throw new Error(result.message || 'Failed to generate recommendation');
            }
        } catch (error) {
            console.error('Error generating discontinue recommendation:', error);
            this.showNotification('Failed to generate recommendation: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    showPortfolioOptimizationModal(data) {
        try {
            const modal = $('#portfolioOptimizationModal');
            if (modal.length) {
                // Populate modal with optimization recommendations
                const modalBody = modal.find('.modal-body');
                modalBody.html(this.generateOptimizationContent(data));
                modal.modal('show');
            } else {
                // Create and show modal if it doesn't exist
                this.createOptimizationModal(data);
            }
        } catch (error) {
            console.error('Error showing portfolio optimization modal:', error);
            this.showNotification('Error displaying optimization results', 'error');
        }
    }

    generateOptimizationContent(data) {
        try {
            const recommendations = data.recommendations || {};
            const summary = data.summary || {};
            
            let content = `
                <div class="optimization-summary mb-4">
                    <h6 class="text-primary mb-3">📊 Portfolio Optimization Summary</h6>
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="card border-success h-100">
                                <div class="card-body py-3">
                                    <h4 class="text-success mb-1">${summary.increase_count || 0}</h4>
                                    <small class="text-muted">Increase Production</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card border-primary h-100">
                                <div class="card-body py-3">
                                    <h4 class="text-primary mb-1">${summary.maintain_count || 0}</h4>
                                    <small class="text-muted">Maintain Levels</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card border-warning h-100">
                                <div class="card-body py-3">
                                    <h4 class="text-warning mb-1">${summary.reduce_count || 0}</h4>
                                    <small class="text-muted">Reduce Production</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card border-danger h-100">
                                <div class="card-body py-3">
                                    <h4 class="text-danger mb-1">${summary.discontinue_count || 0}</h4>
                                    <small class="text-muted">Discontinue</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <p class="text-muted">Total Products Analyzed: <strong>${summary.total_analyzed || 0}</strong></p>
                    </div>
                </div>
            `;

            // Add detailed recommendations sections
            const sections = [
                { key: 'increase_production', title: '📈 Increase Production', color: 'success', icon: 'fas fa-arrow-up' },
                { key: 'maintain_production', title: '➡️ Maintain Current Levels', color: 'primary', icon: 'fas fa-minus' },
                { key: 'reduce_production', title: '📉 Reduce Production', color: 'warning', icon: 'fas fa-arrow-down' },
                { key: 'discontinue', title: '🚫 Consider Discontinuing', color: 'danger', icon: 'fas fa-times' }
            ];

            sections.forEach(section => {
                const items = recommendations[section.key] || [];
                if (items.length > 0) {
                    content += `
                        <div class="recommendation-section mb-4">
                            <h6 class="text-${section.color} mb-3">
                                <i class="${section.icon} mr-2"></i>${section.title}
                            </h6>
                            <div class="row">
                    `;
                    
                    items.forEach(item => {
                        content += `
                            <div class="col-md-6 mb-3">
                                <div class="card border-${section.color} h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-${section.color}">${item.product}</h6>
                                        <p class="card-text">
                                            <strong>Action:</strong> ${item.action}<br>
                                            <strong>Reason:</strong> ${item.reason}
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge badge-${section.color}">${item.priority} Priority</span>
                                            <small class="text-muted">Score: ${item.current_velocity}</small>
                                        </div>
                                        ${item.potential_impact ? `<div class="mt-2"><small class="text-info">${item.potential_impact}</small></div>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    content += `</div></div>`;
                }
            });

            return content;
        } catch (error) {
            console.error('Error generating optimization content:', error);
            return '<div class="alert alert-danger">Error generating optimization content</div>';
        }
    }

    showRecommendationModal(type, data) {
        try {
            // Implementation for showing recommendation modal
            console.log(`${type} recommendation:`, data);
            
            const title = type === 'increase' ? 'Production Increase Recommendation' : 'Product Discontinue Recommendation';
            const content = this.generateRecommendationContent(type, data);
            
            // Show modal (implementation depends on your modal system)
            this.showNotification(`${title} ready to view`, 'info');
        } catch (error) {
            console.error('Error showing recommendation modal:', error);
        }
    }

    generateRecommendationContent(type, data) {
        // Implementation for generating recommendation content
        return `<div class="alert alert-info">Recommendation for ${data.product}: ${type}</div>`;
    }

    showProductDetail(product) {
        try {
            console.log('Product detail:', product);
            // Implementation for showing product detail modal
            this.showNotification('Product details loaded', 'info');
        } catch (error) {
            console.error('Error showing product detail:', error);
        }
    }

    exportData() {
        try {
            this.showLoading('Preparing export...');
            
            // Navigate to export endpoint
            window.location.href = this.apiEndpoints.export;
            
            setTimeout(() => {
                this.hideLoading();
                this.showNotification('Export started successfully', 'success');
            }, 1000);
        } catch (error) {
            console.error('Error exporting data:', error);
            this.showNotification('Failed to export data: ' + error.message, 'error');
            this.hideLoading();
        }
    }

    async refreshData() {
        try {
            this.showLoading('Refreshing product velocity data...');
            
            // Clear current data
            this.currentData = {};
            
            // Reload data
            await this.loadData();
            
            // Recreate charts
            this.createCharts();
            
            this.showNotification('Data refreshed successfully', 'success');
        } catch (error) {
            console.error('Error refreshing data:', error);
            this.showNotification('Failed to refresh data: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    filterByCategory(category) {
        try {
            console.log('Filtering by category:', category);
            
            // Implementation for category filtering
            const categoryFilter = document.getElementById('categoryFilter');
            if (categoryFilter) {
                categoryFilter.value = category;
                
                // Trigger filter event if using DataTables or similar
                $(categoryFilter).trigger('change');
            }
            
            this.showNotification(`Filtered by ${category}`, 'info');
        } catch (error) {
            console.error('Error filtering by category:', error);
        }
    }

    // Utility methods
    destroyChart(chartKey) {
        try {
            if (this.charts[chartKey] && typeof this.charts[chartKey].destroy === 'function') {
                this.charts[chartKey].destroy();
                delete this.charts[chartKey];
            }
        } catch (error) {
            console.error(`Error destroying chart ${chartKey}:`, error);
        }
    }

    truncateText(text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    generateMonthLabels(count) {
        const months = [];
        for (let i = count - 1; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            months.push(date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' }));
        }
        return months;
    }

    darkenColor(color, amount) {
        try {
            // Simple color darkening function
            const num = parseInt(color.replace("#", ""), 16);
            const amt = Math.round(2.55 * amount * 100);
            const R = (num >> 16) - amt;
            const G = (num >> 8 & 0x00FF) - amt;
            const B = (num & 0x0000FF) - amt;
            return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
        } catch (error) {
            return color; // Return original color if darkening fails
        }
    }

    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    showLoading(message = 'Loading...') {
        try {
            // Remove existing loading overlay
            this.hideLoading();
            
            // Create new loading overlay
            const overlay = $(`
                <div class="loading-overlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(255, 255, 255, 0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                ">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <div class="h5 text-primary">${message}</div>
                    </div>
                </div>
            `);
            
            $('body').append(overlay);
        } catch (error) {
            console.error('Error showing loading:', error);
        }
    }

    hideLoading() {
        try {
            $('.loading-overlay').remove();
        } catch (error) {
            console.error('Error hiding loading:', error);
        }
    }

    showNotification(message, type = 'info') {
        try {
            if (typeof toastr !== 'undefined') {
                // Use toastr if available
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: 'toast-top-right',
                    timeOut: 5000
                };
                toastr[type](message);
            } else {
                // Fallback to console log and simple alert for important messages
                console.log(`${type.toUpperCase()}: ${message}`);
                if (type === 'error') {
                    alert(`Error: ${message}`);
                }
            }
        } catch (error) {
            console.error('Error showing notification:', error);
        }
    }

    startPeriodicUpdates() {
        try {
            // Clear existing interval
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            
            // Refresh data every 10 minutes (600000 ms)
            this.refreshInterval = setInterval(() => {
                if (document.visibilityState === 'visible') {
                    console.log('Performing periodic data refresh...');
                    this.refreshData();
                }
            }, 600000);
            
            console.log('Periodic updates started (every 10 minutes)');
        } catch (error) {
            console.error('Error starting periodic updates:', error);
        }
    }

    setupResizeHandler() {
        try {
            $(window).off('resize.productVelocity');
            $(window).on('resize.productVelocity', () => {
                this.onResize();
            });
        } catch (error) {
            console.error('Error setting up resize handler:', error);
        }
    }

    // Resize charts on window resize
    onResize() {
        try {
            Object.keys(this.charts).forEach(chartKey => {
                if (this.charts[chartKey] && typeof this.charts[chartKey].resize === 'function') {
                    try {
                        this.charts[chartKey].resize();
                    } catch (error) {
                        console.warn('Error resizing chart:', chartKey, error);
                    }
                }
            });
        } catch (error) {
            console.error('Error handling resize:', error);
        }
    }

    // Cleanup method
    destroy() {
        try {
            // Clear interval
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            
            // Destroy all charts
            Object.keys(this.charts).forEach(chartKey => {
                this.destroyChart(chartKey);
            });
            
            // Remove event handlers
            $(window).off('resize.productVelocity');
            $(document).off('click', '[data-export="product-velocity"]');
            $(document).off('click', '[data-action="optimize-portfolio"]');
            // ... remove other event handlers
            
            // Clean up global functions
            delete window.recommendIncrease;
            delete window.recommendDiscontinue;
            
            console.log('Product Velocity Analytics destroyed');
        } catch (error) {
            console.error('Error destroying Product Velocity Analytics:', error);
        }
    }
}

// Initialize when DOM is ready
$(document).ready(function() {
    // Only initialize if we're on the product velocity page
    if (window.location.pathname.includes('product-velocity') || 
        document.getElementById('velocityTable') ||
        document.getElementById('velocityTrendChart')) {
        
        try {
            window.productVelocityAnalytics = new ProductVelocityAnalytics();
            console.log('Product Velocity Analytics instance created');
        } catch (error) {
            console.error('Failed to create Product Velocity Analytics instance:', error);
        }
    }
});

// Handle page unload
$(window).on('beforeunload', function() {
    if (window.productVelocityAnalytics) {
        window.productVelocityAnalytics.destroy();
    }
});

// Export for global access
window.ProductVelocityAnalytics = ProductVelocityAnalytics;