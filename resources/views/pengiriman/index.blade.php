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
                    <th width="3%">
                        <input type="checkbox" id="select-all" title="Pilih Semua">
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

function modalAction(url = '') {
    $('#myModal').load(url, function() {
        $('#myModal').modal('show');
    });
}

function filterData() {
    dataTable.ajax.reload(null, false);
}

function resetFilter() {
    $('#filter_toko').val('');
    $('#filter_status').val('');
    $('#filter_tanggal').val('');
    dataTable.ajax.reload(null, false);
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
                d.tanggal_mulai = $('#filter_tanggal').val();
                d.tanggal_akhir = $('#filter_tanggal').val();
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
                    // Hanya tampilkan checkbox untuk status proses
                    if (row.status === 'proses') {
                        return `<input type="checkbox" class="row-checkbox" value="${data}" data-status="${row.status}">`;
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
    
    // Auto filter saat filter berubah (setelah DataTable diinisialisasi)
    $('#filter_toko, #filter_status, #filter_tanggal').on('change', function() {
        dataTable.ajax.reload(null, false);
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
        cancelButtonColor: '#d33',
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
                        dataTable.ajax.reload(null, false);
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

// Fungsi untuk Select All checkbox
$(document).on('change', '#select-all', function() {
    const isChecked = $(this).prop('checked');
    $('.row-checkbox').prop('checked', isChecked);
    updateBulkActionsVisibility();
});

// Fungsi untuk individual checkbox
$(document).on('change', '.row-checkbox', function() {
    const totalCheckboxes = $('.row-checkbox').length;
    const checkedCheckboxes = $('.row-checkbox:checked').length;
    $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
    updateBulkActionsVisibility();
});

// Update visibility tombol aksi massal
function updateBulkActionsVisibility() {
    const checkedCount = $('.row-checkbox:checked').length;
    $('#selected-count').text(checkedCount);
    
    if (checkedCount > 0) {
        $('#bulk-actions').slideDown();
    } else {
        $('#bulk-actions').slideUp();
    }
}

// Clear selection
function clearSelection() {
    $('.row-checkbox, #select-all').prop('checked', false);
    updateBulkActionsVisibility();
}

// Update status massal
function updateStatusBulk(status) {
    const selectedNomers = [];
    $('.row-checkbox:checked').each(function() {
        selectedNomers.push($(this).val());
    });
    
    if (selectedNomers.length === 0) {
        Swal.fire('Perhatian', 'Tidak ada pengiriman yang dipilih', 'warning');
        return;
    }
    
    let title = '';
    let text = '';
    
    if (status === 'terkirim') {
        title = `Ubah ${selectedNomers.length} Pengiriman ke Terkirim?`;
        text = 'Stok barang akan berkurang sesuai jumlah pengiriman';
    } else if (status === 'batal') {
        title = `Batalkan ${selectedNomers.length} Pengiriman?`;
        text = 'Pengiriman akan dibatalkan';
    }
    
    Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: `Mengupdate ${selectedNomers.length} pengiriman`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Update satu per satu
            let successCount = 0;
            let errorCount = 0;
            let totalProcessed = 0;
            
            selectedNomers.forEach(function(nomer) {
                $.ajax({
                    url: "{{ url('pengiriman') }}/" + nomer + "/update_status",
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: status
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            successCount++;
                        } else {
                            errorCount++;
                        }
                    },
                    error: function() {
                        errorCount++;
                    },
                    complete: function() {
                        totalProcessed++;
                        
                        // Jika semua sudah diproses
                        if (totalProcessed === selectedNomers.length) {
                            Swal.fire({
                                icon: successCount > 0 ? 'success' : 'error',
                                title: 'Selesai!',
                                html: `Berhasil: ${successCount}<br>Gagal: ${errorCount}`,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Reload tabel dan clear selection
                            dataTable.ajax.reload(null, false);
                            clearSelection();
                        }
                    }
                });
            });
        }
    });
}
</script>
@endpush
