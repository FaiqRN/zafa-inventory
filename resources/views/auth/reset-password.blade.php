<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password | ZafaSys</title>
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
      margin-bottom: 20px;
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
      padding: 12px 45px 12px 40px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s ease;
    }

    .input-wrapper input:focus {
      outline: none;
      border-color: #fece0b;
    }

    .input-wrapper .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #999;
      transition: color 0.3s ease;
      z-index: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 24px;
      height: 24px;
    }

    .input-wrapper .toggle-password svg {
      width: 20px;
      height: 20px;
      fill: #999;
      transition: fill 0.3s ease;
    }

    .input-wrapper .toggle-password:hover svg {
      fill: #fece0b;
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

    .password-requirements {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 20px;
      font-size: 12px;
      color: #666;
    }

    .password-requirements ul {
      margin: 5px 0 0 15px;
      padding: 0;
    }

    .password-requirements li {
      margin-bottom: 3px;
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

    .hide {
      display: none !important;
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
  </style>
</head>
<body>

<div class="container">
  <div class="left">
    <img src="{{ asset('adminlte/dist/img/Zlogo.png') }}" alt="Logo">
    <h2>Password Baru</h2>
    <p>Buat password baru yang kuat untuk mengamankan akun Anda. Pastikan password mudah diingat tapi sulit ditebak.</p>
  </div>
  <div class="right">
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
            autocomplete="new-password">
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
            autocomplete="new-password">
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

      <a href="{{ route('login') }}" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
        </svg>
        Kembali ke Login
      </a>

    </form>
  </div>
</div>

<script>
  // Toggle password visibility for password field
  const togglePassword1 = document.getElementById('togglePassword1');
  const password = document.getElementById('password');
  const eyeShow1 = document.getElementById('eyeShow1');
  const eyeHide1 = document.getElementById('eyeHide1');

  togglePassword1.addEventListener('click', function() {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    eyeShow1.classList.toggle('hide');
    eyeHide1.classList.toggle('hide');
  });

  // Toggle password visibility for confirm password field
  const togglePassword2 = document.getElementById('togglePassword2');
  const passwordConfirmation = document.getElementById('password_confirmation');
  const eyeShow2 = document.getElementById('eyeShow2');
  const eyeHide2 = document.getElementById('eyeHide2');

  togglePassword2.addEventListener('click', function() {
    const type = passwordConfirmation.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordConfirmation.setAttribute('type', type);
    eyeShow2.classList.toggle('hide');
    eyeHide2.classList.toggle('hide');
  });
</script>

</body>
</html>
