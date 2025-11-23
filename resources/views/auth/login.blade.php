<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login | ZafaSys</title>
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
      font-size: 32px;
      margin-bottom: 10px;
    }

    .left p {
      font-size: 16px;
    }

    .right {
      flex: 1;
      padding: 40px;
    }

    .right h2 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 32px;
      font-weight: 600;
      color: #333;
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

    /* ===== INPUT WRAPPER WITH ICONS ===== */
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    /* Icon di KIRI input (User & Lock) */
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

    /* Input field dengan padding untuk icon */
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

    /* Icon di KANAN input (Eye toggle) */
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

    .form-group a {
      display: block;
      text-align: right;
      font-size: 14px;
      margin-top: 5px;
      color: #555;
      text-decoration: none;
    }

    .form-group a:hover {
      color: #fece0b;
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

    .remember-me {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .remember-me input[type="checkbox"] {
      width: auto;
      margin-right: 8px;
      cursor: pointer;
    }

    .remember-me label {
      margin: 0;
      cursor: pointer;
      font-weight: 400;
      font-size: 14px;
    }

    .btn-login {
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
    }

    .btn-login:hover {
      background: #ffd43b;
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

    /* ============================================
       SPLASH SCREEN STYLES - ELEGANT DESIGN
       ============================================ */
    
    #splash-screen {
      position: fixed;
      inset: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      flex-direction: column;
      transition: opacity 0.5s ease;
    }

    /* ===== WELCOME STATE - Warm & Friendly ===== */
    #splash-screen.welcome {
      background: linear-gradient(135deg, #fdfbfb 0%, #f7f4ec 100%);
    }

    #splash-screen.welcome .splash-icon {
      font-size: 120px;
      margin-bottom: 20px;
      filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
    }

    #splash-screen.welcome h1 {
      font-size: 78px;
      color: #ffd43b;
      font-weight: 700;
      margin-bottom: 10px;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.05);
    }

    #splash-screen.welcome p {
      font-size: 48px;
      color: #fece0b;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.05);
    }

    /* ===== REJECTED STATE - Elegant & Clear ===== */
    #splash-screen.rejected {
      background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
      animation: gentleShake 0.5s ease;
    }

    #splash-screen.rejected .splash-icon {
      font-size: 120px;
      margin-bottom: 20px;
      filter: drop-shadow(0 4px 12px rgba(220, 14, 14, 0.2));
      animation: gentlePulse 1.5s ease infinite;
    }

    #splash-screen.rejected h1 {
      font-size: 78px;
      color: #DC0E0E;
      font-weight: 700;
      margin-bottom: 15px;
      text-shadow: 2px 2px 6px rgba(220, 14, 14, 0.1);
      letter-spacing: 2px;
    }

    #splash-screen.rejected p {
      font-size: 36px;
      color: #DC0E0E;
      text-align: center;
      max-width: 700px;
      padding: 0 20px;
      line-height: 1.4;
      font-weight: 500;
      text-shadow: 1px 1px 3px rgba(220, 14, 14, 0.08);
    }

    /* ===== ANIMATIONS - Subtle & Professional ===== */
    @keyframes gentleShake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
      20%, 40%, 60%, 80% { transform: translateX(4px); }
    }

    @keyframes gentlePulse {
      0%, 100% { 
        transform: scale(1); 
        opacity: 1; 
      }
      50% { 
        transform: scale(1.08); 
        opacity: 0.9; 
      }
    }

    @keyframes fadeOut {
      from { opacity: 1; }
      to { opacity: 0; }
    }

    .fade-out {
      animation: fadeOut 0.5s ease forwards;
    }

    /* Hide class untuk toggle visibility */
    .hide {
      display: none !important;
    }
  </style>
</head>
<body>

<!-- Splash Screen -->
<div id="splash-screen" class="welcome">
  <!-- Welcome Content -->
  <div id="splash-content-welcome">
    <div class="splash-icon">👋</div>
    <h1>Hello</h1>
    <p>Welcome to ZafaSys</p>
  </div>
  
  <!-- Rejected Content -->
  <div id="splash-content-rejected" style="display: none;">
    <div class="splash-icon">🙅‍♀️</div>
    <h1>Oops!</h1>
    <p id="error-detail">Username atau password tidak valid</p>
  </div>
</div>

