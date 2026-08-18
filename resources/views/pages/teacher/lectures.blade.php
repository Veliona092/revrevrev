<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Lectures - Reviso</title>

    <!-- Favicon -->
    <link href="{{ asset('assets/img/brand/favicon.png') }}" rel="icon" type="image/png">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">

    <!-- Icons -->
    <link href="{{ asset('assets/js/plugins/nucleo/css/nucleo.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet" />

    <!-- CSS Files -->
    <link href="{{ asset('assets/css/argon-dashboard.css?v=1.1.2') }}" rel="stylesheet" />
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
                <img src="{{ asset('assets/img/brand/RevisoLogo.png') }}" class="navbar-brand-img" alt="Reviso">
            </a>
            <!-- Collapse -->
            <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                <!-- Nav items -->
                @include('reusable.navbarTeacher')
                <!-- Divider -->
                <hr class="my-3">
                <!-- Heading -->
                <h6 class="navbar-heading text-muted">Documentation</h6>
                <!-- Extra links -->
                <ul class="navbar-nav mb-md-3">
                    <li class="nav-item">
                        <a class="nav-link" href="https://demos.creative-tim.com/argon-dashboard/docs/getting-started/overview.html">
                            <i class="ni ni-spaceship"></i> Getting started
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://demos.creative-tim.com/argon-dashboard/docs/foundation/colors.html">
                            <i class="ni ni-palette"></i> Foundation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://demos.creative-tim.com/argon-dashboard/docs/components/alerts.html">
                            <i class="ni ni-ui-04"></i> Components
                        </a>
                    </li>
                </ul>
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
                            <h6 class="h2 text-white d-inline-block mb-0">My Lectures</h6>
                            <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-home"></i></a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Lectures</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-lg-6 col-5 text-right">
                            <a href="{{ route('teacher.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i> Upload New Lecture
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page content -->
        <div class="container-fluid mt--6">
            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <!-- Main content container (no card) -->
                    <div class="bg-white p-4 shadow-sm rounded">

                        <!-- Success / status messages -->
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

                        <!-- Lectures table -->
                        @if ($lectures->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table align-items-center table-flush">
                                    <thead class="thead-light">
                                        <tr>
                                            <th scope="col">Title</th>
                                            <th scope="col">Uploaded</th>
                                            <th scope="col">File Type</th>
                                            <th scope="col">Size</th>
                                            <th scope="col" class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lectures as $lecture)
                                            <tr>
                                                <td>
                                                    <strong>{{ $lecture->title }}</strong>
                                                    @if ($lecture->description)
                                                        <small class="d-block text-muted">{{ Str::limit($lecture->description, 80) }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $lecture->uploaded_at->diffForHumans() }}</td>
                                                <td>
                                                    <span class="badge badge-soft-{{ $lecture->file_type === 'pdf' ? 'success' : 'primary' }}">
                                                        {{ strtoupper($lecture->file_type) }}
                                                    </span>
                                                </td>
                                                <td>{{ $lecture->file_size_human }}</td>
                                                <td class="text-right">
                                                    <a href="{{ $lecture->file_url }}" class="btn btn-sm btn-neutral" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('teacher.edit', $lecture->id) }}" class="btn btn-sm btn-neutral">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('teacher.destroy', $lecture->id) }}" method="POST" class="d-inline lecture-delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-neutral text-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-4">
                                {{ $lectures->links() }}
                            </div>
                        @else
                            <!-- Empty state -->
                            <div class="text-center py-8">
                                <i class="fas fa-folder-open fa-4x text-muted mb-4"></i>
                                <h4 class="mb-2">No lectures uploaded yet</h4>
                                <p class="text-muted mb-4">Start by uploading your first lecture.</p>
                                <a href="{{ route('teacher.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus mr-2"></i> Upload New Lecture
                                </a>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteLectureConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteLectureConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteLectureConfirmTitle">Confirm Lecture Deletion</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Delete this lecture? This cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmLectureDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Optional JS -->
    <script src="{{ asset('assets/js/plugins/chart.js/dist/Chart.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/chart.js/dist/Chart.extension.js') }}"></script>

    <!-- Argon JS -->
    <script src="{{ asset('assets/js/argon-dashboard.min.js?v=1.1.2') }}"></script>
    <script>
        (function () {
            let pendingDeleteForm = null;

            $('.lecture-delete-form').on('submit', function (e) {
                e.preventDefault();
                pendingDeleteForm = this;
                $('#deleteLectureConfirmModal').modal('show');
            });

            $('#confirmLectureDeleteBtn').on('click', function () {
                if (pendingDeleteForm) {
                    pendingDeleteForm.submit();
                }
            });

            $('#deleteLectureConfirmModal').on('hidden.bs.modal', function () {
                pendingDeleteForm = null;
            });
        })();
    </script>
</body>
</html>


