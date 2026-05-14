@extends('layouts.template')

@section('page_title', 'Setting Z-Score')

@php
    $breadcrumb = (object) [
        'title' => 'Setting Z-Score',
        'list' => ['Home', 'Sistem Pengaturan', 'Setting Z-Score']
    ];
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-9 col-md-12 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="card-tools">
                        @can('create-zscore-setting')
                        <button type="button" class="btn btn-success btn-sm" id="btn-add-zscore">
                            <i class="fas fa-plus mr-1"></i> Tambah Z-Score
                        </button>
                        @endcan
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="form-group">
                        <label for="select-toko">Pilih Toko:</label>
                        <select class="form-control select2" id="select-toko" style="width: 100%;">
                            <option value="">-- Pilih Toko --</option>
                            @foreach($tokos as $toko)
                                <option value="{{ $toko->toko_id }}">{{ $toko->toko_id }} - {{ $toko->nama_toko }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="select-barang">Pilih Barang:</label>
                        <select class="form-control select2" id="select-barang" style="width: 100%;" disabled>
                            <option value="">-- Pilih Barang --</option>
                        </select>
                    </div>

                    <div id="zscore-section" style="display: none;">
                        {{-- FIX #3: Info banner tentang fitur is_active --}}
                        <div class="alert alert-warning alert-sm py-2 mb-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Perhatian:</strong> Hanya satu service level yang dapat <strong>Aktif</strong> per produk per toko.
                            Baris yang aktif digunakan untuk menghitung <em>Safety Stock</em>. Klik <i class="fas fa-check text-success"></i> untuk mengaktifkan.
                        </div>

                        <div class="search-wrapper mb-3">
                            <label for="searchZscore" class="search-label">Cari :</label>
                            <input type="text" class="form-control form-control-sm"
                                   id="searchZscore" placeholder="Cari Z-Score...">
                        </div>

                        <div class="table-wrapper" style="overflow-x: auto;">
                            {{-- FIX #3: Tambah kolom "Aktif" --}}
                            <table class="table table-hover table-sm mb-0" style="min-width: 780px;">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th style="min-width: 120px; width: 150px;">Label</th>
                                        <th style="min-width: 120px; width: 140px;" class="text-center">Service Level (%)</th>
                                        <th style="min-width: 100px; width: 120px;" class="text-right">Z-Score</th>
                                        <th style="width: 90px;" class="text-center">Status</th>
                                        <th style="min-width: 160px; width: 210px;">Keterangan</th>
                                        <th style="min-width: 120px; width: 130px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-zscore">
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="zscore-empty-state" class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-hand-pointer"></i>
                        </div>
                        <p class="empty-state-text">Pilih toko dan barang untuk melihat setting Z-Score</p>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <strong>Informasi:</strong> Z-Score adalah nilai statistik yang digunakan untuk menghitung Safety Stock.
                Semakin tinggi service level, semakin besar Z-Score dan Safety Stock yang dibutuhkan.
                <ul class="mb-0 mt-2">
                    <li>Hanya <strong>satu</strong> baris bertanda <span class="badge badge-success">Aktif</span> yang dipakai dalam kalkulasi.</li>
                    <li>Default: <strong>Standar 95%</strong> (Z = 1.6449) — sesuai praktik umum UMKM consignment.</li>
                    <li>Gunakan tombol <i class="fas fa-check text-success"></i> untuk mengganti service level aktif.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add/Edit Z-Score -->
<div class="modal fade" id="modal-zscore" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modal-title">Tambah Z-Score</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-zscore">
                <div class="modal-body">
                    <input type="hidden" id="zscore-id" name="id">

                    <div class="form-group">
                        <label for="zscore-label">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="zscore-label" name="label"
                               placeholder="Contoh: Standar, Tinggi, Sangat Tinggi" required>
                        <small class="form-text text-muted">Nama deskriptif untuk Z-Score ini</small>
                    </div>

                    <div class="form-group">
                        <label for="zscore-service-level">Service Level (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="zscore-service-level" name="service_level"
                               min="0" max="100" step="0.01" placeholder="Contoh: 95" required>
                        <small class="form-text text-muted">Persentase tingkat layanan (0-100)</small>
                    </div>

                    <div class="form-group">
                        <label for="zscore-z-score">Z-Score <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="zscore-z-score" name="z_score"
                               min="0" step="0.0001" placeholder="Contoh: 1.6449" required>
                        <small class="form-text text-muted">Nilai Z-Score sesuai dengan service level</small>
                    </div>

                    <div class="form-group">
                        <label for="zscore-keterangan">Keterangan</label>
                        <textarea class="form-control" id="zscore-keterangan" name="keterangan"
                                  rows="3" placeholder="Masukkan keterangan atau catatan (opsional)"></textarea>
                    </div>

                    <div class="alert alert-info mb-0">
                        <strong>Contoh nilai Z-Score umum:</strong>
                        <ul class="mb-0 mt-2">
                            <li>90% → Z = 1.2816</li>
                            <li>95% → Z = 1.6449 <em>(default aktif)</em></li>
                            <li>97% → Z = 1.8808</li>
                            <li>99% → Z = 2.3263</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    @if(\Illuminate\Support\Facades\Gate::allows('create-zscore-setting') || \Illuminate\Support\Facades\Gate::allows('edit-zscore-setting'))
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/zscore-setting.css') }}?v={{ time() }}">
@endpush

@push('js')
<script>
window.zscorePermissions = {
    create: @json(\Illuminate\Support\Facades\Gate::allows('create-zscore-setting')),
    edit: @json(\Illuminate\Support\Facades\Gate::allows('edit-zscore-setting')),
    delete: @json(\Illuminate\Support\Facades\Gate::allows('delete-zscore-setting')),
};
</script>
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('js/zscore-setting.js') }}?v={{ time() }}"></script>
@endpush