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
<div class="pengiriman-page">
    <div class="card">
        <div class="card-header">
            {{-- <h3 class="card-title">Daftar Pengiriman</h3> --}}
            <div class="card-tools">
                @can('edit-pengiriman')
                <button type="button" class="btn btn-success btn-sm mr-2" id="btnBulkApprove" style="display: none;" onclick="bulkApprove()">
                    <i class="fas fa-check-double"></i> Approve Selected (<span id="selectedCount">0</span>)
                </button>
                @endcan
                @can('create-pengiriman')
                <button type="button" class="btn btn-primary btn-sm" onclick="modalAction('{{ url('/pengiriman/create_ajax') }}')">
                    <i class="fas fa-plus"></i> Tambah Pengiriman
                </button>
                @endcan
            </div>
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
                    <label for="filter_status">Status</label>
                    <select id="filter_status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="proses">Proses</option>
                        <option value="terkirim">Terkirim</option>
                        <option value="batal">Batal</option>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-3 mb-2">
                    <label for="filter_tanggal">Tanggal</label>
                    <input type="date" id="filter_tanggal" class="form-control">
                </div>
                <div class="col-12 col-sm-6 col-md-3 mb-2">
                    <div class="d-none d-md-block" aria-hidden="true">&nbsp;</div>
                    <button type="button" class="btn btn-info btn-block" onclick="filterData()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <div class="table-responsive pengiriman-table-wrap">
            <table class="table table-bordered table-striped table-hover table-sm w-100" id="table_pengiriman">
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
    
    <div id="myModal" class="modal fade animate shake" tabindex="-1" data-backdrop="static" data-keyboard="false" data-width="75%" aria-hidden="true"></div>
</div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('css/pengiriman-mobile.css') }}">
<style>
    #table_pengiriman th,
    #table_pengiriman td {
        vertical-align: middle;
    }

    #table_pengiriman .btn {
        min-width: 34px;
    }
</style>
@endpush

@push('js')
<script>
let dataTable;
let selectedItems = [];
const canEditPengiriman = @json(auth()->check() && auth()->user()->can('edit-pengiriman'));

function modalAction(url = '') {
    $.get(url)
        .done(function(response) {
            const $modal = $('#myModal');
            $modal.html(response);

            $modal.find('script').each(function() {
                const scriptText = this.text || this.textContent || this.innerHTML || '';
                if (scriptText.trim().length) {
                    $.globalEval(scriptText);
                }
            });

            $modal.modal('show');
        })
        .fail(function() {
            AlertHelper.error('Error', 'Gagal memuat form pengiriman');
        });
}

function filterData() {
    selectedItems = [];
    $('#selectAll').prop('checked', false).prop('indeterminate', false);
    updateBulkButton();

    if (dataTable) {
        dataTable.ajax.reload();
    }
}

function updateBulkButton() {
    if (!canEditPengiriman) {
        return;
    }

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
    if (!canEditPengiriman) {
        $('#selectAll').prop('checked', false).prop('indeterminate', false);
        return;
    }

    let allProsesCheckboxes = $('.row-checkbox:visible');
    let checkedCount = allProsesCheckboxes.filter(':checked').length;
    $('#selectAll').prop('checked', allProsesCheckboxes.length > 0 && checkedCount === allProsesCheckboxes.length);
    $('#selectAll').prop('indeterminate', checkedCount > 0 && checkedCount < allProsesCheckboxes.length);
}

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#table_pengiriman')) {
        $('#table_pengiriman').DataTable().destroy();
    }

    dataTable = $('#table_pengiriman').DataTable({
        serverSide: true,
        processing: true,
        responsive: false,
        autoWidth: false,
        scrollX: true,
        ajax: {
            url: "{{ url('pengiriman/list') }}",
            type: "POST",
            data: function(d) {
                let tanggal = $('#filter_tanggal').val();

                d.toko_id = $('#filter_toko').val();
                d.status = $('#filter_status').val();
                d.start_date = tanggal || '';
                d.end_date = tanggal || '';
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            error: function(xhr) {
                AlertHelper.ajaxError('Error!', xhr, 'Gagal memuat data tabel pengiriman', false);
            }
        },
        columns: [
            {
                data: 'nomer_pengiriman',
                className: 'text-center align-middle',
                visible: canEditPengiriman,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (canEditPengiriman && row.status === 'proses') {
                        let checked = selectedItems.includes(data) ? 'checked' : '';
                        return `<input type="checkbox" class="row-checkbox" data-nomer="${data}" ${checked} onchange="toggleSelectItem('${data}', this.checked)">`;
                    }
                    return '';
                }
            },
            {
                data: 'DT_RowIndex',
                className: 'text-center align-middle',
                orderable: false,
                searchable: false
            },
            {
                data: 'nomer_pengiriman',
                className: 'align-middle',
                orderable: true
            },
            {
                data: 'formatted_tanggal',
                className: 'align-middle',
                orderable: true
            },
            {
                data: 'toko_nama',
                className: 'align-middle',
                orderable: false
            },
            {
                data: 'total_jumlah',
                className: 'text-center align-middle',
                orderable: false
            },
            {
                data: 'status_label',
                className: 'text-center align-middle',
                orderable: false
            },
            {
                data: 'nomer_pengiriman',
                className: 'text-center align-middle text-nowrap',
                orderable: false,
                render: function(data, type, row) {
                    let btnStatus = '';
                    if (canEditPengiriman && row.status === 'proses') {
                        btnStatus = `
                            <button type="button" class="btn btn-success btn-sm mr-1 mb-1" onclick="updateStatus('${data}', 'terkirim')" title="Ubah ke Terkirim">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm mr-1 mb-1" onclick="updateStatus('${data}', 'batal')" title="Batalkan">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                    }
                    
                    return `
                        <div class="d-flex justify-content-center flex-wrap">
                            ${btnStatus}
                            <button type="button" class="btn btn-info btn-sm mr-1 mb-1" onclick="showDetail('${data}')" title="Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="{{ url('pengiriman') }}/${data}/print" target="_blank" class="btn btn-secondary btn-sm mb-1" title="Print">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    `;
                }
            }
        ],
        columnDefs: [
            { responsivePriority: 1, targets: [2, 7] },
            { responsivePriority: 2, targets: [6] },
            { responsivePriority: 3, targets: [3] },
            { responsivePriority: 4, targets: [5] },
            { responsivePriority: 5, targets: [4] },
            { responsivePriority: 6, targets: [1] }
        ],
        order: [[2, 'desc']],
        drawCallback: function() {
            updateSelectAllCheckbox();
        }
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

    dataTable.on('draw', function() {
        recalculateTableLayout();
    });

    $(window)
        .off('resize.pengiriman orientationchange.pengiriman')
        .on('resize.pengiriman orientationchange.pengiriman', function() {
            recalculateTableLayout();
        });

    recalculateTableLayout();
});

function updateStatus(nomer, status) {
    if (!canEditPengiriman) {
        AlertHelper.error('Error!', 'Anda tidak memiliki izin untuk mengubah status pengiriman.', false);
        return;
    }

    let title = '';
    let text = '';
    
    if (status === 'terkirim') {
        title = 'Ubah Status ke Terkirim?';
        text = 'Stok barang akan berkurang sesuai jumlah pengiriman';
    } else if (status === 'batal') {
        title = 'Batalkan Pengiriman?';
        text = 'Jika pengiriman sudah terkirim, stok akan dikembalikan';
    }
    
    AlertHelper.confirm(title, text, 'Ya, Lanjutkan', 'Batal').then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('pengiriman') }}/" + nomer + "/update_status",
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status
                },
                beforeSend: function() {
                    AlertHelper.loading();
                },
                success: function(response) {
                    if (response.status === 'success') {
                        AlertHelper.success('Berhasil!', response.message);
                        dataTable.ajax.reload();
                    } else {
                        AlertHelper.error('Gagal!', simplifyPengirimanErrorMessage(response.message), false);
                    }
                },
                error: function(xhr) {
                    const message = simplifyPengirimanErrorMessage(
                        AlertHelper.parseAjaxError(xhr, 'Terjadi kesalahan pada server')
                    );
                    AlertHelper.error('Error!', message, false);
                }
            });
        }
    });
}

