<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password | ZafaSys</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(to right, #e0eafc, #cfdef3);
    }

    .container {
      display: flex;
      width: 900px;
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .left {
      background: #fece0b;
      color: white;
      flex: 1;
      padding: 60px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      border-top-right-radius: 160px;
      border-bottom-right-radius: 160px;
    }

    .left img {
      width: 150px;
      height: 150px;
      margin-bottom: 30px;
      animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    .left h2 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .left p {
      font-size: 14px;
      line-height: 1.6;
    }

    .right {
      flex: 1;
      padding: 40px;
    }

    .right h2 {
      text-align: center;
      margin-bottom: 15px;
      font-size: 28px;
      font-weight: 600;
      color: #333;
    }

    .right .subtitle {
      text-align: center;
      color: #666;
      font-size: 14px;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 25px;
    }

    .form-group label {
      display: block;
      font-weight: 500;
      margin-bottom: 8px;
      color: #333;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-wrapper .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
      pointer-events: none;
      z-index: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 20px;
      height: 20px;
    }

    .input-wrapper .input-icon svg {
      width: 20px;
      height: 20px;
      fill: #999;
    }

    .input-wrapper input {
      width: 100%;
      padding: 12px 15px 12px 40px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s ease;
    }

    .input-wrapper input:focus {
      outline: none;
      border-color: #fece0b;
    }

    .error-message {
      color: #e74c3c;
      font-size: 13px;
      margin-top: 5px;
      display: block;
    }

    .flash-message {
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      text-align: center;
    }

    .flash-message.success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .flash-message.info {
      background-color: #d1ecf1;
      color: #0c5460;
      border: 1px solid #bee5eb;
    }

    .flash-message.error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .flash-message.warning {
      background-color: #fff3cd;
      color: #856404;
      border: 1px solid #ffc107;
    }

    .btn-submit {
      width: 100%;
      padding: 12px;
      background: #fece0b;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
      font-size: 16px;
      margin-bottom: 15px;
    }

    .btn-submit:hover {
      background: #ffd43b;
    }

    .btn-submit:disabled {
      background: #ccc;
      cursor: not-allowed;
    }

    .back-link {
      display: block;
      text-align: center;
      color: #666;
      text-decoration: none;
      font-size: 14px;
      transition: color 0.3s ease;
    }

    .back-link:hover {
      color: #fece0b;
    }

    .back-link svg {
      width: 16px;
      height: 16px;
      vertical-align: middle;
      margin-right: 5px;
      fill: currentColor;
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
        width: 90%;
      }

      .left, .right {
        padding: 30px 20px;
      }
    }

    /* Success State Styles */
    .success-state {
      text-align: center;
      padding: 20px 0;
    }

    .success-icon {
      font-size: 64px;
      margin-bottom: 20px;
    }

    .success-state h3 {
      color: #155724;
      margin-bottom: 15px;
      font-size: 20px;
    }

    .success-state p {
      color: #666;
      font-size: 14px;
      margin-bottom: 10px;
    }

    .email-highlight {
      background-color: #f8f9fa;
      padding: 8px 15px;
      border-radius: 5px;
      font-weight: 600;
      color: #333;
      display: inline-block;
      margin: 10px 0;
    }

    .countdown-text {
      color: #856404;
      font-size: 13px;
      margin: 15px 0;
      padding: 10px;
      background-color: #fff3cd;
      border-radius: 8px;
    }

    .countdown-timer {
      font-weight: bold;
      color: #e74c3c;
    }

    .tips-box {
      background-color: #e8f4fd;
      border: 1px solid #bee5eb;
      border-radius: 8px;
      padding: 15px;
      margin: 20px 0;
      text-align: left;
      font-size: 13px;
    }

    .tips-box strong {
      color: #0c5460;
    }

    .tips-box ul {
      margin: 8px 0 0 15px;
      padding: 0;
      color: #0c5460;
    }

    .tips-box li {
      margin-bottom: 5px;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="left">
    <img src="{{ asset('adminlte/dist/img/Zlogo.png') }}" alt="Logo">
    @if(session('email_sent'))
      <h2>Email Terkirim!</h2>
      <p>Kami telah mengirimkan link reset password ke email Anda. Cek inbox atau folder spam.</p>
    @else
      <h2>Lupa Password?</h2>
      <p>Jangan khawatir! Masukkan username dan email yang terdaftar dan kami akan mengirimkan link untuk reset password Anda.</p>
    @endif
  </div>
  <div class="right">
    @if(session('email_sent'))
      {{-- SUCCESS STATE - Email sudah dikirim --}}
      <div class="success-state">
        <div class="success-icon">✉️</div>
        <h3>Link Reset Password Terkirim!</h3>
        
        <p>Kami telah mengirim email ke:</p>
        <div class="email-highlight">{{ session('sent_email') }}</div>
        
        <div class="tips-box">
          <strong>Tips:</strong>
          <ul>
            <li>Cek folder <strong>Inbox</strong> email Anda</li>
            <li>Jika tidak ada, cek folder <strong>Spam/Junk</strong></li>
            <li>Link berlaku selama <strong>60 menit</strong></li>
          </ul>
        </div>

        <div class="countdown-text" id="countdown-container">
          Belum menerima email? Anda dapat mengirim ulang dalam 
          <span class="countdown-timer" id="countdown">05:00</span>
        </div>

        <form method="POST" action="{{ route('password.email') }}" id="resend-form" style="display: none;">
          @csrf
          <input type="hidden" name="username" value="{{ old('username') }}">
          <input type="hidden" name="email" value="{{ session('sent_email') }}">
          <button class="btn-submit" type="submit">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: white; vertical-align: middle; margin-right: 5px;">
              <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
            </svg>
            Kirim Ulang Email
          </button>
        </form>

        <a href="{{ route('login') }}" class="back-link" style="margin-top: 20px;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
          </svg>
          Kembali ke Login
        </a>
      </div>

      <script>
        // Countdown timer
        const cooldownUntil = {{ session('cooldown_until', 0) }};
        const countdownEl = document.getElementById('countdown');
        const countdownContainer = document.getElementById('countdown-container');
        const resendForm = document.getElementById('resend-form');

        function updateCountdown() {
          const now = Math.floor(Date.now() / 1000);
          const remaining = cooldownUntil - now;

          if (remaining <= 0) {
            countdownContainer.style.display = 'none';
            resendForm.style.display = 'block';
            return;
          }

          const minutes = Math.floor(remaining / 60);
          const seconds = remaining % 60;
          countdownEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
          
          setTimeout(updateCountdown, 1000);
        }

        updateCountdown();
      </script>

    @else
      {{-- FORM STATE - Belum kirim email --}}
      <h2>Reset Password</h2>
      <p class="subtitle">Masukkan username dan email akun Anda</p>

      <!-- Flash Messages -->
      @if(session('message'))
        <div class="flash-message {{ session('class', 'info') }}">
          {{ session('message') }}
        </div>
      @endif

      <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Username Field -->
        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </span>
            <input 
              type="text" 
              name="username" 
              id="username" 
              placeholder="Masukkan username Anda" 
              value="{{ old('username') }}"
              required
              autocomplete="username"
              maxlength="50">
          </div>
          @error('username')
            <span class="error-message">{{ $message }}</span>
          @enderror
        </div>

        <!-- Email Field -->
        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
              </svg>
            </span>
            <input 
              type="email" 
              name="email" 
              id="email" 
              placeholder="Masukkan email Anda" 
              value="{{ old('email') }}"
              required
              autocomplete="email">
          </div>
          @error('email')
            <span class="error-message">{{ $message }}</span>
          @enderror
        </div>

        <button class="btn-submit" type="submit">Kirim Link Reset</button>

        <a href="{{ route('login') }}" class="back-link">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
          </svg>
          Kembali ke Login
        </a>

      </form>
    @endif
  </div>
</div>

</body>
</html>
