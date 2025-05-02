@extends('layouts.template')

@section('page_title', 'Dashboard')

@php
    $activemenu = 'dashboard';
    $breadcrumb = (object) [
        'title' => 'Dashboard',
        'list' => ['Home', 'Dashboard']
    ];
@endphp

@push('css')
<style>
    .small-box {
        border-radius: 10px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Info boxes -->
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>50</h3>
                    <p>Total Barang</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <a href="{{ url('/barang') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-md-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>25</h3>
                    <p>Total Toko</p>
                </div>
                <div class="icon">
                    <i class="fas fa-store"></i>
                </div>
                <a href="{{ url('/toko') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-md-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>120</h3>
                    <p>Pengiriman Bulan Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-truck"></i>
                </div>
                <a href="{{ url('/pengiriman') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-md-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>15</h3>
                    <p>Retur Bulan Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <a href="{{ url('/retur') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
    <!-- /.row -->
    
    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <div class="col-md-8">
            <!-- Grafik Pengiriman Chart -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-1"></i>
                        Grafik Pengiriman Barang
                    </h3>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="pengiriman-chart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <!-- /.card -->

            <!-- Pengiriman Terbaru -->
            <div class="card">
                <div class="card-header border-transparent">
                    <h3 class="card-title">Pengiriman Terbaru</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table m-0">
                            <thead>
                                <tr>
                                    <th>No. Pengiriman</th>
                                    <th>Toko</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042901') }}">P2025042901</a></td>
                                    <td>Toko Sejahtera</td>
                                    <td>29 Apr 2025</td>
                                    <td><span class="badge badge-success">Terkirim</span></td>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042901') }}" class="btn btn-xs btn-info">Detail</a></td>
                                </tr>
                                <tr>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042801') }}">P2025042801</a></td>
                                    <td>Toko Makmur</td>
                                    <td>28 Apr 2025</td>
                                    <td><span class="badge badge-warning">Dalam Pengiriman</span></td>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042801') }}" class="btn btn-xs btn-info">Detail</a></td>
                                </tr>
                                <tr>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042701') }}">P2025042701</a></td>
                                    <td>Toko Bahagia</td>
                                    <td>27 Apr 2025</td>
                                    <td><span class="badge badge-success">Terkirim</span></td>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042701') }}" class="btn btn-xs btn-info">Detail</a></td>
                                </tr>
                                <tr>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042601') }}">P2025042601</a></td>
                                    <td>Toko Bersama</td>
                                    <td>26 Apr 2025</td>
                                    <td><span class="badge badge-success">Terkirim</span></td>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042601') }}" class="btn btn-xs btn-info">Detail</a></td>
                                </tr>
                                <tr>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042501') }}">P2025042501</a></td>
                                    <td>Toko Jaya</td>
                                    <td>25 Apr 2025</td>
                                    <td><span class="badge badge-success">Terkirim</span></td>
                                    <td><a href="{{ url('/pengiriman/detail/P2025042501') }}" class="btn btn-xs btn-info">Detail</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.table-responsive -->
                </div>
                <!-- /.card-body -->
                <div class="card-footer clearfix">
                    <a href="{{ url('/pengiriman') }}" class="btn btn-sm btn-primary float-right">Lihat Semua Pengiriman</a>
                </div>
                <!-- /.card-footer -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
        
        <div class="col-md-4">
            <!-- Barang Terlaris -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Barang Terlaris</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        <li class="item">
                            <div class="product-img">
                                <img src="{{ asset('adminlte/dist/img/default-150x150.png') }}" alt="Product Image" class="img-size-50">
                            </div>
                            <div class="product-info">
                                <a href="{{ url('/barang/1') }}" class="product-title">Barang A
                                    <span class="badge badge-info float-right">120 unit</span></a>
                                <span class="product-description">
                                    SKU: B001
                                </span>
                            </div>
                        </li>
                        <li class="item">
                            <div class="product-img">
                                <img src="{{ asset('adminlte/dist/img/default-150x150.png') }}" alt="Product Image" class="img-size-50">
                            </div>
                            <div class="product-info">
                                <a href="{{ url('/barang/2') }}" class="product-title">Barang B
                                    <span class="badge badge-info float-right">90 unit</span></a>
                                <span class="product-description">
                                    SKU: B002
                                </span>
                            </div>
                        </li>
                        <li class="item">
                            <div class="product-img">
                                <img src="{{ asset('adminlte/dist/img/default-150x150.png') }}" alt="Product Image" class="img-size-50">
                            </div>
                            <div class="product-info">
                                <a href="{{ url('/barang/3') }}" class="product-title">Barang C
                                    <span class="badge badge-info float-right">85 unit</span></a>
                                <span class="product-description">
                                    SKU: B003
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
                <!-- /.card-body -->
                <div class="card-footer text-center">
                    <a href="{{ url('/laporan-penjualan') }}" class="uppercase">Lihat Semua</a>
                </div>
                <!-- /.card-footer -->
            </div>
            <!-- /.card -->

            <!-- Stok Menipis -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Stok Menipis</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        <li class="item">
                            <div class="product-img">
                                <img src="{{ asset('adminlte/dist/img/default-150x150.png') }}" alt="Product Image" class="img-size-50">
                            </div>
                            <div class="product-info">
                                <a href="{{ url('/barang/5') }}" class="product-title">Barang E
                                    <span class="badge badge-danger float-right">5 unit</span></a>
                                <span class="product-description">
                                    SKU: B005
                                </span>
                            </div>
                        </li>
                        <li class="item">
                            <div class="product-img">
                                <img src="{{ asset('adminlte/dist/img/default-150x150.png') }}" alt="Product Image" class="img-size-50">
                            </div>
                            <div class="product-info">
                                <a href="{{ url('/barang/8') }}" class="product-title">Barang H
                                    <span class="badge badge-danger float-right">7 unit</span></a>
                                <span class="product-description">
                                    SKU: B008
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
                <!-- /.card-body -->
                <div class="card-footer text-center">
                    <a href="{{ url('/barang') }}" class="uppercase">Lihat Semua</a>
                </div>
                <!-- /.card-footer -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>
@endsection

@push('js')
<script>
$(function () {
    // Pengiriman Chart
    var pengirimanCanvas = document.getElementById('pengiriman-chart').getContext('2d');
    var pengirimanChart = new Chart(pengirimanCanvas, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Pengiriman',
                data: [65, 59, 80, 81, 90, 110, 120, 130, 110, 105, 95, 120],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 3,
                pointRadius: 3,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
});
</script>
@endpush