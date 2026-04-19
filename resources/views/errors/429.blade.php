<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 - Terlalu Banyak Permintaan</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #e0eafc, #cfdef3);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-page {
            width: 100%;
            max-width: 800px;
        }

        .card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            border: none;
        }

        .error-icon i {
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .btn-lg {
            padding: 12px 40px;
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

        .countdown-box {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="card shadow-lg border-0">
            <div class="card-body text-center p-5">
                <div class="error-icon mb-4">
                    <i class="fas fa-stopwatch" style="font-size: 120px; color: #fc0808;"></i>
                </div>

                <h1 class="display-1 font-weight-bold" style="color: #fc0808;">429</h1>
                
                <h2 class="mb-4" style="color: #4A2511;">Terlalu Banyak Permintaan</h2>
                
                <div class="alert alert-danger mx-auto" style="max-width: 600px;" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <strong>Anda telah melebihi batas permintaan</strong>
                </div>

                <p class="lead text-muted mb-4">
                    Maaf, Anda telah melakukan terlalu banyak permintaan dalam waktu singkat.
                </p>

                <p class="text-muted mb-4">
                    Untuk menjaga kualitas layanan, kami membatasi jumlah permintaan per pengguna.
                    Silakan tunggu beberapa saat sebelum mencoba lagi.
                </p>

                <div class="countdown-box mx-auto mb-4" style="max-width: 400px;">
                    <div class="alert alert-info mb-0" role="alert">
                        <i class="fas fa-clock mr-2"></i>
                        <strong>Silakan tunggu sebentar...</strong>
                        <p class="mb-0 mt-2 small">Anda dapat mencoba lagi dalam beberapa menit</p>
                    </div>
                </div>

                <div class="mt-5">
                    <button onclick="window.location.reload()" class="btn btn-warning btn-lg text-white font-weight-bold">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Coba Lagi
                    </button>
                </div>

                <div class="mt-5 pt-4 border-top">
                    <p class="text-muted small mb-2">
                        <i class="fas fa-lightbulb mr-1"></i>
                        Tips untuk menghindari pembatasan:
                    </p>
                    <ul class="list-unstyled text-muted small">
                        <li><i class="fas fa-check text-success mr-2"></i>Kurangi frekuensi permintaan Anda</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Tunggu beberapa saat sebelum melakukan tindakan berikutnya</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Hubungi administrator jika Anda memerlukan akses lebih tinggi</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
