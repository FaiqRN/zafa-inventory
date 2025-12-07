@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle mr-2"></i>Tambah Stok Barang
                    </h3>
                </div>
                
                <form id="formTambahStok" action="{{ route('barang.store-tambah-stok', $barang->barang_id) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Kode Barang</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $barang->barang_kode }}" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Nama Barang</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $barang->nama_barang }}" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Satuan</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $barang->satuan }}" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Jumlah Stok <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="jumlah" name="jumlah" min="1" placeholder="Masukkan jumlah stok" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">{{ $barang->satuan }}</span>
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="error-jumlah"></div>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Tanggal Stok <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control" id="tanggal" name="tanggal" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                <small class="form-text text-muted">Tanggal tidak boleh melebihi hari ini</small>
                                <div class="invalid-feedback" id="error-tanggal"></div>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Catatan</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                                <div class="invalid-feedback" id="error-catatan"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-success" id="btnSimpan">
                                    <i class="fas fa-save mr-1"></i> Simpan
                                </button>
                                <a href="{{ route('barang.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times mr-1"></i> Batal
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    $('#formTambahStok').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        const submitBtn = $('#btnSimpan');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        window.location.href = '{{ route("barang.index") }}';
                    });
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        $('#' + key).addClass('is-invalid');
                        $('#error-' + key).text(value[0]);
                    });
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        text: 'Periksa kembali form Anda',
                        confirmButtonColor: '#dc3545'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data',
                        confirmButtonColor: '#dc3545'
                    });
                }
            }
        });
    });
});
</script>
@endpush
