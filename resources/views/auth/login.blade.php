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

    #splash-screen {
      position: fixed;
      inset: 0;
      background: linear-gradient(to right, #fdfdfc, #ebe8e0);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      flex-direction: column;
    }

    #splash-screen h1 {
      font-size: 78px;
      color: #ffd43b;
      font-weight: 700;
      margin-bottom: 10px;
    }

    #splash-screen p {
      font-size: 48px;
      color: #fece0b;
    }
  </style>
</head>
<body>

<!-- Splash Screen -->
<div id="splash-screen">
  <h1>Hello 👋</h1>
  <p>Welcome to ZafaSys</p>
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
    <form method="POST" action="{{ route('login') }}">
      @csrf

      <!-- Username -->
      <div class="form-group">
        <label for="username">Username</label>
        <i class='bx bx-user input-icon-left'></i>
        <input type="text" name="username" id="username" placeholder="Enter your username" required>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label for="password">Password</label>
        <i class='bx bx-lock input-icon-left'></i>
        <input type="password" name="password" id="password" placeholder="Enter your password" required>
        <i class='bx bx-show toggle-password' id="togglePassword"></i>
        <a href="#">Forgot Password?</a>
      </div>

      <button class="btn-login" type="submit">Login</button>

    </form>
  </div>
</div>

<!-- JavaScript -->
<script>
  window.addEventListener('load', function () {
    setTimeout(function () {
      document.getElementById('splash-screen').style.display = 'none';
    }, 2000);
  });

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
