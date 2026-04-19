@extends('layouts.template')

@section('page_title', 'Setting EOQ')

@php
    $breadcrumb = (object) [
        'title' => 'Setting EOQ',
        'list' => ['Home', 'Sistem Pengaturan', 'Setting EOQ']
    ];
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        Biaya Pemesanan Global 
                    </h3>
                    <div class="card-tools">
                        @can('create-eoq-setting')
                        <button type="button" class="btn btn-success btn-sm" id="btn-add-global">
                            <i class="fas fa-plus mr-1"></i> Tambah Biaya
                        </button>
                        @endcan
                    </div>
                </div>
                
                <div class="card-body p-3">
                    <div class="search-wrapper mb-3">
                        <label class="search-label">Cari :</label>
                        <input type="text" class="form-control form-control-sm" 
                               id="searchBiayaGlobal" placeholder="Cari biaya...">
                    </div>
                    
                    <div class="table-wrapper" style="overflow-x: auto;">
                        <table class="table table-hover table-sm mb-0" style="min-width: 600px;">
                            <thead>
                                <tr>
                                    <th style="width: 40px;" class="text-center">No</th>
                                    <th style="min-width: 200px; width: 250px;">Nama Biaya</th>
                                    <th style="min-width: 150px; width: 180px;" class="text-right">Nominal (Rp)</th>
                                    <th style="min-width: 100px; width: 100px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-biaya-global">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Memuat data...
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="2" class="text-right">Total:</th>
                                    <th class="text-right text-primary" id="total-global">Rp 0</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 col-md-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        Override Biaya per Toko
                    </h3>
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
                    
                    <div id="toko-biaya-section" style="display: none;">
                        <div class="search-wrapper mb-3">
                            <label class="search-label">Cari :</label>
                            <input type="text" class="form-control form-control-sm" 
                                   id="searchBiayaToko" placeholder="Cari override...">
                        </div>
                        
                        <div class="table-wrapper" style="overflow-x: auto;">
                            <table class="table table-hover table-sm mb-0" style="min-width: 600px;">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th style="min-width: 150px; width: 200px;">Nama Biaya</th>
                                        <th style="min-width: 120px; width: 140px;" class="text-right">Global (Rp)</th>
                                        <th style="min-width: 120px; width: 140px;" class="text-right">Override (Rp)</th>
                                        <th style="min-width: 100px; width: 100px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-biaya-toko">
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="3" class="text-right">Total untuk Toko:</th>
                                        <th class="text-right text-warning" id="total-toko">Rp 0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <div id="toko-empty-state" class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-hand-pointer"></i>
                        </div>
                        <p class="empty-state-text">Pilih toko untuk melihat override biaya pemesanan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        Biaya Penyimpanan per Produk 
                    </h3>
                </div>
                
                <div class="card-body p-3">
                    <div class="form-group">
                        <label for="select-barang">Pilih Produk:</label>
                        <select class="form-control select2" id="select-barang" style="width: 100%;">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($barangs as $barang)
                                <option value="{{ $barang->barang_id }}">{{ $barang->barang_id }} - {{ $barang->nama_barang }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="barang-biaya-section" style="display: none;">
                        <div class="mb-3">
                            @can('create-eoq-setting')
                            <button type="button" class="btn btn-success btn-sm" id="btn-add-simpan">
                                <i class="fas fa-plus mr-1"></i> Tambah Komponen
                            </button>
                            @endcan
                        </div>
                        
                        <div class="search-wrapper mb-3">
                            <label class="search-label">Cari :</label>
                            <input type="text" class="form-control form-control-sm" 
                                   id="searchBiayaSimpan" placeholder="Cari komponen...">
                        </div>
                        
                        <div class="table-wrapper" style="overflow-x: auto;">
                            <table class="table table-hover table-sm mb-0" style="min-width: 700px;">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th style="min-width: 150px; width: 200px;">Nama Komponen</th>
                                        <th style="min-width: 120px; width: 140px;" class="text-right">Harga Pokok (Rp)</th>
                                        <th style="min-width: 100px; width: 120px;" class="text-right">Persentase (%)</th>
                                        <th style="min-width: 120px; width: 140px;" class="text-right">Biaya (Rp)</th>
                                        <th style="min-width: 100px; width: 100px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-biaya-simpan">
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="3" class="text-right">Total Persentase:</th>
                                        <th class="text-right text-success" id="total-persentase">0.00%</th>
                                        <th colspan="2"></th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-right">Biaya Penyimpanan/unit/tahun:</th>
                                        <th colspan="2" class="text-right text-success" id="total-biaya-simpan">Rp 0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <div id="barang-empty-state" class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-hand-pointer"></i>
                        </div>
                        <p class="empty-state-text">Pilih produk untuk melihat biaya penyimpanan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-biaya-global" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modal-global-title">Tambah Biaya Pemesanan Global</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-biaya-global">
                <div class="modal-body">
                    <input type="hidden" id="global-id" name="id">
                    <div class="form-group">
                        <label for="global-nama-biaya">Nama Biaya <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="global-nama-biaya" name="nama_biaya" required>
                        <small class="form-text text-muted">Contoh: Biaya transportasi, Biaya tenaga packing</small>
                    </div>
                    <div class="form-group">
                        <label for="global-nominal">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="global-nominal" name="nominal" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="global-keterangan">Keterangan</label>
                        <textarea class="form-control" id="global-keterangan" name="keterangan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    @if(\Illuminate\Support\Facades\Gate::allows('create-eoq-setting') || \Illuminate\Support\Facades\Gate::allows('edit-eoq-setting'))
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-biaya-toko" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Override Biaya Pemesanan Toko</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-biaya-toko">
                <div class="modal-body">
                    <input type="hidden" id="toko-id" name="toko_id">
                    <input type="hidden" id="toko-nama-biaya" name="nama_biaya">
                    
                    <div class="alert alert-info">
                        <strong>Nama Biaya:</strong> <span id="toko-nama-display"></span><br>
                        <strong>Nominal Global:</strong> Rp <span id="toko-nominal-global"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="toko-nominal">Nominal Override (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="toko-nominal" name="nominal" min="0" step="0.01" required>
                        <small class="form-text text-muted">Masukkan nominal khusus untuk toko ini</small>
                    </div>
                    <div class="form-group">
                        <label for="toko-keterangan">Keterangan</label>
                        <textarea class="form-control" id="toko-keterangan" name="keterangan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    @can('create-eoq-setting')
                    <button type="submit" class="btn btn-warning">Simpan Override</button>
                    @endcan
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-biaya-simpan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="modal-simpan-title">Tambah Komponen Biaya Penyimpanan</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-biaya-simpan">
                <div class="modal-body">
                    <input type="hidden" id="simpan-id" name="id">
                    <input type="hidden" id="simpan-barang-id" name="barang_id">
                    
                    <div class="form-group">
                        <label for="simpan-harga-pokok">Harga Pokok (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="simpan-harga-pokok" name="harga_pokok" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="simpan-nama-komponen">Nama Komponen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="simpan-nama-komponen" name="nama_komponen" required>
                        <small class="form-text text-muted">Contoh: Biaya modal tertahan, Risiko kerusakan/expired</small>
                    </div>
                    <div class="form-group">
                        <label for="simpan-persentase">Persentase (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="simpan-persentase" name="persentase" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="simpan-keterangan">Keterangan</label>
                        <textarea class="form-control" id="simpan-keterangan" name="keterangan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    @if(\Illuminate\Support\Facades\Gate::allows('create-eoq-setting') || \Illuminate\Support\Facades\Gate::allows('edit-eoq-setting'))
                    <button type="submit" class="btn btn-success">Simpan</button>
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
<link rel="stylesheet" href="{{ asset('css/barang.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/eoq-setting.css') }}?v={{ time() }}">
@endpush

@push('js')
<script>
window.eoqPermissions = {
    create: @json(\Illuminate\Support\Facades\Gate::allows('create-eoq-setting')),
    edit: @json(\Illuminate\Support\Facades\Gate::allows('edit-eoq-setting')),
    delete: @json(\Illuminate\Support\Facades\Gate::allows('delete-eoq-setting')),
};
</script>
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('js/eoq-setting.js') }}?v={{ time() }}"></script>
@endpush
