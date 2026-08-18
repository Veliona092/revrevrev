<!-- resources/views/pages/teacher/lectures-edit.blade.php -->

@extends('layouts.appTeach')

@section('content')
    <div class="container">
        <h1>Edit Lecture: {{ $lecture->title }}</h1>

        <form method="POST" action="{{ route('teacher.update', $lecture->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title', $lecture->title) }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                          rows="5">{{ old('description', $lecture->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="lecture_file" class="form-label">Replace File (optional)</label>
                <input type="file" name="lecture_file" id="lecture_file" class="form-control @error('lecture_file') is-invalid @enderror">
                @error('lecture_file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small>Current file: {{ $lecture->file_name }} ({{ number_format($lecture->file_size / 1024, 1) }} KB)</small>
            </div>

            <button type="submit" class="btn btn-primary">Update Lecture</button>
            <a href="{{ route('lectures') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection

