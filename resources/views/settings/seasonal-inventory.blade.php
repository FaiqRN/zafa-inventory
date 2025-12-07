@extends('layouts.template')

@section('page_title', 'Pengaturan Seasonal Inventory')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('user.index') }}">Sistem Pengaturan</a></li>
<li class="breadcrumb-item active">Pengaturan Seasonal Inventory</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary">
            <h3 class="card-title text-white">
                <i class="fas fa-calendar-alt mr-2"></i>
                Pengaturan Seasonal Inventory
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-warning" id="btn-reset">
                    <i class="fas fa-undo mr-1"></i>Reset ke Default
                </button>
                <button type="button" class="btn btn-sm btn-success" id="btn-save">
                    <i class="fas fa-save mr-1"></i>Simpan Perubahan
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Informasi:</strong> Pengaturan ini mengontrol forecast dan perhitungan seasonal inventory.
                Perubahan akan langsung berlaku setelah disimpan dan cache dibersihkan.
            </div>

            <form id="settings-form">
                @csrf

                @foreach($settings as $category => $categorySettings)
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-{{ getCategoryIcon($category) }} mr-2"></i>
                            {{ getCategoryLabel($category) }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($categorySettings as $setting)
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="setting_{{ $setting->key }}">
                                        <strong>{{ $setting->label }}</strong>
                                        @if($setting->unit)
                                        <span class="badge badge-secondary">{{ $setting->unit }}</span>
                                        @endif
                                    </label>

                                    @if($setting->type == 'number' || $setting->type == 'percentage')
                                    <input
                                        type="number"
                                        class="form-control setting-input"
                                        id="setting_{{ $setting->key }}"
                                        name="settings[{{ $setting->key }}]"
                                        data-key="{{ $setting->key }}"
                                        value="{{ $setting->value }}"
                                        min="{{ $setting->min_value }}"
                                        max="{{ $setting->max_value }}"
                                        step="{{ $setting->type == 'percentage' ? '1' : 'any' }}"
                                        required
                                    >
                                    @else
                                    <input
                                        type="text"
                                        class="form-control setting-input"
                                        id="setting_{{ $setting->key }}"
                                        name="settings[{{ $setting->key }}]"
                                        data-key="{{ $setting->key }}"
                                        value="{{ $setting->value }}"
                                        required
                                    >
                                    @endif

                                    @if($setting->description)
                                    <small class="form-text text-muted">
                                        {{ $setting->description }}
                                    </small>
                                    @endif

                                    @if($setting->min_value !== null || $setting->max_value !== null)
                                    <small class="form-text text-info">
                                        <i class="fas fa-info-circle"></i>
                                        Range: {{ $setting->min_value ?? 'N/A' }} - {{ $setting->max_value ?? 'N/A' }}
                                    </small>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach

            </form>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-clock mr-1"></i>
                        Terakhir diupdate: <span id="last-updated">-</span>
                    </small>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('user.index') }}'">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali
                    </button>
                    <button type="button" class="btn btn-success" id="btn-save-footer">
                        <i class="fas fa-save mr-1"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
.setting-input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.card-header h5 {
    color: #495057;
    font-weight: 600;
}

.form-group label {
    color: #495057;
}

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    // Save settings
    $('#btn-save, #btn-save-footer').on('click', function() {
        saveSettings();
    });

    // Reset settings
    $('#btn-reset').on('click', function() {
        Swal.fire({
            title: 'Reset ke Default?',
            text: 'Semua pengaturan akan dikembalikan ke nilai default',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                resetSettings();
            }
        });
    });

    // Track changes
    $('.setting-input').on('change', function() {
        $(this).addClass('border-warning');
    });
});

function saveSettings() {
    const settings = [];

    $('.setting-input').each(function() {
        settings.push({
            key: $(this).data('key'),
            value: $(this).val()
        });
    });

    $.ajax({
        url: '{{ route("seasonal-inventory-settings.update") }}',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            settings: settings
        },
        beforeSend: function() {
            Swal.fire({
                title: 'Menyimpan...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        success: function(response) {
            if (response.success) {
                $('.setting-input').removeClass('border-warning');

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                $('#last-updated').text(new Date().toLocaleString('id-ID'));
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: response.message,
                    html: response.errors ? '<ul class="text-left">' +
                          response.errors.map(e => '<li>' + e + '</li>').join('') +
                          '</ul>' : response.message
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi kesalahan saat menyimpan pengaturan'
            });
        }
    });
}

function resetSettings() {
    $.ajax({
        url: '{{ route("seasonal-inventory-settings.reset") }}',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            Swal.fire({
                title: 'Mereset...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
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
                text: 'Terjadi kesalahan saat mereset pengaturan'
            });
        }
    });
}
</script>
@endpush

@php
function getCategoryIcon($category) {
    $icons = [
        'forecast' => 'chart-line',
        'safety_stock' => 'shield-alt',
        'seasonal' => 'calendar-alt',
        'system' => 'server'
    ];
    return $icons[$category] ?? 'cog';
}

function getCategoryLabel($category) {
    $labels = [
        'forecast' => 'Pengaturan Forecast',
        'safety_stock' => 'Pengaturan Safety Stock',
        'seasonal' => 'Pengaturan Seasonal',
        'system' => 'Pengaturan Sistem'
    ];
    return $labels[$category] ?? ucfirst($category);
}
@endphp
