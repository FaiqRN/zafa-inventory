@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="error-page" style="margin-top: 50px;">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <div class="error-icon mb-4">
                            <i class="fas fa-ban" style="font-size: 120px; color: #dc3545;"></i>
                        </div>

                        <h1 class="display-1 font-weight-bold" style="color: #dc3545;">403</h1>
                        
                        <h2 class="mb-4" style="color: #4A2511;">Akses Ditolak</h2>
                        
                        <div class="alert alert-danger mx-auto" style="max-width: 600px;" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Anda Tidak Memiliki Izin</strong>
                        </div>

                        <p class="lead text-muted mb-4">
                            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
                        </p>

                        <p class="text-muted mb-4">
                            @if(isset($exception) && $exception->getMessage())
                                {{ $exception->getMessage() }}
                            @else
                                Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.
                            @endif
                        </p>

                        <div class="mt-5">
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg mr-2">
                                <i class="fas fa-home mr-2"></i>
                                Kembali ke Dashboard
                            </a>
                            <button onclick="window.history.back()" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Halaman Sebelumnya
                            </button>
                        </div>

                        <div class="mt-5 pt-4 border-top">
                            <p class="text-muted small mb-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Jika Anda memerlukan akses ke halaman ini:
                            </p>
                            <ul class="list-unstyled text-muted small">
                                <li><i class="fas fa-check text-success mr-2"></i>Hubungi administrator sistem</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .error-page {
        min-height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .error-icon i {
        animation: shake 0.5s;
        animation-iteration-count: 1;
    }

    @keyframes shake {
        0% { transform: translate(1px, 1px) rotate(0deg); }
        10% { transform: translate(-1px, -2px) rotate(-1deg); }
        20% { transform: translate(-3px, 0px) rotate(1deg); }
        30% { transform: translate(3px, 2px) rotate(0deg); }
        40% { transform: translate(1px, -1px) rotate(1deg); }
        50% { transform: translate(-1px, 2px) rotate(-1deg); }
        60% { transform: translate(-3px, 1px) rotate(0deg); }
        70% { transform: translate(3px, 1px) rotate(-1deg); }
        80% { transform: translate(-1px, -1px) rotate(1deg); }
        90% { transform: translate(1px, 2px) rotate(0deg); }
        100% { transform: translate(1px, -2px) rotate(-1deg); }
    }

    .card {
        border-radius: 15px;
        overflow: hidden;
    }

    .btn-lg {
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .btn-lg:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .alert {
        border-radius: 10px;
        border: none;
        font-size: 16px;
    }
</style>
@endsection
