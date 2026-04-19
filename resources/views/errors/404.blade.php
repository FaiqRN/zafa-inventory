@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="error-page" style="margin-top: 50px;">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <div class="error-icon mb-4">
                            <i class="fas fa-search" style="font-size: 120px; color: #ffc107;"></i>
                        </div>

                        <h1 class="display-1 font-weight-bold" style="color: #ffc107;">404</h1>
                        
                        <h2 class="mb-4" style="color: #4A2511;">Halaman Tidak Ditemukan</h2>
                        
                        <div class="alert alert-warning mx-auto" style="max-width: 600px;" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Oops! Halaman yang Anda cari tidak ada</strong>
                        </div>

                        <p class="lead text-muted mb-4">
                            Halaman yang Anda cari mungkin telah dipindahkan, dihapus, atau tidak pernah ada.
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
                            <p class="text-muted small">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Saran: Periksa URL atau gunakan menu navigasi untuk menemukan halaman yang Anda butuhkan.
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
        animation: bounce 1s infinite;
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-20px);
        }
        60% {
            transform: translateY(-10px);
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
