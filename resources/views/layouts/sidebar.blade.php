<!-- resources/views/components/sidebar.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>@yield('title') - Reviso</title>

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
  <link href="{{ asset('assets/js/plugins/nucleo/css/nucleo.css') }}" rel="stylesheet" />
  <link href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet" />

  <style>
    /* Sidebar container */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 250px;
      background: #fff;
      border-right: 1px solid #e9ecef;
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .sidebar-header {
      padding: 1rem;
      text-align: center;
      border-bottom: 1px solid #e9ecef;
    }

    .sidebar-header img {
      max-height: 40px;
    }

    .sidebar-nav {
      list-style: none;
      margin: 0;
      padding: 0;
    }

    .sidebar-nav li {
      display: block;
    }

    .sidebar-nav a {
      display: flex;
      align-items: center;
      padding: 0.75rem 1rem;
      color: #525f7f;
      text-decoration: none;
      font-size: 0.95rem;
      transition: background 0.2s ease;
    }

    .sidebar-nav a:hover {
      background: #f6f9fc;
      border-radius: 4px;
    }

    .sidebar-nav a.active {
      background: #5e72e4;
      color: #fff;
      border-radius: 4px;
    }

    .sidebar-nav i {
      margin-right: 0.75rem;
      font-size: 1rem;
    }

    @media (max-width: 991px) {
      .sidebar {
        left: -250px;
      }
      .sidebar.open {
        left: 0;
      }
      .toggle-btn {
        display: block;
      }
    }

    .toggle-btn {
      position: fixed;
      top: 1rem;
      left: 1rem;
      background: #5e72e4;
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      cursor: pointer;
      border-radius: 4px;
      display: none;
      z-index: 1100;
    }

    .content {
      margin-left: 250px;
      padding: 2rem;
      transition: margin-left 0.3s ease;
    }

    @media (max-width: 991px) {
      .content {
        margin-left: 0;
      }
    }
  </style>
</head>
<body class="bg-light">
  <!-- Toggle button for mobile -->
  <button class="toggle-btn" onclick="toggleSidebar()">☰ Menu</button>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <a href="{{ route('dashboard') }}">
        <img src="{{ asset('assets/img/brand/RevisoLogo.png') }}" alt="Reviso">
      </a>
    </div>
    <ul class="sidebar-nav">
      <!-- Teacher-specific nav -->
      @include('reusable.navbarTeacher')

      <hr class="my-3">

      <h6 class="navbar-heading text-muted px-3">Documentation</h6>
      <li><a href="https://demos.creative-tim.com/argon-dashboard/docs/getting-started/overview.html" target="_blank"><i class="ni ni-spaceship"></i> Getting started</a></li>
      <li><a href="https://demos.creative-tim.com/argon-dashboard/docs/foundation/colors.html" target="_blank"><i class="ni ni-palette"></i> Foundation</a></li>
      <li><a href="https://demos.creative-tim.com/argon-dashboard/docs/components/alerts.html" target="_blank"><i class="ni ni-ui-04"></i> Components</a></li>
    </ul>
  </div>

  <!-- Page content -->
  <div class="content">
    @yield('content')
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
    }
  </script>
</body>
</html>
