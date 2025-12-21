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
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Retur Barang</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-12 col-sm-6 col-md-3 mb-2">
                <label>Toko</label>
                <select id="filter_toko" class="form-control">
                    <option value="">Semua Toko</option>
                    @foreach($toko as $t)
                        <option value="{{ $t->toko_id }}">{{ $t->nama_toko }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-2">
                <label>Tanggal</label>
                <input type="date" id="filter_date" class="form-control">
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-2">
                <label class="d-none d-md-block">&nbsp;</label>
                <button type="button" class="btn btn-info btn-block" onclick="filterData()">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>

        <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover table-sm" id="table_retur">
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

<div id="myModal" class="modal fade animate shake" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" data-width="75%" aria-hidden="true"></div>
@endsection

@push('css')
@endpush

@push('js')
<script>
let dataTable;

function modalAction(url = '') {
    $('#myModal').load(url, function() {
        $('#myModal').modal('show');
    });
}

function filterData() {
    dataTable.ajax.reload();
}

function showDetail(nomer) {
    modalAction("{{ url('retur') }}/" + nomer);
}

$(document).ready(function() {
    dataTable = $('#table_retur').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: "{{ url('retur/data') }}",
            type: "GET",
            data: function(d) {
                d.toko_id = $('#filter_toko').val();
                d.date = $('#filter_date').val();
            }
        },
        columns: [
            {
                data: 'DT_RowIndex',
                className: 'text-center',
                orderable: false,
                searchable: false
            },
            {
                data: 'nomer_pengiriman',
                orderable: true
            },
            {
                data: 'formatted_tanggal_pengiriman',
                orderable: true
            },
            {
                data: 'tanggal_retur',
                className: 'text-center',
                orderable: false
            },
            {
                data: 'toko_nama',
                orderable: false
            },
            {
                data: 'nomer_pengiriman',
                className: 'text-center',
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <button type="button" class="btn btn-info btn-sm" onclick="showDetail('${data}')" title="Detail">
                            <i class="fas fa-eye"></i> Detail
                        </button>
                    `;
                }
            }
        ],
        order: [[2, 'desc']]
    });
});
</script>
@endpush