@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit Profile</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <h5><i class="icon fas fa-ban"></i> Error!</h5>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama_lengkap">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                           value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="{{ old('email', $user->email) }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="username">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="{{ old('username', $user->username) }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="telp">Nomor Telepon <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="telp" name="telp" 
                                           value="{{ old('telp', $user->telp) }}" required>
                                    <small class="text-muted">Minimal 10 digit</small>
                                </div>
                                <div class="form-group">
                                    <label for="jenis_kelamin">Jenis Kelamin</label>
                                    <select class="form-control" id="jenis_kelamin" name="jenis_kelamin">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="L" {{ old('jenis_kelamin', $user->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="P" {{ old('jenis_kelamin', $user->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="tempat_lahir">Tempat Lahir</label>
                                    <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" 
                                           value="{{ old('tempat_lahir', $user->tempat_lahir) }}">
                                </div>
                                <div class="form-group">
                                    <label for="tanggal_lahir">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" 
                                           value="{{ old('tanggal_lahir', $user->tanggal_lahir ? $user->tanggal_lahir->format('Y-m-d') : '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="alamat">Alamat <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="4" required>{{ old('alamat', $user->alamat) }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="foto">Foto Profil</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="foto" name="foto" accept="image/*">
                                            <label class="custom-file-label" for="foto">Pilih file</label>
                                        </div>
                                    </div>
                                    <small class="text-muted">Format: JPG, JPEG, PNG. Maks. 2MB</small>
                                </div>
                                <div class="form-group">
                                    <label>Preview Foto Profil</label>
                                    <div class="text-center mt-2">
                                        @if($user->foto)
                                            <img id="preview-image" src="{{ asset('storage/profile/'.$user->foto) }}" 
                                                 alt="Preview" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                        @else
                                            <img id="preview-image" src="{{ asset('adminlte/dist/img/user-default.jpg') }}" 
                                                 alt="Preview" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="{{ route('profile') }}" class="btn btn-secondary">Batal</a>
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
        // Preview foto sebelum upload
        $('#foto').change(function() {
            let reader = new FileReader();
            reader.onload = (e) => {
                $('#preview-image').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
            
            // Update label nama file
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass('selected').html(fileName);
        });
    });
</script>
@endpush