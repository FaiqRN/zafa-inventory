<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login | ZafaSys</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
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
      position: relative;
      margin-bottom: 25px;
    }

    .form-group label {
      display: block;
      font-weight: 500;
      margin-bottom: 5px;
    }

    .form-group input {
      width: 100%;
      padding: 12px 40px 12px 40px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    .form-group .input-icon-left {
      position: absolute;
      top: 38px;
      left: 10px;
      color: #999;
      font-size: 18px;
    }

    .form-group .toggle-password {
      position: absolute;
      top: 38px;
      right: 10px;
      cursor: pointer;
      color: #999;
      font-size: 18px;
    }

    .form-group a {
      display: block;
      text-align: right;
      font-size: 14px;
      margin-top: 5px;
      color: #555;
      text-decoration: none;
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
    }

    .btn-login:hover {
      background: #ffd43b;
    }

    .social-login {
      text-align: center;
      margin-top: 20px;
    }

    .social-login i {
      font-size: 22px;
      margin: 0 10px;
      cursor: pointer;
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

      <!-- Username -->
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" placeholder="Enter your username" value="{{ old('username') }}">
        @error('username')
          <span class="error-message">{{ $message }}</span>
        @enderror
      </div>

      <!-- Password -->
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter your password">
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
  // Check if there are validation errors
  const hasErrors = {{ $errors->any() ? 'true' : 'false' }};
  const errorMessage = @json($errors->first('username') ?? 'Username atau password tidak valid');
  
  window.addEventListener('load', function () {
    const splashScreen = document.getElementById('splash-screen');
    const welcomeContent = document.getElementById('splash-content-welcome');
    const rejectedContent = document.getElementById('splash-content-rejected');
    const errorDetail = document.getElementById('error-detail');
    
    if (hasErrors) {
      // Show rejected state - elegant error display
      splashScreen.className = 'rejected';
      welcomeContent.style.display = 'none';
      rejectedContent.style.display = 'flex';
      rejectedContent.style.flexDirection = 'column';
      rejectedContent.style.alignItems = 'center';
      errorDetail.textContent = errorMessage;
      
      // Hide splash screen after 2.8 seconds (slightly longer for error reading)
      setTimeout(function () {
        splashScreen.classList.add('fade-out');
        setTimeout(function() {
          splashScreen.style.display = 'none';
        }, 500);
      }, 2800);
    } else {
      // Show welcome state - friendly greeting
      splashScreen.className = 'welcome';
      welcomeContent.style.display = 'flex';
      welcomeContent.style.flexDirection = 'column';
      welcomeContent.style.alignItems = 'center';
      rejectedContent.style.display = 'none';
      
      // Hide splash screen after 2 seconds
      setTimeout(function () {
        splashScreen.classList.add('fade-out');
        setTimeout(function() {
          splashScreen.style.display = 'none';
        }, 500);
      }, 2000);
    }
  });

  // Toggle Password Visibility
  const togglePassword = document.querySelector('#togglePassword');
  const passwordInput = document.querySelector('#password');

  togglePassword.addEventListener('click', function () {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.classList.toggle('bx-show');
    this.classList.toggle('bx-hide');
  });
</script>

</body>
</html>