<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{config('app.name','ZafaSys')}}</title>

  <!-- Favicon ZafaSys - Multiple Sizes for Better Display -->
  <link rel="icon" type="image/png" sizes="16x16" href="{{asset('adminlte/dist/img/Zlogo.png')}}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{asset('adminlte/dist/img/Zlogo.png')}}">
  <link rel="icon" type="image/png" sizes="48x48" href="{{asset('adminlte/dist/img/Zlogo.png')}}">
  <link rel="shortcut icon" href="{{asset('adminlte/dist/img/Zlogo.png')}}">
  <link rel="apple-touch-icon" href="{{asset('adminlte/dist/img/Zlogo.png')}}">

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
  <!-- Alert Helper Styles -->
  <link rel="stylesheet" href="{{asset('css/alert-styles.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('adminlte/dist/css/adminlte.min.css')}}">
  <!-- ZafaSys Responsive Styles -->
  <link rel="stylesheet" href="{{asset('css/responsive.css')}}">

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
      --zafa-header: #FFF2C6;      /* NEW: Warm Cream Header */
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
      cursor: pointer !important;
      position: relative !important;
      z-index: 1 !important;
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

    /* Parent menu with dropdown indicator */
    .nav-sidebar .has-treeview > .nav-link {
      position: relative !important;
    }

    .nav-sidebar .has-treeview > .nav-link .right {
      position: absolute !important;
      right: 16px !important;
      top: 50% !important;
      transform: translateY(-50%) !important;
      transition: transform 0.3s ease !important;
      font-size: 0.9rem !important;
    }

    /* Rotate arrow when menu is open */
    .nav-sidebar .has-treeview.menu-open > .nav-link .right {
      transform: translateY(-50%) rotate(-90deg) !important;
    }

    /* Sidebar Sub-menu - Better Spacing & Layout */
    .nav-treeview {
      padding-left: 0 !important;
      margin-top: 4px !important;
      margin-bottom: 4px !important;
      display: none; /* Hidden by default */
    }

    /* Show submenu when parent is open */
    .nav-item.menu-open > .nav-treeview {
      display: block !important;
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
      cursor: pointer !important;
      position: relative !important;
      z-index: 1 !important;
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

    /* ========================================
       FIXED: Header/Navbar dengan Warna Baru
       ======================================== */
    .main-header.navbar {
      background-color: var(--zafa-header) !important; /* #FFF2C6 - Warm Cream */
      border-bottom: 2px solid var(--zafa-yellow) !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    }

     /* ========================================
       FIXED: Pushmenu Button
       dengan Z-Index dan Positioning Tinggi
       ======================================== */
    .navbar-nav .nav-link[data-widget="pushmenu"] {
      color: var(--zafa-dark) !important;
      background: transparent !important;
      font-size: 1.4rem !important;
      padding: 4px 0 !important;
      border: 0 !important;
      border-radius: 0 !important;
      transition: color 0.2s ease, transform 0.2s ease !important;
      box-shadow: none !important;
      appearance: none !important;
      -webkit-appearance: none !important;
      margin-right: 10px !important;
      position: relative !important;
      left: 8px !important;
      z-index: 9999 !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      width: 38px !important;
      height: 38px !important;
      visibility: visible !important;
      opacity: 1 !important;
    }

    .navbar-nav .nav-link[data-widget="pushmenu"]:hover {
      color: var(--zafa-orange) !important;
      background: transparent !important;
      transform: scale(1.05) !important;
    }

    .navbar-nav .nav-link[data-widget="pushmenu"]:active {
      transform: scale(0.95) !important;
    }

    .navbar-nav .nav-link[data-widget="pushmenu"] i {
      font-size: 1.15rem !important;
      font-weight: 900 !important;
      display: block !important;
      pointer-events: none !important;
      visibility: visible !important;
      opacity: 1 !important;
      color: var(--zafa-dark) !important;
    }

    /* CRITICAL: Ensure icon is always visible in all states */
    body.sidebar-mini.sidebar-collapse .navbar-nav .nav-link[data-widget="pushmenu"] i,
    body.sidebar-mini .navbar-nav .nav-link[data-widget="pushmenu"] i,
    body .navbar-nav .nav-link[data-widget="pushmenu"] i {
      display: block !important;
      visibility: visible !important;
      opacity: 1 !important;
    }

    /* FIX: Ensure navbar items tidak overlap dengan pushmenu button */
    .navbar-nav {
      position: relative !important;
      z-index: 9998 !important;
    }

    /* FIX: Ensure pushmenu tetap visible di collapsed state */
    .sidebar-mini.sidebar-collapse .navbar-nav .nav-link[data-widget="pushmenu"] {
      display: inline-flex !important;
      visibility: visible !important;
      opacity: 1 !important;
      pointer-events: auto !important;
    }

    /* Header Navigation Links */
    .navbar-nav .nav-link {
      color: var(--zafa-dark) !important;
      font-weight: 500 !important;
      transition: all 0.25s ease !important;
      padding: 8px 12px !important;
      border-radius: 8px !important;
      position: relative !important;
      z-index: 1 !important;
    }

    .navbar-nav .nav-link:hover {
      color: var(--zafa-orange) !important;
      background-color: rgba(255, 152, 0, 0.1) !important;
    }

    /* Fullscreen Button - Always Visible */
    .navbar-nav .nav-link[data-widget="fullscreen"] {
      color: var(--zafa-dark) !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      visibility: visible !important;
      opacity: 1 !important;
      pointer-events: auto !important;
    }

    .navbar-nav .nav-link[data-widget="fullscreen"]:hover {
      color: var(--zafa-orange) !important;
      background-color: rgba(255, 152, 0, 0.1) !important;
    }

    .navbar-nav .nav-link[data-widget="fullscreen"] i {
      display: block !important;
    }

    /* User Dropdown */
    .dropdown-menu {
      border-radius: 10px !important;
      box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important;
      border: 1px solid #E0E0E0 !important;
    }

    .dropdown-item {
      color: var(--zafa-dark) !important;
      padding: 10px 20px !important;
      transition: all 0.25s ease !important;
    }

    .dropdown-item:hover {
      background-color: var(--zafa-cream) !important;
      color: var(--zafa-orange) !important;
    }

    .dropdown-item i {
      color: var(--zafa-orange) !important;
    }

    /* Buttons */
    .btn-primary {
      background-color: var(--zafa-yellow) !important;
      border-color: var(--zafa-yellow) !important;
      color: white !important;
      font-weight: 600 !important;
      border-radius: 8px !important;
      transition: all 0.25s ease !important;
    }

    .btn-primary:hover {
      background-color: var(--zafa-orange) !important;
      border-color: var(--zafa-orange) !important;
      transform: translateY(-2px) !important;
      box-shadow: 0 4px 12px rgba(255,152,0,0.3) !important;
    }

    .btn-success {
      background-color: var(--zafa-turquoise) !important;
      border-color: var(--zafa-turquoise) !important;
      color: white !important;
      font-weight: 600 !important;
      border-radius: 8px !important;
    }

    .btn-success:hover {
      background-color: var(--zafa-teal) !important;
      border-color: var(--zafa-teal) !important;
      transform: translateY(-2px) !important;
    }

    .btn-info {
      background-color: var(--zafa-turquoise) !important;
      border-color: var(--zafa-turquoise) !important;
      color: white !important;
      font-weight: 600 !important;
      border-radius: 8px !important;
    }

    .btn-info:hover {
      background-color: var(--zafa-teal) !important;
      border-color: var(--zafa-teal) !important;
    }

    .btn-warning {
      background-color: var(--zafa-orange) !important;
      border-color: var(--zafa-orange) !important;
      color: white !important;
      font-weight: 600 !important;
      border-radius: 8px !important;
    }

    .btn-danger {
      border-radius: 8px !important;
      font-weight: 600 !important;
    }

    /* Footer */
    .main-footer {
      background-color: var(--zafa-light) !important;
      border-top: 2px solid var(--zafa-yellow) !important;
      color: var(--zafa-dark) !important;
    }

    .main-footer a {
      color: var(--zafa-orange) !important;
      font-weight: 600 !important;
      text-decoration: none !important;
    }

    .main-footer a:hover {
      color: var(--zafa-yellow) !important;
    }

    /* Breadcrumb */
    .breadcrumb {
      background-color: transparent !important;
      padding: 0 !important;
      margin-bottom: 0 !important;
    }

    .breadcrumb-item {
      color: #666 !important;
    }

    .breadcrumb-item.active {
      color: var(--zafa-dark) !important;
      font-weight: 600 !important;
    }

    .breadcrumb-item a {
      color: var(--zafa-orange) !important;
      text-decoration: none !important;
    }

    .breadcrumb-item a:hover {
      color: var(--zafa-yellow) !important;
    }

    /* Scrollbar Styling */
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
      margin-right: 10px !important;
    }

    /* Ensure dropdown menus are clickable */
    .nav-sidebar .has-treeview > .nav-link,
    .nav-treeview .nav-link {
      pointer-events: auto !important;
      user-select: none !important;
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
      transform: translateY(-50%) rotate(-90deg) !important;
    }

    /* Mobile Responsive */
    @media (max-width: 767.98px) {
      .main-sidebar {
        width: 250px !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        bottom: 0 !important;
        z-index: 1050 !important;
        transform: translateX(-100%) !important;
        transition: transform 0.3s ease-in-out !important;
      }

      /* Show sidebar when not collapsed */
      body:not(.sidebar-collapse) .main-sidebar,
      body.sidebar-open .main-sidebar {
        transform: translateX(0) !important;
      }

      /* Content wrapper full width on mobile */
      .content-wrapper,
      .main-footer {
        margin-left: 0 !important;
      }

      /* Sidebar overlay */
      .sidebar-overlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background: rgba(0, 0, 0, 0.5) !important;
        z-index: 1040 !important;
        display: block !important;
      }

      /* Hide overlay when sidebar collapsed */
      body.sidebar-collapse .sidebar-overlay {
        display: none !important;
      }
      
      .nav-treeview > .nav-item > .nav-link {
        font-size: 0.85rem !important;
        padding-left: 45px !important;
      }

      /* FIX: Pushmenu button tetap visible di mobile */
      .navbar-nav .nav-link[data-widget="pushmenu"] {
        z-index: 10000 !important;
      }

      /* CRITICAL: Ensure dropdown menus work on mobile */
      .nav-sidebar .has-treeview > .nav-link {
        pointer-events: auto !important;
        touch-action: manipulation !important;
      }

      .nav-treeview {
        pointer-events: auto !important;
      }

      .nav-treeview > .nav-item > .nav-link {
        pointer-events: auto !important;
        touch-action: manipulation !important;
        min-height: 44px !important;
        display: flex !important;
        align-items: center !important;
      }

      /* MOBILE: Ensure sidebar completely hidden when collapsed */
      body.sidebar-collapse .main-sidebar,
      body.sidebar-mini.sidebar-collapse .main-sidebar {
        transform: translateX(-100%) !important;
        width: 250px !important;
        visibility: hidden !important;
      }

      /* MOBILE: Show sidebar when opened */
      body:not(.sidebar-collapse) .main-sidebar,
      body.sidebar-open .main-sidebar {
        transform: translateX(0) !important;
        visibility: visible !important;
      }

      /* MOBILE: Hide any floating submenu */
      .nav-treeview {
        position: relative !important;
        left: auto !important;
        top: auto !important;
        box-shadow: none !important;
        background: transparent !important;
      }
    }

    /* Sidebar Collapsed State - DESKTOP ONLY */
    @media (min-width: 768px) {
      .sidebar-mini.sidebar-collapse .main-sidebar {
        width: 4.6rem !important;
      }

      .sidebar-mini.sidebar-collapse .nav-sidebar > .nav-item > .nav-link p,
      .sidebar-mini.sidebar-collapse .brand-text {
        display: none !important;
      }

      .sidebar-mini.sidebar-collapse .nav-sidebar > .nav-item > .nav-link {
        width: calc(4.6rem - 0.5rem) !important;
        text-align: center !important;
        padding: 0.8rem 0 !important;
        margin: 0.25rem auto !important;
      }

      .sidebar-mini.sidebar-collapse .nav-sidebar > .nav-item > .nav-link .nav-icon {
        margin-right: 0 !important;
        font-size: 1.2rem !important;
      }

      /* Hide submenu in collapsed state by default */
      .sidebar-mini.sidebar-collapse .nav-treeview {
        display: none !important;
      }

      /* FLOATING SUBMENU ON HOVER - Show with proper styling */
      .sidebar-mini.sidebar-collapse .nav-sidebar .has-treeview:hover > .nav-treeview {
        display: block !important;
        position: fixed !important;
        left: 4.6rem !important;
        background: #ffffff !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2) !important;
        border-radius: 8px !important;
        padding: 8px 0 !important;
        min-width: 220px !important;
        z-index: 1060 !important;
        border: 1px solid #e0e0e0 !important;
      }

      /* CRITICAL: Show text labels in floating submenu */
      .sidebar-mini.sidebar-collapse .nav-sidebar .has-treeview:hover > .nav-treeview .nav-link p {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        margin-left: 0 !important;
        white-space: nowrap !important;
      }

      /* Submenu item styling in floating mode */
      .sidebar-mini.sidebar-collapse .nav-sidebar .has-treeview:hover > .nav-treeview .nav-link {
        display: flex !important;
        align-items: center !important;
        width: auto !important;
        padding: 10px 20px !important;
        margin: 2px 8px !important;
        text-align: left !important;
        color: #333 !important;
        border-radius: 4px !important;
      }

      .sidebar-mini.sidebar-collapse .nav-sidebar .has-treeview:hover > .nav-treeview .nav-link:hover {
        background-color: #f5f5f5 !important;
        color: var(--zafa-turquoise, #17a2b8) !important;
      }

      .sidebar-mini.sidebar-collapse .nav-sidebar .has-treeview:hover > .nav-treeview .nav-icon {
        margin-right: 10px !important;
        font-size: 0.6rem !important;
        color: var(--zafa-turquoise, #17a2b8) !important;
      }

      /* Keep submenu visible when hovering over it */
      .sidebar-mini.sidebar-collapse .nav-treeview:hover {
        display: block !important;
      }

      /* Hide arrow in collapsed state */
      .sidebar-mini.sidebar-collapse .nav-sidebar .has-treeview > .nav-link .right {
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
    }

    /* CRITICAL FIX: Ensure pushmenu always clickable in all states */
    body.sidebar-mini.sidebar-collapse .navbar-nav .nav-link[data-widget="pushmenu"],
    body.sidebar-mini .navbar-nav .nav-link[data-widget="pushmenu"] {
      pointer-events: auto !important;
      visibility: visible !important;
      display: inline-flex !important;
      z-index: 99999 !important;
      opacity: 1 !important;
    }

    /* CRITICAL: Icon inside pushmenu must always be visible */
    body.sidebar-mini.sidebar-collapse .navbar-nav .nav-link[data-widget="pushmenu"] i,
    body.sidebar-mini .navbar-nav .nav-link[data-widget="pushmenu"] i,
    body .navbar-nav .nav-link[data-widget="pushmenu"] i,
    .navbar-nav .nav-link[data-widget="pushmenu"] i.pushmenu-icon {
      display: block !important;
      visibility: visible !important;
      opacity: 1 !important;
      font-size: 1.3rem !important;
      color: var(--zafa-dark) !important;
    }

    /* Prevent any CSS from hiding the icon */
    .navbar-nav .nav-link[data-widget="pushmenu"] * {
      visibility: visible !important;
      opacity: 1 !important;
    }

    /* Prevent any overlay from blocking pushmenu */
    .navbar-nav .nav-item:first-child {
      z-index: 10000 !important;
      position: relative !important;
    }

    /* Ensure all menu items are clickable */
    .nav-sidebar .nav-item,
    .nav-sidebar .nav-link {
      pointer-events: auto !important;
    }
  </style>

  @stack('css')
  @stack('styles')
</head>
<body class="hold-transition sidebar-mini">
<!-- Flash Messages Meta Tags for AlertHelper -->
@if(session('alert_success'))
    <meta name="flash-success" content="{{ session('alert_success') }}">
@endif
@if(session('alert_error'))
    <meta name="flash-error" content="{{ session('alert_error') }}">
@endif
@if(session('alert_warning'))
    <meta name="flash-warning" content="{{ session('alert_warning') }}">
@endif
@if(session('alert_info'))
    <meta name="flash-info" content="{{ session('alert_info') }}">
@endif

<!-- Site wrapper -->
<div class="wrapper">
  <!-- Navbar -->
  @include('layouts.header')
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{url('/')}}" class="brand-link">
      <img src="{{asset('adminlte/dist/img/Zlogo.png')}}" alt="Zafa Logo" class="brand-image img-circle elevation-3" style="opacity: .9">
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
<!-- Alert Helper JS -->
<script src="{{asset('js/alert-helper.js')}}"></script>
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

<script>
    // MOBILE RESPONSIVE FIX
  $(document).ready(function() {
    // CRITICAL FIX: Force pushmenu icon to be visible
    function ensurePushmenuVisible() {
      var $pushmenu = $('[data-widget="pushmenu"]');
      var $icon = $pushmenu.find('i.pushmenu-icon');
      
      $pushmenu.css({
        'pointer-events': 'auto',
        'z-index': '99999',
        'position': 'relative',
        'display': 'inline-flex',
        'visibility': 'visible',
        'opacity': '1'
      });
      
      if ($icon.length === 0) {
        $pushmenu.html('<i class="fas fa-chevron-left pushmenu-icon" style="display: block !important; visibility: visible !important; opacity: 1 !important; font-size: 1.3rem !important;"></i>');
        $icon = $pushmenu.find('i.pushmenu-icon');
      }

      $icon.css({
        'display': 'block',
        'visibility': 'visible',
        'opacity': '1',
        'font-size': '1.3rem'
      });
      
      syncPushmenuIcon();
    }

    function syncPushmenuIcon() {
      var $pushmenu = $('[data-widget="pushmenu"]');
      var $icon = $pushmenu.find('i.pushmenu-icon');

      if ($icon.length === 0) {
        return;
      }

      var isCollapsed = $('body').hasClass('sidebar-collapse');
      $icon.removeClass('fa-chevron-left fa-chevron-right')
           .addClass(isCollapsed ? 'fa-chevron-right' : 'fa-chevron-left');
    }
    
    // Run immediately
    ensurePushmenuVisible();
    
    // Run after a short delay to override any conflicting CSS
    setTimeout(ensurePushmenuVisible, 100);
    setTimeout(ensurePushmenuVisible, 500);

    // Mobile detection
    function isMobile() {
      return window.innerWidth < 768;
    }

    // Check if sidebar is collapsed
    function isSidebarCollapsed() {
      return $('body').hasClass('sidebar-collapse');
    }

    // RESPONSIVE: Auto-collapse sidebar on mobile
    function handleResponsive() {
      if (isMobile()) {
        // On mobile, start with collapsed sidebar
        if (!$('body').hasClass('sidebar-collapse')) {
          $('body').addClass('sidebar-collapse sidebar-closed sidebar-mini');
        }
      } else {
        // On desktop, remove mobile-specific classes
        $('body').removeClass('sidebar-closed');
      }

      syncPushmenuIcon();
    }
    
    // Run on load
    handleResponsive();
    
    // Run on resize with debounce
    let resizeTimer;
    $(window).on('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(handleResponsive, 250);
    });

    // Enhanced pushmenu click handler for mobile
    $('[data-widget="pushmenu"]').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      if (isMobile()) {
        // Mobile behavior: toggle with overlay
        if ($('body').hasClass('sidebar-collapse')) {
          $('body').removeClass('sidebar-collapse');
          $('body').addClass('sidebar-open');
          // Add overlay
          if (!$('.sidebar-overlay').length) {
            $('<div class="sidebar-overlay"></div>').appendTo('body');
          }
        } else {
          $('body').addClass('sidebar-collapse');
          $('body').removeClass('sidebar-open');
          $('.sidebar-overlay').remove();
        }
      } else {
        // Desktop behavior: normal toggle
        $('body').toggleClass('sidebar-collapse');
        
        // CRITICAL FIX: Close all dropdowns when collapsing sidebar
        if ($('body').hasClass('sidebar-collapse')) {
          $('.nav-sidebar .has-treeview.menu-open').removeClass('menu-open');
          $('.nav-treeview').slideUp(200);
        }
      }

      syncPushmenuIcon();
    });

    // Close sidebar when clicking overlay on mobile
    $(document).on('click', '.sidebar-overlay', function() {
      $('body').addClass('sidebar-collapse');
      $('body').removeClass('sidebar-open');
      $(this).remove();
    });

    // Close sidebar when clicking outside on mobile (but not on menu items)
    $(document).on('click', function(e) {
      if (isMobile() && !$('body').hasClass('sidebar-collapse')) {
        if (!$(e.target).closest('.main-sidebar').length &&
            !$(e.target).closest('[data-widget="pushmenu"]').length) {
          $('body').addClass('sidebar-collapse');
          $('body').removeClass('sidebar-open');
          $('.sidebar-overlay').remove();
        }
      }
    });

    // CRITICAL FIX: Handle dropdown menu clicks in sidebar
    // Prevent sidebar from closing when clicking dropdown menu
    $('.main-sidebar').on('click', function(e) {
      e.stopPropagation();
    });

    // CRITICAL FIX: Initialize AdminLTE Treeview for dropdown menus
    // This ensures dropdown menus work properly
    if ($.fn.Treeview) {
      $('[data-widget="treeview"]').Treeview('init');
    }

    // ENHANCED: Manual treeview toggle for better control
    $('.nav-sidebar .has-treeview > a').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      var $parent = $(this).parent();
      var $treeview = $parent.find('> .nav-treeview');
      
      // CRITICAL FIX: Don't allow dropdown in collapsed mode on desktop
      if (!isMobile() && isSidebarCollapsed()) {
        // Expand sidebar first, then open menu
        $('body').removeClass('sidebar-collapse');
        
        // Wait for sidebar animation, then open menu
        setTimeout(function() {
          if (!$parent.hasClass('menu-open')) {
            // Close other open menus
            $('.nav-sidebar .has-treeview.menu-open').not($parent).each(function() {
              $(this).removeClass('menu-open');
              $(this).find('> .nav-treeview').slideUp(300);
            });
            
            // Open clicked menu
            $parent.addClass('menu-open');
            $treeview.slideDown(300);
          }
        }, 300);
        return;
      }
      
      // Toggle menu-open class
      if ($parent.hasClass('menu-open')) {
        $parent.removeClass('menu-open');
        $treeview.slideUp(300);
      } else {
        // Close other open menus (accordion behavior)
        $('.nav-sidebar .has-treeview.menu-open').not($parent).each(function() {
          $(this).removeClass('menu-open');
          $(this).find('> .nav-treeview').slideUp(300);
        });
        
        // Open clicked menu
        $parent.addClass('menu-open');
        $treeview.slideDown(300);
      }
    });

    // Prevent submenu links from toggling parent menu
    $('.nav-treeview .nav-link').on('click', function(e) {
      e.stopPropagation();
      // Allow normal navigation
    });

    // CRITICAL FIX: Hover behavior for collapsed sidebar on desktop
    if (!isMobile()) {
      console.log('Setting up hover behavior for collapsed sidebar');
      
      // Remove any existing hover handlers first
      $('.sidebar-mini.sidebar-collapse .nav-sidebar .has-treeview').off('mouseenter mouseleave');
      
      // Add new hover handlers with event delegation
      $(document).on('mouseenter', '.sidebar-mini.sidebar-collapse .nav-sidebar .has-treeview', function() {
        if (!$('body').hasClass('sidebar-collapse')) return;
        
        console.log('Hovering over menu item in collapsed mode');
        
        var $this = $(this);
        var $submenu = $this.find('> .nav-treeview');
        var offset = $this.offset();
        
        console.log('Submenu found:', $submenu.length);
        console.log('Offset:', offset);
        
        // Show submenu as floating menu
        $submenu.css({
          'position': 'fixed',
          'left': '4.6rem',
          'top': offset.top + 'px',
          'background': 'white',
          'box-shadow': '0 4px 20px rgba(0,0,0,0.2)',
          'border-radius': '8px',
          'padding': '8px 0',
          'min-width': '220px',
          'z-index': '1060',
          'display': 'block',
          'border': '1px solid #e0e0e0'
        });
        
        console.log('Submenu displayed');
        
        // Adjust submenu links styling for floating mode
        $submenu.find('.nav-link').css({
          'padding': '10px 20px',
          'margin': '2px 8px',
          'white-space': 'nowrap'
        });
      });
      
      $(document).on('mouseleave', '.sidebar-mini.sidebar-collapse .nav-sidebar .has-treeview', function() {
        if (!$('body').hasClass('sidebar-collapse')) return;
        
        console.log('Mouse leaving menu item');
        
        var $submenu = $(this).find('> .nav-treeview');
        
        // Delay hiding to allow moving mouse to submenu
        setTimeout(function() {
          if (!$submenu.is(':hover')) {
            console.log('Hiding submenu');
            $submenu.css('display', 'none');
          }
        }, 100);
      });
      
      // Keep submenu visible when hovering over it
      $(document).on('mouseenter', '.sidebar-mini.sidebar-collapse .nav-treeview', function() {
        console.log('Hovering over submenu');
        $(this).css('display', 'block');
      });
      
      $(document).on('mouseleave', '.sidebar-mini.sidebar-collapse .nav-treeview', function() {
        console.log('Mouse leaving submenu');
        $(this).css('display', 'none');
      });
    }

    // Make DataTables responsive by default
    if ($.fn.DataTable) {
      $.extend(true, $.fn.dataTable.defaults, {
        responsive: true,
        autoWidth: false,
        scrollX: true,
        language: {
          search: "Cari:",
          lengthMenu: "Tampilkan _MENU_ data",
          info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
          infoEmpty: "Tidak ada data",
          infoFiltered: "(difilter dari _MAX_ total data)",
          zeroRecords: "Tidak ada data yang cocok",
          paginate: {
            first: "Pertama",
            last: "Terakhir",
            next: "Selanjutnya",
            previous: "Sebelumnya"
          }
        }
      });
    }

    // Fix touch events on mobile
    if ('ontouchstart' in window) {
      $('body').addClass('touch-device');
    }
  });
</script>

@stack('js')
@stack('scripts')
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
