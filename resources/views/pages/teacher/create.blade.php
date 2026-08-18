<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Upload New Lecture - Reviso</title>

    <!-- Favicon -->
    <link href="{{ asset('assets/img/brand/favicon.png') }}" rel="icon" type="image/png">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">

    <!-- Icons -->
    <link href="{{ asset('assets/js/plugins/nucleo/css/nucleo.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet" />

    <!-- CSS Files -->
    <link href="{{ asset('assets/css/argon-dashboard.css?v=1.1.2') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet" />
</head>

<body class="bg-light">

    <!-- Sidebar/Navbar -->
    <nav class="navbar navbar-vertical fixed-left navbar-expand-md navbar-light bg-white" id="sidenav-main">
        <div class="container-fluid">
            <!-- Toggler -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidenav-collapse-main"
                    aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Brand -->
            <a class="navbar-brand pt-0" href="{{ route('dashboard') }}">
                <img src="{{ asset('assets/img/brand/blue.png') }}" class="navbar-brand-img" alt="Reviso">
            </a>
            <!-- Collapse -->
            <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                @include('reusable.navbarTeacher')
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div class="main-content" id="panel">

        <!-- Page header -->
        <div class="header pb-6 bg-gradient-primary">
            <div class="container-fluid">
                <div class="header-body">
                    <div class="row align-items-center py-4">
                        <div class="col-lg-6 col-7">
                            <h6 class="h2 text-white d-inline-block mb-0">Upload New Lecture</h6>
                            <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-home"></i></a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('lectures') }}">My Lectures</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">New Lecture</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page content -->
        <div class="container-fluid mt--6">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <!-- Upload form -->
                    <form method="POST" action="/lectures" enctype="multipart/form-data" class="bg-white p-4 shadow-sm rounded">
                        @csrf

                        <!-- Success / error messages -->
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                @foreach ($errors->all() as $error)
                                    {{ $error }}<br>
                                @endforeach
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <!-- Title -->
                        <div class="form-group">
                            <label class="form-control-label" for="title">Lecture Title</label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   class="form-control @error('title') is-invalid @enderror"
                                   placeholder="e.g. Introduction to Cognitive Psychology"
                                   value="{{ old('title') }}"
                                   required>
                            @error('title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label class="form-control-label" for="description">Description / Notes</label>
                            <textarea id="description" 
                                      name="description" 
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="4"
                                      placeholder="Optional: brief overview or instructions for students">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- File upload -->
                        <div class="form-group">
                            <label class="form-control-label" for="lecture_file">Lecture File</label>
                            <div class="custom-file">
                                <input type="file" 
                                       id="lecture_file" 
                                       name="lecture_file" 
                                       class="custom-file-input @error('lecture_file') is-invalid @enderror"
                                       accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.png,.gif"
                                       required>
                                <label class="custom-file-label" for="lecture_file">Choose file or drag & drop</label>
                            </div>
                            <small class="form-text text-muted">
                                Allowed: PDF, Word (.doc/.docx), PowerPoint (.ppt/.pptx), JPG, PNG, GIF<br>
                                Max size: 25 MB
                            </small>
                            @error('lecture_file')
                                <span class="text-danger small d-block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Submit -->
                        <div class="text-right mt-4">
                            <a href="{{ route('lectures') }}" class="btn btn-secondary mr-3">Cancel</a>
                            <button type="submit" class="btn btn-primary">Upload Lecture</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/argon-dashboard.min.js?v=1.1.2') }}"></script>

    <!-- Optional: Custom file input label update -->
    <script>
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            let fileName = e.target.files[0]?.name || 'Choose file';
            e.target.nextElementSibling.innerText = fileName;
        });
    </script>
</body>
</html>


