@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="error-page" style="margin-top: 50px;">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <div class="error-icon mb-4">
                            <i class="fas fa-tools" style="font-size: 120px; color: #6c757d;"></i>
                        </div>

                        <h1 class="display-1 font-weight-bold" style="color: #6c757d;">500</h1>
                        
                        <h2 class="mb-4" style="color: #4A2511;">Terjadi Kesalahan Server</h2>
                        
                        <div class="alert alert-secondary mx-auto" style="max-width: 600px;" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Maaf, terjadi kesalahan pada server</strong>
                        </div>

                        <p class="lead text-muted mb-4">
                            Kami sedang mengalami masalah teknis. Tim kami telah diberitahu dan sedang memperbaikinya.
                        </p>

                        <div class="mt-5">
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg mr-2">
                                <i class="fas fa-home mr-2"></i>
                                Kembali ke Dashboard
                            </a>
                            <button onclick="window.location.reload()" class="btn btn-secondary btn-lg">
                                <i class="fas fa-sync-alt mr-2"></i>
                                Muat Ulang Halaman
                            </button>
                        </div>

                        <div class="mt-5 pt-4 border-top">
                            <p class="text-muted small">
                                <i class="fas fa-info-circle mr-1"></i>
                                Jika masalah berlanjut, silakan hubungi administrator sistem.
                            </p>
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
        animation: rotate 2s linear infinite;
    }

    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
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
