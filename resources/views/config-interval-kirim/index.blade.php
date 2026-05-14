@extends('layouts.template')

@section('page_title', 'Konfigurasi Interval Pengiriman')

@php
    $breadcrumb = (object) [
        'title' => 'Konfigurasi Interval Pengiriman',
        'list'  => ['Home', 'Sistem Pengaturan', 'Konfigurasi Interval Pengiriman'],
    ];
@endphp

@section('content')
<div class="container-fluid">

    {{-- Info banner --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Tentang konfigurasi ini:</strong>
                Nilai <code>min_interval_kirim_hari</code> menentukan jarak minimum (hari) antar pengiriman yang
                direkomendasikan sistem. Jika EOQ menghitung interval lebih pendek dari nilai ini,
                sistem akan menyesuaikannya ke nilai minimum ini.
                Nilai <strong>0</strong> berarti tidak ada batasan.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-10 col-12">

            {{-- Card konfigurasi global --}}
            <div class="card shadow-sm">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">
                        <i class="fas fa-sliders-h mr-2"></i>
                        Interval Minimum Pengiriman (Global)
                    </h3>
                </div>

                <div class="card-body">
                    {{-- Nilai saat ini --}}
                    <div class="text-center py-4 mb-4 bg-light rounded">
                        <div class="display-4 font-weight-bold text-primary" id="nilai-saat-ini">
                            {{ $konfigurasi->nilai ?? $defaultValue }}
                        </div>
                        <small class="text-muted">hari (nilai saat ini)</small>
                        @if(($konfigurasi->nilai ?? $defaultValue) == 0)
                            <div class="mt-2">
                                <span class="badge badge-secondary">Tidak ada batasan minimum</span>
                            </div>
                        @else
                            <div class="mt-2">
                                <span class="badge badge-primary">
                                    Pengiriman minimal setiap {{ $konfigurasi->nilai ?? $defaultValue }} hari
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Keterangan --}}
                    @if($konfigurasi->keterangan ?? null)
                    <div class="alert alert-light border mb-4">
                        <small class="text-muted">
                            <i class="fas fa-file-alt mr-1"></i>
                            <strong>Keterangan:</strong><br>
                            {{ $konfigurasi->keterangan }}
                        </small>
                    </div>
                    @endif

                    {{-- Riwayat perubahan --}}
                    @if($konfigurasi->updated_at ?? null)
                    <div class="text-muted small mb-3">
                        <i class="fas fa-clock mr-1"></i>
                        Terakhir diperbarui:
                        {{ \Carbon\Carbon::parse($konfigurasi->updated_at)->translatedFormat('d F Y, H:i') }}
                    </div>
                    @endif

                    @if($canUpdate)
                    <hr>

                    {{-- Form update --}}
                    <form
                        id="form-update-interval"
                        data-update-url="{{ route('config-interval-kirim.update') }}"
                        data-csrf="{{ csrf_token() }}"
                    >
                        @csrf
                        <div class="form-group">
                            <label for="input-nilai">
                                Nilai Baru (hari)
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control form-control-lg"
                                    id="input-nilai"
                                    name="nilai"
                                    min="0"
                                    max="365"
                                    value="{{ $konfigurasi->nilai ?? $defaultValue }}"
                                    placeholder="Masukkan jumlah hari (0 = tidak ada batasan)"
                                    required
                                >
                                <div class="input-group-append">
                                    <span class="input-group-text">hari</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Rentang: 0 – 365 hari. Nilai 0 = tidak ada batasan minimum.
                                Default sistem: {{ $defaultValue }} hari.
                            </small>
                        </div>

                        <div id="preview-badge" class="mb-3" style="display:none;">
                            <span class="badge badge-info px-3 py-2" id="preview-text"></span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block" id="btn-simpan">
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                    </form>
                    @else
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-lock mr-1"></i>
                        Anda tidak memiliki izin untuk mengubah konfigurasi ini.
                    </div>
                    @endif
                </div>
            </div>

            {{-- Card penjelasan logika --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-question-circle mr-2 text-info"></i>
                        Cara Kerja Interval Minimum
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Prioritas</th>
                                    <th>Sumber</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge badge-danger">1</span></td>
                                    <td>Override per-toko (<code>toko.min_interval_kirim_hari</code>)</td>
                                    <td>Jika toko memiliki nilai > 0, nilai ini digunakan.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-warning">2</span></td>
                                    <td>Konfigurasi global (halaman ini)</td>
                                    <td>Digunakan jika toko tidak punya override.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-secondary">3</span></td>
                                    <td>Fallback default sistem ({{ $defaultValue }} hari)</td>
                                    <td>Digunakan jika tabel konfigurasi kosong.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-light border mt-3 mb-0">
                        <small>
                            <i class="fas fa-shield-alt text-success mr-1"></i>
                            <strong>Keamanan produk selalu prioritas:</strong>
                            interval minimum tidak pernah melebihi batas aman shelf life produk.
                        </small>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('js')
<script src="{{ asset('js/config-interval-kirim.js') }}"></script>
@endpush
