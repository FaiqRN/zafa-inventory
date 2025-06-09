@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-fw fa-users"></i> Data Customer
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" id="btnTambah">
                            <i class="fas fa-plus"></i> Tambah Data
                        </button>
                        <button type="button" class="btn btn-success btn-sm" id="btnImport">
                            <i class="fas fa-file-import"></i> Import dari Excel/CSV
                        </button>
                        <button type="button" class="btn btn-info btn-sm" id="btnSyncPemesanan">
                            <i class="fas fa-sync"></i> Sinkronkan dari Pemesanan
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="btnDebugTables">
                            <i class="fas fa-bug"></i> Debug Tables
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="customerTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama</th>
                                <th>Gender</th>
                                <th>Usia</th>
                                <th>Alamat</th>
                                <th>Email</th>
                                <th>No. Telepon</th>
                                <th>Sumber Data</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Customer -->
<div class="modal fade" id="modalCustomer" tabindex="-1" role="dialog" aria-labelledby="modalCustomerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCustomerLabel">Tambah Data Customer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCustomer">
                <div class="modal-body">
                    <input type="hidden" id="customer_id" name="customer_id">
                    
                    <div class="form-group">
                        <label for="nama">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select class="form-control" id="gender" name="gender">
                                    <option value="">Pilih Gender</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="usia">Usia</label>
                                <input type="number" class="form-control" id="usia" name="usia" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="no_tlp">No. Telepon</label>
                                <input type="text" class="form-control" id="no_tlp" name="no_tlp">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="modalImport" tabindex="-1" role="dialog" aria-labelledby="modalImportLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImportLabel">Import Data Customer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formImport" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="file">File Excel/CSV</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="file" name="file" accept=".xlsx,.xls,.csv" required>
                            <label class="custom-file-label" for="file">Pilih file</label>
                        </div>
                        <small class="form-text text-muted">
                            Format kolom: Nama, Gender, Usia, Alamat, Email, No_Tlp
                        </small>
                    </div>
                    
                    <div class="alert alert-info">
                        <p class="mb-0"><i class="fas fa-info-circle"></i> Jika data dengan email atau nomor telepon yang sama sudah ada, maka data akan diperbarui.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanImport">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Debug -->
<div class="modal fade" id="modalDebug" tabindex="-1" role="dialog" aria-labelledby="modalDebugLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDebugLabel">Debug Database Tables</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Tabel Pemesanan</h5>
                            </div>
                            <div class="card-body">
                                <h6>Kolom Tabel:</h6>
                                <pre id="pemesananColumns" class="bg-light p-2"></pre>
                                
                                <h6 class="mt-3">Contoh Data:</h6>
                                <pre id="pemesananSamples" class="bg-light p-2"></pre>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Tabel Customer</h5>
                            </div>
                            <div class="card-body">
                                <h6>Kolom Tabel:</h6>
                                <pre id="customerColumns" class="bg-light p-2"></pre>
                                
                                <h6 class="mt-3">Contoh Data:</h6>
                                <pre id="customerSamples" class="bg-light p-2"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/customer.js') }}"></script>
@endpush