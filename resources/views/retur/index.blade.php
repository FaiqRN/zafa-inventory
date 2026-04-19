@extends('layouts.template')

@section('page_title', 'Retur Barang')

@php
    $activemenu = 'retur';
    $breadcrumb = (object) [
        'title' => 'Retur Barang',
        'list' => ['Home', 'Transaksi', 'Retur Barang']
    ];
@endphp

@section('content')
<div class="retur-page">
    <div class="card">
        <div class="card-header">
            {{-- <h3 class="card-title">Daftar Retur Barang</h3> --}}
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12 col-sm-6 col-md-3 mb-2">
                    <label for="filter_toko">Toko</label>
                    <select id="filter_toko" class="form-control">
                        <option value="">Semua Toko</option>
                        @foreach($toko as $t)
                            <option value="{{ $t->toko_id }}">{{ $t->nama_toko }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3 mb-2">
                    <label for="filter_date">Tanggal</label>
                    <input type="date" id="filter_date" class="form-control">
                </div>
                <div class="col-12 col-sm-6 col-md-3 mb-2">
                    <div class="d-none d-md-block" aria-hidden="true">&nbsp;</div>
                    <button type="button" class="btn btn-info btn-block" onclick="filterData()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <div class="table-responsive retur-table-wrap">
            <table class="table table-bordered table-striped table-hover table-sm w-100" id="table_retur">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Pengiriman</th>
                        <th>Tanggal Pengiriman</th>
                        <th>Tanggal Retur</th>
                        <th>Toko</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade animate shake" tabindex="-1" data-backdrop="static" data-keyboard="false" data-width="75%" aria-hidden="true"></div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('css/retur-mobile.css') }}">
<style>
    #table_retur th,
    #table_retur td {
        vertical-align: middle;
    }

    #table_retur .btn {
        min-width: 78px;
    }
</style>
@endpush

@push('js')
<script>
let dataTable;
const canCreateRetur = @json(auth()->check() && auth()->user()->can('create-retur'));

function modalAction(url = '') {
    $('#myModal').load(url, function() {
        $('#myModal').modal('show');
    });
}

function filterData() {
    if (dataTable) {
        dataTable.ajax.reload();
    }
}

function showDetail(nomer) {
    modalAction("{{ url('retur') }}/" + nomer);
}

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#table_retur')) {
        $('#table_retur').DataTable().destroy();
    }

    dataTable = $('#table_retur').DataTable({
        serverSide: true,
        processing: true,
        responsive: false,
        autoWidth: false,
        scrollX: true,
        ajax: {
            url: "{{ url('retur/data') }}",
            type: "GET",
            data: function(d) {
                d.toko_id = $('#filter_toko').val();
                d.date = $('#filter_date').val();
            },
            error: function(xhr) {
                let message = xhr.responseJSON?.message || 'Gagal memuat data tabel retur';
                AlertHelper.error('Error!', message);
            }
        },
        columns: [
            {
                data: 'DT_RowIndex',
                className: 'text-center align-middle',
                orderable: false,
                searchable: false
            },
            {
                data: 'nomer_pengiriman',
                className: 'align-middle text-nowrap',
                orderable: true
            },
            {
                data: 'formatted_tanggal_pengiriman',
                className: 'align-middle text-nowrap',
                orderable: true
            },
            {
                data: 'tanggal_retur',
                className: 'text-center align-middle text-nowrap',
                orderable: false
            },
            {
                data: 'toko_nama',
                className: 'align-middle',
                orderable: false
            },
            {
                data: 'nomer_pengiriman',
                className: 'text-center align-middle text-nowrap',
                orderable: false,
                render: function(data, type, row) {
                    const actionText = canCreateRetur ? 'Retur' : 'Detail';

                    return `
                        <div class="d-flex justify-content-center flex-wrap">
                            <button type="button" class="btn btn-info btn-sm" onclick="showDetail('${data}')" title="${actionText}">
                                <i class="fas fa-eye"></i> ${actionText}
                            </button>
                        </div>
                    `;
                }
            }
        ],
        columnDefs: [
            { responsivePriority: 1, targets: [1, 5] },
            { responsivePriority: 2, targets: [3] },
            { responsivePriority: 3, targets: [2] },
            { responsivePriority: 4, targets: [4] },
            { responsivePriority: 5, targets: [0] }
        ],
        order: []
    });

    const recalculateTableLayout = function() {
        if (!dataTable) {
            return;
        }

        dataTable.columns.adjust();

        if (dataTable.responsive) {
            dataTable.responsive.recalc();
        }
    };

    $(window)
        .off('resize.retur orientationchange.retur')
        .on('resize.retur orientationchange.retur', function() {
            recalculateTableLayout();
        });

    recalculateTableLayout();
});
</script>
@endpush
