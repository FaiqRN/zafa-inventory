<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{config('app.name','ZafaSys')}}</title>

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

  <!-- Custom Color Scheme -->
  <style>
    /* Custom Color Scheme - Color Hunt Palette */
    :root {
      --primary-color: #309898;    /* Teal */
      --secondary-color: #FF9F00;  /* Orange */
      --accent-color: #F4631E;     /* Red-Orange */
      --danger-color: #CB0404;     /* Dark Red */
    }

    /* Sidebar Styling */
    .main-sidebar {
      background-color: var(--primary-color) !important;
      background-image: linear-gradient(180deg, var(--primary-color) 0%, #00235B 60%) !important;
    }

    /* Sidebar Brand */
    .brand-link {
      background-color: rgba(0,0,0,0.1) !important;
      border-bottom: 1px solid rgba(255,255,255,0.1) !important;
      padding: 0.8rem 1rem !important;
      min-height: 80px !important;
      display: flex !important;
      align-items: center !important;
    }

    .brand-link:hover {
      background-color: rgba(0,0,0,0.2) !important;
    }

    .brand-text {
      color: white !important;
      font-weight: bold !important;
      font-size: 1.3rem !important;
    }

    /* Logo Styling */
    .brand-image {
      width: 65px !important;
      height: 70px !important;
      max-height: none !important;
      border: 3px solid var(--secondary-color) !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
      margin-right: 15px !important;
    }

    /* Sidebar Menu Items */
    .nav-sidebar .nav-item > .nav-link {
      color: rgba(255,255,255,0.9) !important;
      border-radius: 8px !important;
      margin: 2px 8px !important;
      transition: all 0.3s ease !important;
    }

    .nav-sidebar .nav-item > .nav-link:hover {
      background-color: rgba(255,255,255,0.1) !important;
      color: white !important;
      transform: translateX(5px) !important;
    }

    .nav-sidebar .nav-item > .nav-link.active {
      background-color: var(--secondary-color) !important;
      color: white !important;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
    }

    /* Sidebar Sub-menu */
    .nav-treeview > .nav-item > .nav-link {
      color: rgba(255,255,255,0.8) !important;
      padding-left: 3rem !important;
    }

    .nav-treeview > .nav-item > .nav-link:hover {
      background-color: rgba(255,255,255,0.1) !important;
      color: white !important;
    }

    .nav-treeview > .nav-item > .nav-link.active {
      background-color: var(--accent-color) !important;
      color: white !important;
    }

    /* Header/Navbar Styling */
    .main-header.navbar {
      background-color: #FFFAD7 !important;
      border-bottom: 3px solid var(--primary-color) !important;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }

    /* Header Navigation Links */
    .navbar-nav .nav-link {
      color: var(--primary-color) !important;
      font-weight: 500 !important;
      transition: all 0.3s ease !important;
    }

    .navbar-nav .nav-link:hover {
      color: var(--accent-color) !important;
      transform: translateY(-1px) !important;
    }

    /* User Dropdown */
    .dropdown-menu {
      border: 2px solid var(--primary-color) !important;
      border-radius: 8px !important;
      box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    }

    .dropdown-item {
      color: var(--primary-color) !important;
      transition: all 0.3s ease !important;
    }

    .dropdown-item:hover {
      background-color: var(--primary-color) !important;
      color: whitesmoke !important;
    }

    /* User Profile Image Border */
    .img-circle {
      border: 2px solid var(--secondary-color) !important;
    }

    /* Footer Styling */
    .main-footer {
      background-color: var(--primary-color) !important;
      color: white !important;
      border-top: 3px solid var(--secondary-color) !important;
    }

    .main-footer a {
      color: var(--secondary-color) !important;
      font-weight: bold !important;
      text-decoration: none !important;
    }

    .main-footer a:hover {
      color: var(--accent-color) !important;
      text-decoration: underline !important;
    }

    /* Control Sidebar */
    .control-sidebar-dark {
      background-color: var(--primary-color) !important;
    }

    /* Breadcrumb Styling */
    .content-header {
      background-color: #f8f9fa !important;
      border-bottom: 1px solid #e9ecef !important;
    }

    .breadcrumb {
      background-color: transparent !important;
    }

    .breadcrumb-item a {
      color: var(--primary-color) !important;
      text-decoration: none !important;
    }

    .breadcrumb-item a:hover {
      color: var(--accent-color) !important;
      text-decoration: underline !important;
    }

    .breadcrumb-item.active {
      color: var(--accent-color) !important;
      font-weight: bold !important;
    }

    /* Page Title */
    .content-header h1 {
      color: var(--primary-color) !important;
      font-weight: bold !important;
    }

    /* Button Customizations */
    .btn-primary {
      background-color: var(--primary-color) !important;
      border-color: var(--primary-color) !important;
    }

    .btn-primary:hover, .btn-primary:focus {
      background-color: #267373 !important;
      border-color: #267373 !important;
    }

    .btn-warning {
      background-color: var(--secondary-color) !important;
      border-color: var(--secondary-color) !important;
    }

    .btn-warning:hover, .btn-warning:focus {
      background-color: #e68a00 !important;
      border-color: #e68a00 !important;
    }

    .btn-danger {
      background-color: var(--danger-color) !important;
      border-color: var(--danger-color) !important;
    }

    .btn-danger:hover, .btn-danger:focus {
      background-color: #a30303 !important;
      border-color: #a30303 !important;
    }

    /* Scrollbar Customization for Sidebar */
    .sidebar::-webkit-scrollbar {
      width: 6px !important;
    }

    .sidebar::-webkit-scrollbar-track {
      background: rgba(255,255,255,0.1) !important;
    }

    .sidebar::-webkit-scrollbar-thumb {
      background: rgba(255,255,255,0.3) !important;
      border-radius: 3px !important;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
      background: rgba(255,255,255,0.5) !important;
    }

    /* Additional Animations */
    .nav-sidebar .nav-item {
      transition: all 0.3s ease !important;
    }

    .nav-sidebar .nav-item:hover {
      transform: scale(1.02) !important;
    }

    /* Active menu icon color */
    .nav-sidebar .nav-link.active .nav-icon {
      color: white !important;
    }

    /* Pushmenu button styling */
    .nav-link[data-widget="pushmenu"] {
      color: var(--primary-color) !important;
    }

    .nav-link[data-widget="pushmenu"]:hover {
      color: var(--accent-color) !important;
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
<script src="{{asset('adminlte/plugins/datatables-responsive/js/datatable.responsive.min.js')}}"></script>
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