<!-- Login Form -->
<div class="container">
  <div class="left">
    <img src="{{ asset('adminlte/dist/img/zafalogo.png') }}" alt="Logo">
    <h2>Hello, Welcome!</h2>
    <p>Please login to continue</p>
  </div>
  <div class="right">
    <h2>Login</h2>

    <!-- Flash Messages -->
    @if(session('message'))
      <div class="flash-message {{ session('class', 'info') }}">
        {{ session('message') }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <!-- Username Field -->
      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-wrapper">
          <!-- ICON USER (KIRI) - SVG Inline -->
          <span class="input-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
            </svg>
          </span>
          <input 
            type="text" 
            name="username" 
            id="username" 
            placeholder="Enter your username" 
            value="{{ old('username') }}"
            autocomplete="username">
        </div>
        @error('username')
          <span class="error-message">{{ $message }}</span>
        @enderror
      </div>

      <!-- Password Field -->
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <!-- ICON GEMBOK (KIRI) - SVG Inline -->
          <span class="input-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
            </svg>
          </span>
          <input 
            type="text" 
            name="password" 
            id="password" 
            placeholder="Enter your password"
            autocomplete="current-password">
          
          <!-- ICON MATA (KANAN) - SVG Inline untuk toggle show/hide -->
          <span class="toggle-password" id="togglePassword" title="Hide password">
            <!-- Eye Hide Icon (default - password visible) -->
            <svg id="eyeHide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
            </svg>
            <!-- Eye Show Icon (when password is hidden) -->
            <svg id="eyeShow" class="hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
            </svg>
          </span>
        </div>
        @error('password')
          <span class="error-message">{{ $message }}</span>
        @enderror
        <a href="#">Forgot Password?</a>
      </div>

      <!-- Remember Me -->
      <div class="remember-me">
        <input type="checkbox" name="remember" id="remember" value="1">
        <label for="remember">Remember Me</label>
      </div>

      <button class="btn-login" type="submit">Login</button>

    </form>
  </div>
</div>

<!-- JavaScript -->
<script>
  // ============================================
  // SPLASH SCREEN FUNCTIONALITY
  // ============================================
  
  const hasErrors = {{ $errors->any() ? 'true' : 'false' }};
  const errorMessage = @json($errors->first('username') ?? 'Username atau password tidak valid');
  
  window.addEventListener('load', function () {
    const splashScreen = document.getElementById('splash-screen');
    const welcomeContent = document.getElementById('splash-content-welcome');
    const rejectedContent = document.getElementById('splash-content-rejected');
    const errorDetail = document.getElementById('error-detail');
    
    if (hasErrors) {
      // Show rejected state
      splashScreen.className = 'rejected';
      welcomeContent.style.display = 'none';
      rejectedContent.style.display = 'flex';
      rejectedContent.style.flexDirection = 'column';
      rejectedContent.style.alignItems = 'center';
      errorDetail.textContent = errorMessage;
      
      setTimeout(function () {
        splashScreen.classList.add('fade-out');
        setTimeout(function() {
          splashScreen.style.display = 'none';
        }, 500);
      }, 2800);
    } else {
      // Show welcome state
      splashScreen.className = 'welcome';
      welcomeContent.style.display = 'flex';
      welcomeContent.style.flexDirection = 'column';
      welcomeContent.style.alignItems = 'center';
      rejectedContent.style.display = 'none';
      
      setTimeout(function () {
        splashScreen.classList.add('fade-out');
        setTimeout(function() {
          splashScreen.style.display = 'none';
        }, 500);
      }, 2000);
    }
  });

  // ============================================
  // PASSWORD SHOW/HIDE FUNCTIONALITY
  // ============================================
  // BEHAVIOR:
  // 1. Saat halaman dibuka: password TERLIHAT (type="text"), icon HIDE (eyeHide tampil)
  // 2. Saat user mengetik: password TETAP TERLIHAT
  // 3. Setelah berhenti 1.5 detik: password AUTO-TERTUTUP (type="password"), icon SHOW (eyeShow tampil)
  // 4. Klik icon: toggle manual antara show/hide
  // ============================================
  
  const togglePassword = document.querySelector('#togglePassword');
  const passwordInput = document.querySelector('#password');
  const eyeHide = document.getElementById('eyeHide');
  const eyeShow = document.getElementById('eyeShow');
  
  let typingTimer;
  let isManuallyToggled = false;

  // Event: User mengetik password
  passwordInput.addEventListener('input', function() {
    // Clear timer sebelumnya
    clearTimeout(typingTimer);
    
    // Saat mengetik: password TETAP TERLIHAT (kecuali user manual hide)
    if (!isManuallyToggled) {
      passwordInput.setAttribute('type', 'text');
      eyeHide.classList.remove('hide');
      eyeShow.classList.add('hide');
      togglePassword.setAttribute('title', 'Hide password');
    }
    
    // Set timer untuk auto-hide setelah 1.5 detik tidak mengetik
    typingTimer = setTimeout(function() {
      if (!isManuallyToggled) {
        // AUTO-HIDE: password jadi dots (●●●●)
        passwordInput.setAttribute('type', 'password');
        eyeHide.classList.add('hide');
        eyeShow.classList.remove('hide');
        togglePassword.setAttribute('title', 'Show password');
      }
    }, 1500); // 1.5 detik
  });

  // Event: User klik icon mata (manual toggle)
  togglePassword.addEventListener('click', function () {
    // Clear auto-hide timer
    clearTimeout(typingTimer);
    
    const currentType = passwordInput.getAttribute('type');
    
    if (currentType === 'password') {
      // Password TERSEMBUNYI → TAMPILKAN
      passwordInput.setAttribute('type', 'text');
      eyeHide.classList.remove('hide');
      eyeShow.classList.add('hide');
      this.setAttribute('title', 'Hide password');
      isManuallyToggled = true;
    } else {
      // Password TERLIHAT → SEMBUNYIKAN
      passwordInput.setAttribute('type', 'password');
      eyeHide.classList.add('hide');
      eyeShow.classList.remove('hide');
      this.setAttribute('title', 'Show password');
      isManuallyToggled = true;
    }
  });

  // Event: User mulai mengetik lagi (reset flag manual)
  passwordInput.addEventListener('keydown', function() {
    if (isManuallyToggled) {
      isManuallyToggled = false;
    }
  });

  // Event: User focus ke field password (jika ada isi, tampilkan)
  passwordInput.addEventListener('focus', function() {
    if (!isManuallyToggled && passwordInput.value.length > 0) {
      passwordInput.setAttribute('type', 'text');
      eyeHide.classList.remove('hide');
      eyeShow.classList.add('hide');
      togglePassword.setAttribute('title', 'Hide password');
    }
  });
</script>

</body>
</html>