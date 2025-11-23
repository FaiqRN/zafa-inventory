@extends('layouts.template')

@section('page_title', 'Pengiriman Barang')

@php
    $activemenu = 'pengiriman';
    $breadcrumb = (object) [
        'title' => 'Pengiriman Barang',
        'list' => ['Home', 'Transaksi', 'Pengiriman Barang']
    ];
@endphp

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Pengiriman</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" onclick="modalAction('{{ url('/pengiriman/create_ajax') }}')">
                <i class="fas fa-plus"></i> Tambah Pengiriman
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label>Toko</label>
                <select id="filter_toko" class="form-control">
                    <option value="">Semua Toko</option>
                    @foreach($toko as $t)
                        <option value="{{ $t->toko_id }}">{{ $t->nama_toko }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Status</label>
                <select id="filter_status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="proses">Proses</option>
                    <option value="terkirim">Terkirim</option>
                    <option value="batal">Batal</option>
                </select>
            </div>

            <div class="col-md-2">
                <label>Tanggal</label>
                <input type="date" id="filter_tanggal" class="form-control">
            </div>
            <div class="col-md-2 offset-md-2">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-info btn-block" onclick="filterData()">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>

        <table class="table table-bordered table-striped table-hover table-sm" id="table_pengiriman">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Pengiriman</th>
                    <th>Tanggal</th>
                    <th>Toko</th>
                    <th>Jumlah Kirim</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
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

$(document).ready(function() {
    dataTable = $('#table_pengiriman').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: "{{ url('pengiriman/list') }}",
            type: "POST",
            data: function(d) {
                d.toko_id = $('#filter_toko').val();
                d.status = $('#filter_status').val();
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
                data: 'formatted_tanggal',
                orderable: true
            },
            {
                data: 'toko_nama',
                orderable: false
            },
            {
                data: 'total_jumlah',
                className: 'text-center',
                orderable: false
            },
            {
                data: 'status_label',
                className: 'text-center',
                orderable: false
            },
            {
                data: 'nomer_pengiriman',
                className: 'text-center',
                orderable: false,
                render: function(data, type, row) {
                    let btnStatus = '';
                    if (row.status === 'proses') {
                        btnStatus = `
                            <button type="button" class="btn btn-success btn-sm" onclick="updateStatus('${data}', 'terkirim')" title="Ubah ke Terkirim">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="updateStatus('${data}', 'batal')" title="Batalkan">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                    }
                    
                    return `
                        ${btnStatus}
                        <button type="button" class="btn btn-info btn-sm" onclick="showDetail('${data}')" title="Detail">
                            <i class="fas fa-eye"></i>
                        </button>
                        <a href="{{ url('pengiriman') }}/${data}/print" target="_blank" class="btn btn-secondary btn-sm" title="Print">
                            <i class="fas fa-print"></i>
                        </a>
                    `;
                }
            }
        ],
        order: [[2, 'desc']]
    });
});

function updateStatus(nomer, status) {
    let message = '';
    if (status === 'terkirim') {
        message = 'Ubah status ke "Terkirim"?\n⚠️ Stok barang akan berkurang sesuai jumlah pengiriman.';
    } else if (status === 'batal') {
        message = 'Ubah status ke "Batal"?\n⚠️ Jika pengiriman sudah terkirim, stok akan dikembalikan.';
    }
    
    if (confirm(message)) {
        $.ajax({
            url: "{{ url('pengiriman') }}/" + nomer + "/update_status",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                status: status
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500
                    });
                    dataTable.ajax.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Terjadi kesalahan'
                });
            }
        });
    }
}

function showDetail(nomer) {
    modalAction("{{ url('pengiriman') }}/" + nomer + "/show_ajax");
}
</script>
@endpush
