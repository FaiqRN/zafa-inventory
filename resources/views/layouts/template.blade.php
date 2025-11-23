<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{config('app.name','ZafaSys')}}</title>

  <!-- Favicon ZafaSys - Multiple Sizes for Better Display -->
  <link rel="icon" type="image/png" sizes="16x16" href="{{asset('adminlte/dist/img/zafalogo.png')}}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{asset('adminlte/dist/img/zafalogo.png')}}">
  <link rel="icon" type="image/png" sizes="48x48" href="{{asset('adminlte/dist/img/zafalogo.png')}}">
  <link rel="shortcut icon" href="{{asset('adminlte/dist/img/zafalogo.png')}}">
  <link rel="apple-touch-icon" href="{{asset('adminlte/dist/img/zafalogo.png')}}">

  <meta name="csrf-token" content="{{csrf_token()}}">

  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{asset('adminlte/plugins/fontawesome-free/css/all.min.css')}}">
  <!-- DataTables-->
  <link rel="stylesheet" href="{{asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('adminlte/dist/css/adminlte.min.css')}}">

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
  <!-- ZafaSys Color Scheme - Inspired by Logo -->
  <style>
    /* Color Palette from ZafaSys Logo */
    :root {
      --zafa-yellow: #FFC107;      /* Logo Yellow - Primary */
      --zafa-gold: #FFD700;        /* Logo Gold - Bright */
      --zafa-orange: #FF9800;      /* Logo Orange/Potato */
      --zafa-brown: #8D6E63;       /* Logo Brown - Warm */
      --zafa-turquoise: #26C6DA;   /* Logo Turquoise/Fish */
      --zafa-teal: #00897B;        /* Logo Teal - Deep */
      --zafa-dark: #4A2511;        /* Logo Text - Dark Brown */
      --zafa-light: #FFFEF7;       /* Warm White - Sidebar */
      --zafa-cream: #FFF9E6;       /* Light Cream - Hover */
    }

    /* Sidebar - Subtle Cream Background for Visual Separation */
    .main-sidebar {
      background-color: var(--zafa-light) !important;
      box-shadow: 2px 0 12px rgba(0,0,0,0.08) !important;
      width: 280px !important;
    }

    /* Adjust content wrapper for wider sidebar */
    @media (min-width: 768px) {
      .sidebar-mini.sidebar-collapse .content-wrapper,
      .sidebar-mini.sidebar-collapse .main-footer {
        margin-left: 4.6rem !important;
      }
      
      .content-wrapper,
      .main-footer {
        margin-left: 280px !important;
      }
    }

    /* Sidebar Brand */
    .brand-link {
      background-color: var(--zafa-light) !important;
      border-bottom: 2px solid var(--zafa-yellow) !important;
      padding: 1rem 1.2rem !important;
      min-height: 85px !important;
      display: flex !important;
      align-items: center !important;
    }

    .brand-link:hover {
      background-color: var(--zafa-cream) !important;
      transition: all 0.3s ease !important;
    }

    .brand-text {
      color: var(--zafa-dark) !important;
      font-weight: 700 !important;
      font-size: 1.4rem !important;
    }

    /* Logo Styling */
    .brand-image {
      width: 65px !important;
      height: 70px !important;
      max-height: none !important;
      border: 2px solid var(--zafa-yellow) !important;
      box-shadow: 0 2px 8px rgba(255,193,7,0.3) !important;
      margin-right: 15px !important;
    }

    /* Sidebar Menu Items - Proper Spacing */
    .nav-sidebar .nav-item > .nav-link {
      color: var(--zafa-dark) !important;
      background-color: transparent !important;
      border-radius: 10px !important;
      margin: 4px 12px !important;
      padding: 12px 16px !important;
      transition: all 0.25s ease !important;
      font-weight: 500 !important;
      font-size: 0.95rem !important;
    }

    .nav-sidebar .nav-item > .nav-link:hover {
      background-color: white !important;
      color: var(--zafa-orange) !important;
      transform: translateX(4px) !important;
      box-shadow: 0 2px 6px rgba(0,0,0,0.06) !important;
    }

    .nav-sidebar .nav-item > .nav-link.active {
      background-color: var(--zafa-yellow) !important;
      color: white !important;
      font-weight: 600 !important;
      box-shadow: 0 2px 8px rgba(255,193,7,0.3) !important;
    }

    /* Sidebar Sub-menu - Better Spacing & Layout */
    .nav-treeview {
      padding-left: 0 !important;
      margin-top: 4px !important;
      margin-bottom: 4px !important;
    }

    .nav-treeview > .nav-item > .nav-link {
      color: #666 !important;
      background-color: transparent !important;
      padding: 10px 16px 10px 52px !important;
      margin: 2px 12px !important;
      border-radius: 8px !important;
      font-size: 0.88rem !important;
      line-height: 1.3 !important;
      transition: all 0.25s ease !important;
      white-space: normal !important;
      word-wrap: break-word !important;
      display: flex !important;
      align-items: center !important;
    }

    .nav-treeview > .nav-item > .nav-link:hover {
      background-color: white !important;
      color: var(--zafa-turquoise) !important;
      transform: translateX(4px) !important;
      box-shadow: 0 2px 6px rgba(0,0,0,0.06) !important;
    }

    .nav-treeview > .nav-item > .nav-link.active {
      background-color: var(--zafa-turquoise) !important;
      color: white !important;
      font-weight: 600 !important;
    }

    /* Sub-menu Icon & Text Layout */
    .nav-treeview > .nav-item > .nav-link .nav-icon {
      font-size: 0.75rem !important;
      margin-right: 8px !important;
      flex-shrink: 0 !important;
    }

    .nav-treeview > .nav-item > .nav-link p {
      white-space: normal !important;
      word-wrap: break-word !important;
      line-height: 1.3 !important;
      margin: 0 !important;
    }

    /* Header/Navbar - Clean White */
    .main-header.navbar {
      background-color: white !important;
      border-bottom: 2px solid #F5F5F5 !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
    }

    /* FIX: Pushmenu Button (Hamburger) - SUPER VISIBLE */
    .navbar-nav .nav-link[data-widget="pushmenu"] {
      color: var(--zafa-dark) !important;
      background-color: var(--zafa-yellow) !important;
      font-size: 1.4rem !important;
      padding: 12px 18px !important;
      border-radius: 10px !important;
      transition: all 0.3s ease !important;
      border: 3px solid var(--zafa-orange) !important;
      box-shadow: 0 2px 8px rgba(255,193,7,0.4) !important;
      margin-right: 10px !important;
    }

    .navbar-nav .nav-link[data-widget="pushmenu"]:hover {
      color: white !important;
      background-color: var(--zafa-orange) !important;
      transform: scale(1.1) rotate(90deg) !important;
      border-color: var(--zafa-dark) !important;
      box-shadow: 0 4px 12px rgba(255,152,0,0.5) !important;
    }

    .navbar-nav .nav-link[data-widget="pushmenu"] i {
      font-size: 1.3rem !important;
      font-weight: 900 !important;
      display: block !important;
    }

    /* Header Navigation Links */
    .navbar-nav .nav-link {
      color: var(--zafa-dark) !important;
      font-weight: 500 !important;
      transition: all 0.25s ease !important;
      padding: 8px 12px !important;
      border-radius: 8px !important;
    }

    .navbar-nav .nav-link:hover {
      color: var(--zafa-orange) !important;
      background-color: var(--zafa-cream) !important;
    }

    /* Fullscreen Button */
    .navbar-nav .nav-link[data-widget="fullscreen"] {
      color: var(--zafa-dark) !important;
    }

    .navbar-nav .nav-link[data-widget="fullscreen"]:hover {
      color: var(--zafa-orange) !important;
      background-color: var(--zafa-cream) !important;
    }

    /* User Dropdown */
    .dropdown-menu {
      border: 1px solid #E0E0E0 !important;
      border-radius: 10px !important;
      box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important;
      background-color: white !important;
    }

    .dropdown-item {
      color: var(--zafa-dark) !important;
      transition: all 0.25s ease !important;
      padding: 10px 20px !important;
      border-radius: 8px !important;
      margin: 3px 8px !important;
    }

    .dropdown-item:hover {
      background-color: var(--zafa-cream) !important;
      color: var(--zafa-orange) !important;
    }

    /* User Profile Image Border */
    .img-circle {
      border: 2px solid var(--zafa-yellow) !important;
    }

    /* Footer - Clean & Minimal */
    .main-footer {
      background-color: white !important;
      color: var(--zafa-dark) !important;
      border-top: 2px solid #F5F5F5 !important;
      font-weight: 500 !important;
    }

    .main-footer a {
      color: var(--zafa-orange) !important;
      font-weight: 600 !important;
      text-decoration: none !important;
      transition: all 0.25s ease !important;
    }

    .main-footer a:hover {
      color: var(--zafa-turquoise) !important;
    }

    .main-footer .float-right {
      color: #757575 !important;
    }

    /* Control Sidebar */
    .control-sidebar-dark {
      background-color: var(--zafa-dark) !important;
    }

    /* Breadcrumb Styling */
    .content-header {
      background-color: white !important;
      border-bottom: 1px solid #F5F5F5 !important;
      padding: 16px 20px !important;
    }

    .breadcrumb {
      background-color: transparent !important;
      margin-bottom: 0 !important;
    }

    .breadcrumb-item a {
      color: #757575 !important;
      text-decoration: none !important;
      transition: all 0.25s ease !important;
    }

    .breadcrumb-item a:hover {
      color: var(--zafa-orange) !important;
    }

    .breadcrumb-item.active {
      color: var(--zafa-orange) !important;
      font-weight: 600 !important;
    }

    .breadcrumb-item + .breadcrumb-item::before {
      color: #BDBDBD !important;
    }

    /* Page Title */
    .content-header h1 {
      color: var(--zafa-dark) !important;
      font-weight: 700 !important;
    }

    /* Button Customizations */
    .btn-primary {
      background-color: var(--zafa-turquoise) !important;
      border: none !important;
      color: white !important;
      font-weight: 600 !important;
      transition: all 0.25s ease !important;
    }

    .btn-primary:hover, .btn-primary:focus {
      background-color: var(--zafa-teal) !important;
      transform: translateY(-1px) !important;
      box-shadow: 0 4px 8px rgba(38,198,218,0.3) !important;
    }

    .btn-warning {
      background-color: var(--zafa-yellow) !important;
      border: none !important;
      color: white !important;
      font-weight: 600 !important;
      transition: all 0.25s ease !important;
    }

    .btn-warning:hover, .btn-warning:focus {
      background-color: var(--zafa-orange) !important;
      transform: translateY(-1px) !important;
      box-shadow: 0 4px 8px rgba(255,152,0,0.3) !important;
      color: white !important;
    }

    .btn-danger {
      background-color: #E53935 !important;
      border: none !important;
      color: white !important;
      font-weight: 600 !important;
      transition: all 0.25s ease !important;
    }

    .btn-danger:hover, .btn-danger:focus {
      background-color: #C62828 !important;
      transform: translateY(-1px) !important;
      box-shadow: 0 4px 8px rgba(229,57,53,0.3) !important;
    }

    /* Scrollbar Customization */
    .sidebar::-webkit-scrollbar {
      width: 6px !important;
    }

    .sidebar::-webkit-scrollbar-track {
      background: #F5F5F5 !important;
    }

    .sidebar::-webkit-scrollbar-thumb {
      background: #BDBDBD !important;
      border-radius: 3px !important;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
      background: #9E9E9E !important;
    }

    /* Active menu icon */
    .nav-sidebar .nav-link.active .nav-icon {
      color: white !important;
    }

    .nav-sidebar .nav-link .nav-icon {
      transition: all 0.25s ease !important;
    }

    /* Content Wrapper */
    .content-wrapper {
      background-color: #FAFAFA !important;
    }

    /* Card Styling */
    .card {
      border-radius: 10px !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
      border: 1px solid #E0E0E0 !important;
      transition: all 0.25s ease !important;
    }

    .card:hover {
      box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important;
    }

    .card-header {
      background-color: white !important;
      color: var(--zafa-dark) !important;
      font-weight: 700 !important;
      border-bottom: 2px solid var(--zafa-yellow) !important;
      border-radius: 10px 10px 0 0 !important;
    }

    /* Dashboard Card Text - Better Contrast */
    .card .card-body {
      color: var(--zafa-dark) !important;
    }

    .card .card-body h3,
    .card .card-body h4,
    .card .card-body h5,
    .card .card-body .text-white {
      color: var(--zafa-dark) !important;
      font-weight: 700 !important;
    }

    /* Info Box Text - Dark for visibility */
    .info-box .info-box-text,
    .info-box .info-box-number {
      color: white !important;
      text-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
    }

    /* Small Box Text */
    .small-box h3,
    .small-box p {
      color: white !important;
      text-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
    }

    .small-box .icon {
      color: rgba(255,255,255,0.3) !important;
    }

    /* Table Styling */
    .table thead th {
      background-color: #FAFAFA !important;
      color: var(--zafa-dark) !important;
      font-weight: 600 !important;
      border-bottom: 2px solid var(--zafa-yellow) !important;
    }

    .table-hover tbody tr:hover {
      background-color: var(--zafa-cream) !important;
    }

    /* Menu Parent with Arrow Indicator */
    .nav-sidebar .nav-item.has-treeview > .nav-link .right {
      transition: transform 0.3s ease !important;
    }

    .nav-sidebar .nav-item.menu-open > .nav-link .right {
      transform: rotate(-90deg) !important;
    }

    /* Mobile Responsive */
    @media (max-width: 767.98px) {
      .main-sidebar {
        width: 250px !important;
      }
      
      .nav-treeview > .nav-item > .nav-link {
        font-size: 0.85rem !important;
        padding-left: 45px !important;
      }
    }

    /* Sidebar Collapsed State - FIX */
    .sidebar-mini.sidebar-collapse .main-sidebar {
      width: 4.6rem !important;
    }

    .sidebar-mini.sidebar-collapse .nav-sidebar .nav-link p,
    .sidebar-mini.sidebar-collapse .brand-text {
      display: none !important;
    }

    .sidebar-mini.sidebar-collapse .nav-sidebar .nav-link {
      width: calc(4.6rem - 0.5rem) !important;
      text-align: center !important;
      padding: 0.8rem 0 !important;
      margin: 0.25rem auto !important;
    }

    .sidebar-mini.sidebar-collapse .nav-sidebar .nav-link .nav-icon {
      margin-right: 0 !important;
      font-size: 1.2rem !important;
    }

    .sidebar-mini.sidebar-collapse .nav-treeview {
      display: none !important;
    }

    .sidebar-mini.sidebar-collapse .brand-link {
      justify-content: center !important;
      padding: 0.8rem 0 !important;
    }

    .sidebar-mini.sidebar-collapse .brand-image {
      margin-right: 0 !important;
      width: 50px !important;
      height: 50px !important;
    }
  </style>

  @stack('css')
</head>
<body class="hold-transition sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper">
  <!-- Navbar -->
  @include('layouts.header')
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{url('/')}}" class="brand-link">
      <img src="{{asset('adminlte/dist/img/zafalogo.png')}}" alt="Zafa Logo" class="brand-image img-circle elevation-3" style="opacity: .9">
      <span class="brand-text font-weight-light">ZafaSys</span>
    </a>

    <!-- Sidebar -->
    @include('layouts.sidebar')
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    @include('layouts.breadcrumb')

    <!-- Main content -->
    <section class="content">
      @yield('content')
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  @include('layouts.footer')

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="{{asset('adminlte/plugins/jquery/jquery.min.js')}}"></script>
<!-- Bootstrap 4 -->
<script src="{{asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!--DataTable & Plugins-->
<script src="{{asset('adminlte/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/jszip/jszip.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/pdfmake/pdfmake.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/pdfmake/vfs_fonts.js')}}"></script>
<script src="{{asset('adminlte/plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>
<script src="{{asset('adminlte/plugins/datatables-buttons/js/buttons.colvis.min.js')}}"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Chart JS -->
<script src="{{asset('adminlte/plugins/chart.js/Chart.min.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{asset('adminlte/dist/js/adminlte.min.js')}}"></script>
<!-- Leaflet JS Libraries untuk Market Map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster-src.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>



<script>
  $.ajaxSetup({headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}})
</script>
@stack('js')
<script>
  if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
  }
  
  // Mencegah back button setelah logout
  window.onpageshow = function(event) {
      if (event.persisted) {
          window.location.reload();
      }
  };
</script>
</body>
</html>