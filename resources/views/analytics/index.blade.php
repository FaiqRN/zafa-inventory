@extends('layouts.template')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/analytics.css') }}">
@endpush

@section('content')
<div class="content-wrapper">
    <!-- Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-chart-line text-primary mr-2"></i>Analytics CRM Zafa Potato</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Analytics</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid analytics-container">
            
            <!-- Debug Info -->
            @if(config('app.debug') && isset($debugInfo))
            <div class="alert alert-info">
                <strong>Debug:</strong> 
                Toko: {{ $debugInfo['toko_count'] ?? 0 }} | 
                Retur: {{ $debugInfo['retur_count'] ?? 0 }} | 
                Recent: {{ $debugInfo['recent_retur_count'] ?? 0 }}
            </div>
            @endif

            <div class="row">
                
                <!-- SIDEBAR FILTER (25%) -->
                <div class="col-lg-3">
                    <div class="filter-sidebar">
                        
                        <!-- Period -->
                        <div class="filter-section">
                            <div class="filter-header" data-toggle="collapse" data-target="#periodSection">
                                <i class="fas fa-calendar mr-2"></i>Period
                            </div>
                            <div class="collapse show" id="periodSection">
                                <div class="filter-body">
                                    <select id="periodFilter" class="form-control form-control-sm">
                                        @foreach($filterOptions['periods'] as $period)
                                            <option value="{{ $period['value'] }}" {{ $period['value'] == 6 ? 'selected' : '' }}>
                                                {{ $period['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Partners -->
                        <div class="filter-section">
                            <div class="filter-header" data-toggle="collapse" data-target="#partnerSection">
                                <i class="fas fa-store mr-2"></i>Partners ({{ count($filterOptions['partners']) }})
                            </div>
                            <div class="collapse show" id="partnerSection">
                                <div class="filter-body">
                                    <input type="text" id="partnerSearch" class="form-control form-control-sm mb-2" placeholder="Search...">
                                    
                                    <div class="filter-item">
                                        <label><input type="checkbox" id="selectAllPartners"> <strong>Select All</strong></label>
                                    </div>
                                    
                                    @foreach($filterOptions['partners'] as $partner)
                                        <div class="filter-item partner-item" data-name="{{ strtolower($partner->nama_toko) }}">
                                            <label>
                                                <input type="checkbox" name="partners[]" value="{{ $partner->toko_id }}">
                                                <div>
                                                    {{ $partner->nama_toko }}
                                                    <small class="text-muted d-block">{{ $partner->wilayah_kota_kabupaten }}</small>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Regions -->
                        <div class="filter-section">
                            <div class="filter-header" data-toggle="collapse" data-target="#regionSection">
                                <i class="fas fa-map-marker-alt mr-2"></i>Regions ({{ count($filterOptions['regions']) }})
                            </div>
                            <div class="collapse show" id="regionSection">
                                <div class="filter-body">
                                    <div class="filter-item">
                                        <label><input type="checkbox" id="selectAllRegions"> <strong>Select All</strong></label>
                                    </div>
                                    @foreach($filterOptions['regions'] as $region)
                                        <div class="filter-item">
                                            <label>
                                                <input type="checkbox" name="regions[]" value="{{ $region }}">
                                                {{ $region }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Products -->
                        <div class="filter-section">
                            <div class="filter-header" data-toggle="collapse" data-target="#productSection">
                                <i class="fas fa-box mr-2"></i>Products ({{ count($filterOptions['products']) }})
                            </div>
                            <div class="collapse show" id="productSection">
                                <div class="filter-body">
                                    <div class="filter-item">
                                        <label><input type="checkbox" id="selectAllProducts"> <strong>Select All</strong></label>
                                    </div>
                                    @foreach($filterOptions['products'] as $product)
                                        <div class="filter-item">
                                            <label>
                                                <input type="checkbox" name="products[]" value="{{ $product->barang_id }}">
                                                {{ $product->nama_barang }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="p-3">
                            <button class="btn btn-secondary btn-sm btn-block mb-2" id="clearFilters">Clear Filters</button>
                            <button class="btn btn-analytics btn-sm btn-block mb-2" id="refreshData">Refresh Data</button>
                            <button class="btn btn-info btn-sm btn-block" id="loadSample">Sample Data</button>
                        </div>

                    </div>
                </div>

                <!-- MAIN CONTENT (75%) -->
                <div class="col-lg-9">
                    <div class="analytics-content position-relative">

                        <!-- Loading -->
                        <div id="loadingOverlay" class="loading-overlay" style="display: none;">
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-2"></div>
                                <div>Loading Analytics...</div>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6">
                                <div class="summary-card">
                                    <div class="summary-value" id="totalPartners">0</div>
                                    <div class="summary-label">Partners</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="summary-card">
                                    <div class="summary-value" id="avgPerformance">0</div>
                                    <div class="summary-label">Avg Score</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="summary-card">
                                    <div class="summary-value" id="topPerformers">0</div>
                                    <div class="summary-label">A+ Grade</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="summary-card">
                                    <div class="summary-value" id="totalRevenue">Rp 0</div>
                                    <div class="summary-label">Revenue</div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row mb-4">
                            <div class="col-lg-5">
                                <div class="chart-card">
                                    <div class="chart-header">
                                        <h5 class="chart-title"><i class="fas fa-chart-pie mr-2"></i>Grade Distribution</h5>
                                    </div>
                                    <div class="chart-body">
                                        <div class="chart-container">
                                            <canvas id="gradeChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="chart-card">
                                    <div class="chart-header">
                                        <h5 class="chart-title"><i class="fas fa-chart-scatter mr-2"></i>Performance vs Revenue</h5>
                                    </div>
                                    <div class="chart-body">
                                        <div class="chart-container">
                                            <canvas id="scatterChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-card">
                            <div class="chart-header">
                                <h5 class="chart-title"><i class="fas fa-table mr-2"></i>Partner Ranking</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Partner</th>
                                            <th>Score</th>
                                            <th>Grade</th>
                                            <th>Sell-Through</th>
                                            <th>Revenue</th>
                                            <th>Cycles</th>
                                        </tr>
                                    </thead>
                                    <tbody id="partnerTable">
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                                <div>Click "Refresh Data" to load analytics</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('plugins/chart.js/Chart.min.js') }}"></script>
<script src="{{ asset('js/analytics.js') }}"></script>
@endpush