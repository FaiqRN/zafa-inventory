<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Login - Zafa Distribusi</title>
    
    <style>
        body {
            background-color: #2b71e8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        
        .login-container {
            background-color: #d3d3d3;
            padding: 2rem;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background-color: #ff0000;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-bottom: 1rem;
            transition: background-color 0.2s;
        }
        
        .btn-login:hover {
            background-color: #cc0000;
        }
        
        .forgot-password {
            text-align: center;
        }
        
        .forgot-password a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background-color: #fff3f3;
            color: #dc3545;
            border: 1px solid #f8d7da;
        }
        
        .alert-success {
            background-color: #f3fff3;
            color: #28a745;
            border: 1px solid #d7f8db;
        }
        
        .company-name {
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .remember-me input {
            margin-right: 0.5rem;
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
