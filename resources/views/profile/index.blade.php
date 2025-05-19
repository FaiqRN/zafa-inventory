@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <!-- Profile Image -->
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        @if($user->foto)
                            <img class="profile-user-img img-fluid img-circle" 
                                 src="{{ asset('storage/profile/'.$user->foto) }}" 
                                 alt="User profile picture" style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <img class="profile-user-img img-fluid img-circle" 
                                 src="{{ asset('adminlte/dist/img/user-default.jpg') }}" 
                                 alt="Default profile picture" style="width: 150px; height: 150px; object-fit: cover;">
                        @endif
                    </div>

                    <h3 class="profile-username text-center">{{ $user->nama_lengkap }}</h3>
                    <p class="text-muted text-center">Admin Zafa Potato</p>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Profile</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <th style="width:30%">Nama Lengkap</th>
                                <td>{{ $user->nama_lengkap }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <th>Username</th>
                                <td>{{ $user->username }}</td>
                            </tr>
                            <tr>
                                <th>No. Telepon</th>
                                <td>{{ $user->telp ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Jenis Kelamin</th>
                                <td>{{ $user->jenis_kelamin == 'L' ? 'Laki-laki' : ($user->jenis_kelamin == 'P' ? 'Perempuan' : '-') }}</td>
                            </tr>
                            <tr>
                                <th>Tempat, Tanggal Lahir</th>
                                <td>
                                    @if($user->tempat_lahir || $user->tanggal_lahir)
                                        {{ $user->tempat_lahir ?? '' }}{{ ($user->tempat_lahir && $user->tanggal_lahir) ? ', ' : '' }}
                                        {{ $user->tanggal_lahir ? $user->tanggal_lahir->format('d-m-Y') : '' }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td>{{ $user->alamat ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection