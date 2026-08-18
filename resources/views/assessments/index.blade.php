@extends('layouts.app')

@section('content')
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-100 p-6">
        <h2 class="text-lg font-semibold mb-4">Assessments</h2>
        <ul>
            <li class="mb-2">Exam 1</li>
        </ul>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col">
        <!-- Top bar -->
        <header class="flex items-center bg-white border-b px-6 py-3">
            <img src="{{ asset('images/RevisoLogo.png') }}" alt="Logo" class="h-8 mr-3">
            <span class="text-gray-700">Placeholder</span>
        </header>

        <!-- Page body -->
        <main class="p-6">
            <h1 class="text-2xl font-bold mb-4">Assessment List</h1>
            <p>Exam details go here...</p>
        </main>
    </div>
</div>
@endsection
