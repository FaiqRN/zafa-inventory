@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Summary Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['total_batch'] }}</h3>
                    <p>Total Batch</p>
                </div>
                <div class="icon">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($summary['total_sisa_stok']) }}</h3>
                    <p>Total Sisa Stok</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($summary['total_terpakai']) }}</h3>
                    <p>Total Terpakai</p>
                </div>
                <div class="icon">
                    <i class="fas fa-truck"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number_format($summary['total_stok_awal']) }}</h3>
                    <p>Total Stok Awal</p>
                </div>
                <div class="icon">
                    <i class="fas fa-database"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Barang Info -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">
                        <i class="fas fa-box mr-2"></i>{{ $barang->nama_barang }}
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light">{{ $barang->barang_kode }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Satuan:</strong> {{ $barang->satuan }}
                        </div>
                        <div class="col-md-3">
                            <strong>Batch Tertua:</strong> {{ $summary['batch_tertua'] }}
                        </div>
                        <div class="col-md-3">
                            <strong>Batch Terbaru:</strong> {{ $summary['batch_terbaru'] }}
                        </div>
                        <div class="col-md-3 text-right">
                            <a href="{{ route('barang.tambah-stok', $barang->barang_id) }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus mr-1"></i> Tambah Stok
                            </a>
                            <a href="{{ route('barang.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Riwayat Batch Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-2"></i>Riwayat Batch Stok (FIFO)
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tableBatch" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Tanggal Stok</th>
                                <th width="12%" class="text-right">Stok Awal</th>
                                <th width="12%" class="text-right">Sisa Stok</th>
                                <th width="12%" class="text-right">Terpakai</th>
                                <th width="30%">Catatan</th>
                                <th width="14%" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@push('js')
<script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

<script>
$(document).ready(function() {
    const table = $('#tableBatch').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("barang.detail-batch-datatable", $barang->barang_id) }}',
            type: 'GET'
        },
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'tanggal',
                name: 'tanggal'
            },
            {
                data: 'stok_awal',
                name: 'stok_awal',
                className: 'text-right',
                render: function(data) {
                    return new Intl.NumberFormat('id-ID').format(data);
                }
            },
            {
                data: 'sisa_stok',
                name: 'sisa_stok',
                className: 'text-right',
                render: function(data) {
                    return new Intl.NumberFormat('id-ID').format(data);
                }
            },
            {
                data: 'terpakai',
                name: 'terpakai',
                className: 'text-right',
                render: function(data) {
                    return new Intl.NumberFormat('id-ID').format(data);
                }
            },
            {
                data: 'catatan',
                name: 'catatan',
                render: function(data) {
                    return data || '-';
                }
            },
            {
                data: 'status',
                name: 'status',
                className: 'text-center',
                render: function(data, type, row) {
                    const badgeClass = row.status_class;
                    return `<span class="badge badge-${badgeClass}">${data}</span>`;
                }
            }
        ],
        order: [[1, 'asc']], // Order by tanggal ASC (FIFO)
        pageLength: 25,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
        },
        responsive: true,
        autoWidth: false
    });
});
</script>
@endpush
