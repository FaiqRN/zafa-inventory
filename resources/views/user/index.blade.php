@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Alert Container -->
    <div id="alert-container"></div>

    <!-- Main Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                {{-- <i class="fas fa-users"></i> Data User --}}
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" id="btnTambahUser">
                    <i class="fas fa-plus"></i> Tambah User
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="table-user">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Username</th>
                            <th width="18%">Nama Lengkap</th>
                            <th width="18%">Email</th>
                            <th width="12%">No. Telp</th>
                            <th width="10%">Role</th>
                            <th width="10%">Jenis Kelamin</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="user-body">
                        <!-- Data akan dimuat via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah User Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formTambahUser">
                <div class="modal-body">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role_id">Role <span class="text-danger">*</span></label>
                                <select class="form-control" id="role_id" name="role_id" required>
                                    <option value="">-- Pilih Role --</option>
                                    @foreach($rolesForCreate as $role)
                                        <option value="{{ $role->role_id }}">{{ $role->nama_role }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Semua role kecuali Admin dapat dipilih. Buat role baru di Manajemen Role.</small>
                                <div class="invalid-feedback" id="error-role_id"></div>
                            </div>
                            <div class="form-group">
                                <label for="username">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required autocomplete="off">
                                <div class="invalid-feedback" id="error-username"></div>
                            </div>
                            <div class="form-group">
                                <label for="firstname">Nama Depan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="firstname" name="firstname" required>
                                <div class="invalid-feedback" id="error-firstname"></div>
                            </div>
                            <div class="form-group">
                                <label for="lastname">Nama Belakang</label>
                                <input type="text" class="form-control" id="lastname" name="lastname">
                                <div class="invalid-feedback" id="error-lastname"></div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback" id="error-email"></div>
                            </div>
                            <div class="form-group">
                                <label for="password">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required autocomplete="off">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Minimal 8 karakter</small>
                                <div class="invalid-feedback" id="error-password"></div>
                            </div>
                            <div class="form-group">
                                <label for="password_confirmation">Konfirmasi Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="off">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password_confirmation">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="error-password_confirmation"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telp">No. Telepon <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="telp" name="telp" required>
                                <small class="text-muted">Minimal 10 digit</small>
                                <div class="invalid-feedback" id="error-telp"></div>
                            </div>
                            <div class="form-group">
                                <label for="alamat">Alamat <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                                <div class="invalid-feedback" id="error-alamat"></div>
                            </div>
                            <div class="form-group">
                                <label for="jenis_kelamin">Jenis Kelamin</label>
                                <select class="form-control" id="jenis_kelamin" name="jenis_kelamin">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                <div class="invalid-feedback" id="error-jenis_kelamin"></div>
                            </div>
                            <div class="form-group">
                                <label for="tempat_lahir">Tempat Lahir</label>
                                <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir">
                                <div class="invalid-feedback" id="error-tempat_lahir"></div>
                            </div>
                            <div class="form-group">
                                <label for="tanggal_lahir">Tanggal Lahir</label>
                                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir">
                                <div class="invalid-feedback" id="error-tanggal_lahir"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanUser">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="modalEditUser" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditUser">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_role_id">Role <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_role_id" name="role_id" required>
                                    <option value="">-- Pilih Role --</option>
                                    @foreach($rolesForEdit as $role)
                                        <option value="{{ $role->role_id }}">{{ $role->nama_role }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-edit_role_id"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_username">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_username" name="username" required autocomplete="off">
                                <div class="invalid-feedback" id="error-edit_username"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_firstname">Nama Depan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_firstname" name="firstname" required>
                                <div class="invalid-feedback" id="error-edit_firstname"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_lastname">Nama Belakang</label>
                                <input type="text" class="form-control" id="edit_lastname" name="lastname">
                                <div class="invalid-feedback" id="error-edit_lastname"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                                <div class="invalid-feedback" id="error-edit_email"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_password">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="edit_password" name="password" autocomplete="off">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#edit_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                <div class="invalid-feedback" id="error-edit_password"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_password_confirmation">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="edit_password_confirmation" name="password_confirmation" autocomplete="off">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#edit_password_confirmation">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="error-edit_password_confirmation"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_telp">No. Telepon <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_telp" name="telp" required>
                                <div class="invalid-feedback" id="error-edit_telp"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_alamat">Alamat <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="edit_alamat" name="alamat" rows="3" required></textarea>
                                <div class="invalid-feedback" id="error-edit_alamat"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_jenis_kelamin">Jenis Kelamin</label>
                                <select class="form-control" id="edit_jenis_kelamin" name="jenis_kelamin">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                <div class="invalid-feedback" id="error-edit_jenis_kelamin"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_tempat_lahir">Tempat Lahir</label>
                                <input type="text" class="form-control" id="edit_tempat_lahir" name="tempat_lahir">
                                <div class="invalid-feedback" id="error-edit_tempat_lahir"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_tanggal_lahir">Tanggal Lahir</label>
                                <input type="date" class="form-control" id="edit_tanggal_lahir" name="tanggal_lahir">
                                <div class="invalid-feedback" id="error-edit_tanggal_lahir"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnUpdateUser">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/user.js') }}?v={{ time() }}"></script>
@endpush
