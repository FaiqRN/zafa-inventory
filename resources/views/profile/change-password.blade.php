@extends('layouts.template')

@section('page_title', 'Ubah Password')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Form Ubah Password</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form action="{{ route('profile.update-password') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                                {{ session('success') }}
                            </div>
                        @endif

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

                        <div class="form-group">
                            <label for="current_password">Password Saat Ini <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <div class="input-group-append">
                                    <span class="input-group-text toggle-password" data-target="current_password">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="password">Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="input-group-append">
                                    <span class="input-group-text toggle-password" data-target="password">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <small class="text-muted">Password minimal 8 karakter</small>
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                <div class="input-group-append">
                                    <span class="input-group-text toggle-password" data-target="password_confirmation">
                                        <i class="fas fa-eye"></i>
                                    </span>
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
        <div class="col-md-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Petunjuk Keamanan Password</h3>
                </div>
                <div class="card-body">
                    <p>Untuk meningkatkan keamanan akun Anda, ikuti petunjuk di bawah ini:</p>
                    <ul>
                        <li>Password minimal 8 karakter</li>
                        <li>Gunakan kombinasi huruf besar dan huruf kecil</li>
                        <li>Sertakan angka dan karakter khusus (!@#$%^&*)</li>
                        <li>Hindari menggunakan informasi pribadi (tanggal lahir, nama, dll)</li>
                        <li>Jangan gunakan password yang sama dengan akun lainnya</li>
                        <li>Ganti password secara berkala (setiap 3-6 bulan)</li>
                    </ul>
                    <div class="alert alert-warning">
                        <i class="icon fas fa-exclamation-triangle"></i> 
                        <strong>Perhatian!</strong> Setelah mengubah password, Anda akan diminta untuk login kembali.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        // Toggle password visibility
        $('.toggle-password').click(function() {
            const target = $(this).data('target');
            const input = $('#' + target);
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Password strength meter (optional, can be implemented later)
    });
</script>
@endpush