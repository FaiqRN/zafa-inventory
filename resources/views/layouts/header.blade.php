<!-- resources/views/layouts/header.blade.php -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ url('/') }}" class="nav-link">Dashboard</a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
        @if(Auth::check())
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    @if(Auth::user()->foto)
                        <img src="{{ asset('storage/profile/'.Auth::user()->foto) }}" class="img-circle elevation-2" alt="User" style="width: 30px; height: 30px; object-fit: cover;">
                    @else
                        <i class="far fa-user-circle"></i>
                    @endif
                    <span class="ml-1">{{ Auth::user()->nama_lengkap ?? 'User' }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="{{ route('profile') }}" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('logout') }}" class="dropdown-item" 
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </li>
        @elseif(session()->has('user_id'))
            <li class="nav-item">
                <a href="{{ route('profile') }}" class="btn btn-primary mr-2">PROFILE</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('logout') }}" class="btn btn-danger">LOGOUT</a>
            </li>
        @endif
    </ul>
</nav>