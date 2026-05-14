<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password | ZafaSys</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/auth-forgot-password.css') }}">
  <link rel="stylesheet" href="{{ asset('css/mobile/auth-forgot-password-mobile.css') }}" media="(max-width: 860px)">
  <script src="{{ asset('js/auth-forgot-password.js') }}" defer></script>
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

        <div class="countdown-text" id="countdown-container" data-cooldown-until="{{ session('cooldown_until', 0) }}">
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
