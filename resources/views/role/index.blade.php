@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Alert Container -->
    <div id="alert-container"></div>

    <!-- Main Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                {{-- <i class="fas fa-user-tag"></i> Data Role --}} 
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" id="btnTambahRole">
                    <i class="fas fa-plus"></i> Tambah Role
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="table-role">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Nama Role</th>
                            <th width="15%">Jumlah User</th>
                            <th width="15%">Jumlah Permission</th>
                            <th width="20%">Dibuat Pada</th>
                            <th width="25%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="role-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Role -->
<div class="modal fade" id="modalTambahRole" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Role Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formTambahRole">
                <div class="modal-body">
                    @csrf
                    <div class="form-group">
                        <label for="name">Nama Role <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="Contoh: Manager, Supervisor, Staff">
                        <small class="text-muted">Nama role harus unik</small>
                        <div class="invalid-feedback" id="error-name"></div>
                    </div>

                    <div class="form-group">
                        <label>Permissions <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-2">Pilih menu dan aksi yang dapat diakses oleh role ini</small>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAll">
                                    <i class="fas fa-check-square"></i> Pilih Semua
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll">
                                    <i class="fas fa-square"></i> Hapus Semua
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            @foreach($permissions as $module => $perms)
                            @if($perms->count() > 0)
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <input type="checkbox" class="module-checkbox" data-module="{{ $module }}">
                                            <strong>{{ $module }}</strong>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($perms as $perm)
                                            <div class="col-md-6">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input permission-checkbox" 
                                                           id="perm_{{ $perm->name }}" 
                                                           name="permissions[]" 
                                                           value="{{ $perm->name }}"
                                                           data-module="{{ $module }}">
                                                    <label class="custom-control-label" for="perm_{{ $perm->name }}">
                                                        {{ ucfirst(str_replace('-', ' ', $perm->name)) }}
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        <div class="invalid-feedback d-block" id="error-permissions"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanRole">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Role -->
<div class="modal fade" id="modalEditRole" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditRole">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="edit_role_id" name="role_id">
                    
                    <div class="form-group">
                        <label for="edit_name">Nama Role <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback" id="error-edit_name"></div>
                    </div>

                    <div class="form-group">
                        <label>Permissions <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-2">Pilih menu dan aksi yang dapat diakses oleh role ini</small>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditSelectAll">
                                    <i class="fas fa-check-square"></i> Pilih Semua
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnEditDeselectAll">
                                    <i class="fas fa-square"></i> Hapus Semua
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            @foreach($permissions as $module => $perms)
                            @if($perms->count() > 0)
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <input type="checkbox" class="edit-module-checkbox" data-module="{{ $module }}">
                                            <strong>{{ $module }}</strong>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($perms as $perm)
                                            <div class="col-md-6">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input edit-permission-checkbox" 
                                                           id="edit_perm_{{ $perm->name }}" 
                                                           name="permissions[]" 
                                                           value="{{ $perm->name }}"
                                                           data-module="{{ $module }}">
                                                    <label class="custom-control-label" for="edit_perm_{{ $perm->name }}">
                                                        {{ ucfirst(str_replace('-', ' ', $perm->name)) }}
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        <div class="invalid-feedback d-block" id="error-edit_permissions"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnUpdateRole">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/role.js') }}?v={{ time() }}"></script>
@endpush