function simplifyPengirimanErrorMessage(message) {
    const parsedMessage = String(message || '').trim();

    if (/stok tidak mencukupi/i.test(parsedMessage)) {
        return 'Stok tidak mencukupi';
    }

    return parsedMessage || 'Terjadi kesalahan pada server';
}

function showDetail(nomer) {
    modalAction("{{ url('pengiriman') }}/" + nomer + "/show_ajax");
}

// Select All functionality
$(document).off('change', '#selectAll').on('change', '#selectAll', function() {
    if (!canEditPengiriman) {
        $(this).prop('checked', false).prop('indeterminate', false);
        return;
    }

    let checked = $(this).prop('checked');
    $('.row-checkbox:visible').each(function() {
        $(this).prop('checked', checked);
        toggleSelectItem($(this).data('nomer'), checked);
    });
});

// Bulk Approve function
function bulkApprove() {
    if (!canEditPengiriman) {
        AlertHelper.error('Error!', 'Anda tidak memiliki izin untuk approve pengiriman.', false);
        return;
    }

    if (selectedItems.length === 0) {
        AlertHelper.warning('Peringatan', 'Pilih minimal satu pengiriman untuk di-approve', false);
        return;
    }

    AlertHelper.confirm(
        'Approve ' + selectedItems.length + ' Pengiriman?',
        'Stok barang akan berkurang sesuai jumlah pengiriman yang dipilih',
        'Ya, Approve Semua',
        'Batal'
    ).then((result) => {
        if (result.isConfirmed) {
            processBulkApprove();
        }
    });
}

function processBulkApprove() {
    if (!canEditPengiriman) {
        AlertHelper.error('Error!', 'Anda tidak memiliki izin untuk approve pengiriman.', false);
        return;
    }

    let total = selectedItems.length;
    let processed = 0;
    let success = 0;
    let failed = 0;
    let failedReasons = [];

    AlertHelper.progress('Memproses Pengiriman', 0, total);

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
                failedReasons.push(simplifyPengirimanErrorMessage(response.message));
            }
            AlertHelper.updateProgress(processed, total);
        }).catch(xhr => {
            processed++;
            failed++;
            failedReasons.push(
                simplifyPengirimanErrorMessage(
                    AlertHelper.parseAjaxError(xhr, 'Terjadi kesalahan pada server')
                )
            );
            AlertHelper.updateProgress(processed, total);
        });
    });

    Promise.all(promises).then(() => {
        selectedItems = [];
        updateBulkButton();
        dataTable.ajax.reload();

        const uniqueReasons = [...new Set(failedReasons)];
        let message = `Berhasil: ${success} pengiriman`;

        if (failed > 0) {
            if (success === 0 && uniqueReasons.length === 1) {
                message = uniqueReasons[0];
            } else {
                message += `<br>Gagal: ${failed} pengiriman`;
                if (uniqueReasons.length === 1) {
                    message += `<br>Alasan: ${uniqueReasons[0]}`;
                }
            }
        }

        if (failed === 0) {
            AlertHelper.success('Berhasil!', message);
        } else {
            AlertHelper.warning('Selesai dengan Error', message, false);
        }
    });
}
</script>
@endpush