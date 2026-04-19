<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ZafaSys</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #fece0b;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 28px;
        }
        .header .logo {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 20px 0;
        }
        .content p {
            margin-bottom: 15px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .reset-button {
            display: inline-block;
            background-color: #fece0b;
            color: #333;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .reset-button:hover {
            background-color: #ffd43b;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
        .link-text {
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">🔐</div>
            <h1>Reset Password</h1>
        </div>
        
        <div class="content">
            <p>Halo <strong>{{ $user->firstname ?? 'Pengguna' }}</strong>,</p>
            
            <p>Kami menerima permintaan untuk mereset password akun ZafaSys Anda. Klik tombol di bawah ini untuk membuat password baru:</p>
            
            <div class="button-container">
                <a href="{{ route('password.reset', ['token' => $token, 'email' => $email]) }}" class="reset-button">
                    Reset Password
                </a>
            </div>
            
            <div class="warning">
                <strong>⚠️ Penting:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Link ini hanya berlaku selama <strong>60 menit</strong>.</li>
                    <li>Jika Anda tidak meminta reset password, abaikan email ini.</li>
                    <li>Jangan bagikan link ini kepada siapapun.</li>
                </ul>
            </div>
            
            <p>Jika tombol di atas tidak berfungsi, salin dan tempel link berikut ke browser Anda:</p>
            <div class="link-text">
                {{ route('password.reset', ['token' => $token, 'email' => $email]) }}
            </div>
        </div>
        
        <div class="footer">
            <p>Email ini dikirim secara otomatis oleh sistem ZafaSys.</p>
            <p>© {{ date('Y') }} ZafaSys. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
