@extends('layouts.domain')

@section('content')
<div class="container-fluid py-4">
    <h4 class="mb-4">{{ $class->name }} - Modules</h4>

    <!-- Overall Progress -->
    <div class="card mb-4">
        <div class="card-body">
            <h5>Overall Class Completion</h5>
            <div class="progress" style="height: 30px;">
                <div class="progress-bar" role="progressbar" style="width: {{ $overallCompletion }}%; background:#C17F24;" aria-valuenow="{{ $overallCompletion }}" aria-valuemin="0" aria-valuemax="100">
                    {{ $overallCompletion }}%
                </div>
            </div>
        </div>
    </div>

    <!-- Modules List -->
    <div class="row">
        @forelse($modules as $module)
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $module->title }}</h5>
                    <p class="card-text">{{ $module->description ?? 'No description' }}</p>

                    <!-- Progress Circle -->
                    <div class="text-center">
                        <div class="progress-circle" data-progress="{{ $progress[$module->id] ?? 0 }}">
                            <svg width="120" height="120">
                                <circle class="progress-circle-bg" cx="60" cy="60" r="50"></circle>
                                <circle class="progress-circle-fill" cx="60" cy="60" r="50" stroke-dasharray="314" stroke-dashoffset="{{ 314 - (314 * ($progress[$module->id] ?? 0) / 100) }}"></circle>
                            </svg>
                            <div class="progress-text">{{ $progress[$module->id] ?? 0 }}%</div>
                        </div>
                    </div>

                    <a href="#" class="btn mt-3" style="background:#C17F24;color:#fff;">Open Module</a>
                </div>
            </div>
        </div>
        @empty
        <p class="text-center text-muted">No modules yet in this class.</p>
        @endforelse
    </div>

    <!-- Announcements -->
    <div class="card mt-4">
        <div class="card-header">Announcements</div>
        <div class="card-body">
            @forelse($announcements as $announcement)
            <div class="alert alert-info">
                <small class="text-muted">{{ $announcement->created_at->diffForHumans() }} by {{ $announcement->user->idnumber }}</small>
                <p>{{ $announcement->message }}</p>
            </div>
            @empty
            <p class="text-muted">No announcements yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
    document.querySelectorAll('.progress-circle').forEach(circle => {
        const progress = circle.dataset.progress;
        circle.querySelector('.progress-text').textContent = progress + '%';
    });
    </script>
@endsection
