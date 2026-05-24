<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password | ZafaSys</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/auth-reset-password.css') }}">
  <link rel="stylesheet" href="{{ asset('css/mobile/auth-reset-password-mobile.css') }}" media="(max-width: 860px)">
  <script src="{{ asset('js/auth-reset-password.js') }}" defer></script>
</head>
<body>

<div class="container" id="resetContainer">
  <div class="left">
    <div class="panel-content">
      <img src="{{ asset('adminlte/dist/img/Zlogo.png') }}" alt="Logo">
      <h2>Password Baru</h2>
      <p>Buat password baru yang kuat untuk mengamankan akun Anda. Pastikan password mudah diingat tapi sulit ditebak.</p>
    </div>
  </div>
  <div class="right">
    <div class="form-card">
      <h2>Reset Password</h2>
      <p class="subtitle">Buat password baru Anda</p>

    <!-- Flash Messages -->
    @if(session('message'))
      <div class="flash-message {{ session('class', 'info') }}">
        {{ session('message') }}
      </div>
    @endif

      <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="username" value="{{ $username }}">

        <!-- Password Field -->
        <div class="form-group">
          <label for="password">Password Baru</label>
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
              placeholder="Masukkan password baru"
              required
              autocomplete="off">
            <span class="toggle-password" id="togglePassword1" title="Tampilkan password">
              <svg id="eyeShow1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
              </svg>
              <svg id="eyeHide1" class="hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
              </svg>
            </span>
          </div>
          @error('password')
            <span class="error-message">{{ $message }}</span>
          @enderror
        </div>

        <!-- Confirm Password Field -->
        <div class="form-group">
          <label for="password_confirmation">Konfirmasi Password</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
              </svg>
            </span>
            <input
              type="password"
              name="password_confirmation"
              id="password_confirmation"
              placeholder="Ulangi password baru"
              required
              autocomplete="off">
            <span class="toggle-password" id="togglePassword2" title="Tampilkan password">
              <svg id="eyeShow2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
              </svg>
              <svg id="eyeHide2" class="hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
              </svg>
            </span>
          </div>
        </div>

        <div class="password-requirements">
          <strong>Persyaratan Password:</strong>
          <ul>
            <li>Minimal 8 karakter</li>
            <li>Disarankan kombinasi huruf, angka, dan simbol</li>
          </ul>
        </div>

        <button class="btn-submit" type="submit">Simpan Password Baru</button>

        <a href="{{ route('login') }}" class="back-link js-back-login">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
          </svg>
          Kembali ke Login
        </a>

      </form>
    </div>
  </div>
</div>

</body>
</html>
