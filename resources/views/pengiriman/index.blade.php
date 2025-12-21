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
            <button type="button" class="btn btn-success btn-sm mr-2" id="btnBulkApprove" style="display: none;" onclick="bulkApprove()">
                <i class="fas fa-check-double"></i> Approve Selected (<span id="selectedCount">0</span>)
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="modalAction('{{ url('/pengiriman/create_ajax') }}')">
                <i class="fas fa-plus"></i> Tambah Pengiriman
            </button>
        </div>
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
                <label>Status</label>
                <select id="filter_status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="proses">Proses</option>
                    <option value="terkirim">Terkirim</option>
                    <option value="batal">Batal</option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-3 mb-2">
                <label>Tanggal</label>
                <input type="date" id="filter_tanggal" class="form-control">
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-2">
                <label class="d-none d-md-block">&nbsp;</label>
                <button type="button" class="btn btn-info btn-block" onclick="filterData()">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>

        <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover table-sm" id="table_pengiriman">
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">
                        <input type="checkbox" id="selectAll" title="Select All Proses">
                    </th>
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
</div>

<div id="myModal" class="modal fade animate shake" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" data-width="75%" aria-hidden="true"></div>
@endsection

@push('css')
@endpush

@push('js')
<script>
let dataTable;
let selectedItems = [];

function modalAction(url = '') {
    $('#myModal').load(url, function() {
        $('#myModal').modal('show');
    });
}

function filterData() {
    selectedItems = [];
    updateBulkButton();
    dataTable.ajax.reload();
}

function updateBulkButton() {
    $('#selectedCount').text(selectedItems.length);
    if (selectedItems.length > 0) {
        $('#btnBulkApprove').show();
    } else {
        $('#btnBulkApprove').hide();
    }
}

function toggleSelectItem(nomer, checked) {
    if (checked) {
        if (!selectedItems.includes(nomer)) {
            selectedItems.push(nomer);
        }
    } else {
        selectedItems = selectedItems.filter(item => item !== nomer);
    }
    updateBulkButton();
    updateSelectAllCheckbox();
}

function updateSelectAllCheckbox() {
    let allProsesCheckboxes = $('.row-checkbox:visible');
    let checkedCount = allProsesCheckboxes.filter(':checked').length;
    $('#selectAll').prop('checked', allProsesCheckboxes.length > 0 && checkedCount === allProsesCheckboxes.length);
    $('#selectAll').prop('indeterminate', checkedCount > 0 && checkedCount < allProsesCheckboxes.length);
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
                data: 'nomer_pengiriman',
                className: 'text-center',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (row.status === 'proses') {
                        let checked = selectedItems.includes(data) ? 'checked' : '';
                        return `<input type="checkbox" class="row-checkbox" data-nomer="${data}" ${checked} onchange="toggleSelectItem('${data}', this.checked)">`;
                    }
                    return '';
                }
            },
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
    let title = '';
    let text = '';
    let icon = 'warning';
    
    if (status === 'terkirim') {
        title = 'Ubah Status ke Terkirim?';
        text = 'Stok barang akan berkurang sesuai jumlah pengiriman';
        icon = 'question';
    } else if (status === 'batal') {
        title = 'Batalkan Pengiriman?';
        text = 'Jika pengiriman sudah terkirim, stok akan dikembalikan';
        icon = 'warning';
    }
    
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('pengiriman') }}/" + nomer + "/update_status",
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status
                },
                beforeSend: function() {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        dataTable.ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan pada server'
                    });
                }
            });
        }
    });
}

function showDetail(nomer) {
    modalAction("{{ url('pengiriman') }}/" + nomer + "/show_ajax");
}

// Select All functionality
$('#selectAll').on('change', function() {
    let checked = $(this).prop('checked');
    $('.row-checkbox:visible').each(function() {
        $(this).prop('checked', checked);
        toggleSelectItem($(this).data('nomer'), checked);
    });
});

// Bulk Approve function
function bulkApprove() {
    if (selectedItems.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Peringatan',
            text: 'Pilih minimal satu pengiriman untuk di-approve'
        });
        return;
    }

    Swal.fire({
        title: 'Approve ' + selectedItems.length + ' Pengiriman?',
        text: 'Stok barang akan berkurang sesuai jumlah pengiriman yang dipilih',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Approve Semua',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            processBulkApprove();
        }
    });
}

function processBulkApprove() {
    let total = selectedItems.length;
    let processed = 0;
    let success = 0;
    let failed = 0;
    let failedItems = [];

    Swal.fire({
        title: 'Memproses...',
        html: `<div>Memproses <b>0</b> dari <b>${total}</b> pengiriman</div><div class="progress mt-3"><div class="progress-bar" role="progressbar" style="width: 0%"></div></div>`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let promises = selectedItems.map(nomer => {
        return $.ajax({
            url: "{{ url('pengiriman') }}/" + nomer + "/update_status",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                status: 'terkirim'
            }
        }).then(response => {
            processed++;
            if (response.status === 'success') {
                success++;
            } else {
                failed++;
                failedItems.push(nomer);
            }
            updateProgress(processed, total);
        }).catch(error => {
            processed++;
            failed++;
            failedItems.push(nomer);
            updateProgress(processed, total);
        });
    });

    Promise.all(promises).then(() => {
        selectedItems = [];
        updateBulkButton();
        dataTable.ajax.reload();

        let message = `Berhasil: ${success} pengiriman`;
        if (failed > 0) {
            message += `<br>Gagal: ${failed} pengiriman (${failedItems.join(', ')})`;
        }

        Swal.fire({
            icon: failed === 0 ? 'success' : 'warning',
            title: failed === 0 ? 'Berhasil!' : 'Selesai dengan Error',
            html: message,
            timer: failed === 0 ? 3000 : null,
            showConfirmButton: failed > 0
        });
    });
}

function updateProgress(processed, total) {
    let percent = Math.round((processed / total) * 100);
    Swal.update({
        html: `<div>Memproses <b>${processed}</b> dari <b>${total}</b> pengiriman</div><div class="progress mt-3"><div class="progress-bar" role="progressbar" style="width: ${percent}%">${percent}%</div></div>`
    });
}
</script>
@endpush