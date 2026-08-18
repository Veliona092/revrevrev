<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') - Reviso</title>

    <!-- Icons - Font Awesome CDN (fixes fallback warning) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Bootstrap CSS (if needed separately) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets/js/plugins/nucleo/css/nucleo.css') }}" rel="stylesheet" />

    <!-- Argon Dashboard CSS -->
    <link href="{{ asset('assets/css/argon-dashboard.css?v=1.1.2') }}" rel="stylesheet" />

    <!-- TinyMCE 7 (modern, fixes old CDN issues) -->
        <!-- Self-hosted TinyMCE (no API key needed) -->
        <script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
    <!-- PDF.js - ONLY if you actually need PDF preview on this page -->
    <!-- If not needed, DELETE these two lines completely -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.0.379/pdf_viewer.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.0.379/pdf.min.js"></script> -->

    @yield('head')
</head>
<body class="bg-light">

    <!-- Sidebar / Vertical Navbar -->
    <nav class="navbar navbar-vertical fixed-left navbar-expand-md navbar-dark bg-mar" id="sidenav-main">
        <div class="container-fluid">
            <!-- Toggler (mobile) -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidenav-collapse-main"
                    aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Brand / Logo -->
            <a class="navbar-brand pt-0" href="{{ route('dashboard') }}">
                <img src="{{ asset('assets/img/brand/RevisoLogo.png') }}" class="navbar-brand-img" alt="Reviso">
            </a>

            <!-- Collapse content -->
            <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                <!-- Teacher-specific navigation -->
                @include('reusable.navbarTeacher')

                <hr class="my-3">

               
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="main-content" id="panel">

        <!-- Gradient Header -->
        <div class="header pb-6 bg-mar">
            <div class="container-fluid">
                <div class="header-body">
                    <div class="row align-items-center py-4">
                        <div class="col-lg-6 col-7">
                            <h6 class="h2 text-white d-inline-block mb-0">@yield('page-heading', 'Dashboard')</h6>
                            <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-home"></i></a></li>
                                    @yield('breadcrumb')
                                </ol>
                            </nav>
                        </div>
                        <div class="col-lg-6 col-5 text-right">
                            @yield('header-actions')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="container-fluid mt--6">
            @yield('content')
        </div>

    </div>

    <!-- Core JS -->
    <script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Optional plugins (Chart.js etc.) – include only if needed -->
    @yield('scripts-before-argon')

    <!-- Argon Dashboard JS -->
    <script src="{{ asset('assets/js/argon-dashboard.min.js?v=1.1.2') }}"></script>

    <!-- Page-specific scripts -->
    @yield('scripts')

</body>
</html>