<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login | ZafaSys</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/auth-login.css') }}">
  <link rel="stylesheet" href="{{ asset('css/mobile/auth-login-mobile.css') }}" media="(max-width: 860px)">
  <script src="{{ asset('js/auth-login.js') }}" defer></script>
</head>
<body>

@php
  $showForgot = old('email') || session('email_sent') || session('sent_email') || session('cooldown_until');
  $showSplashError = !$showForgot && $errors->any();
@endphp

<!-- Splash Screen -->
<div id="splash-screen" class="welcome" style="display: {{ $showSplashError ? 'flex' : 'none' }};" data-has-errors="{{ $showSplashError ? '1' : '0' }}" data-error-message="{{ $showSplashError ? ($errors->first('username') ?? $errors->first('password') ?? 'Username atau password tidak valid') : '' }}">

  <!-- Rejected Content -->
  <div id="splash-content-rejected" style="display: none;">
    <div class="splash-icon">🙅‍♀️</div>
    <h1>Oops!</h1>
    <p id="error-detail">Username atau password tidak valid</p>
  </div>
</div>

<!-- Auth Container -->
<div class="container" id="authContainer" data-start-mode="{{ $showForgot ? 'forgot' : 'login' }}">
  <div class="left">
    <div class="panel-content panel-login">
      <img src="{{ asset('adminlte/dist/img/Zlogo.png') }}" alt="Logo">
      <h2>Hello, Welcome!</h2>
      <p>Please login to continue</p>
    </div>
    <div class="panel-content panel-forgot">
      <img src="{{ asset('adminlte/dist/img/Zlogo.png') }}" alt="Logo">
      <h2>Reset Password</h2>
      <p>Masukkan username dan email untuk menerima link reset password Anda.</p>
    </div>
  </div>

  <div class="right">
    <div class="forms">
      <section class="form-panel form-forgot">
        <div class="form-card">
          @if(session('email_sent'))
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
                <button class="btn-submit" type="submit">Kirim Ulang Email</button>
              </form>
            </div>
          @else
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

              <div class="form-group">
                <label for="forgot-username">Username</label>
                <div class="input-wrapper">
                  <span class="input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                      <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                  </span>
                  <input
                    type="text"
                    name="username"
                    id="forgot-username"
                    placeholder="Masukkan username Anda"
                    value="{{ old('username') }}"
                    required
                    autocomplete="off"
                    maxlength="50">
                </div>
                @error('username')
                  <span class="error-message">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="forgot-email">Email</label>
                <div class="input-wrapper">
                  <span class="input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                      <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                  </span>
                  <input
                    type="email"
                    name="email"
                    id="forgot-email"
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
            </form>
          @endif

          <a href="{{ route('login') }}" class="back-link js-back-login">Back to Login</a>
        </div>
      </section>

      <section class="form-panel form-login">
        <div class="form-card">
          <h2>Login</h2>

          <!-- Flash Messages -->
          @if(session('message'))
            <div class="flash-message {{ session('class', 'info') }}">
              {{ session('message') }}
            </div>
          @endif

          <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
              <label for="login-username">Username</label>
              <div class="input-wrapper">
                <span class="input-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                  </svg>
                </span>
                <input
                  type="text"
                  name="username"
                  id="login-username"
                  placeholder="Enter your username"
                  value="{{ old('username') }}"
                  autocomplete="off">
              </div>
              @error('username')
                <span class="error-message">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <label for="password">Password</label>
              <div class="input-wrapper">
                <span class="input-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                  </svg>
                </span>
                <input
                  type="password"
                  name="password"
                  id="password"
                  placeholder="Enter your password"
                  autocomplete="off">

                <span class="toggle-password" id="togglePassword" title="Show password">
                  <svg id="eyeHide" class="hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                  </svg>
                  <svg id="eyeShow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                  </svg>
                </span>
              </div>
              @error('password')
                <span class="error-message">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-footer">
              <a href="{{ route('password.request') }}" class="forgot-link js-forgot-link">Forgot Password?</a>
            </div>

            <button class="btn-login" type="submit">Login</button>
          </form>
        </div>
      </section>
    </div>
  </div>
</div>

</body>
</html>