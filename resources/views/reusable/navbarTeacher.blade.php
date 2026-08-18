@php
  $teacherNavClassId = \App\Models\ClassModel::query()
      ->where('created_by', Auth::id())
      ->value('id');
@endphp

<ul class="navbar-nav">

  <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
       href="{{ route('dashboard') }}" style="font-size:18px;">
      <i class="ni ni-tv-2 text-char"></i> Dashboard
    </a>
  </li>

  <li class="nav-item {{ request()->routeIs('manageclass') ? 'active' : '' }}">
    <a class="nav-link {{ request()->routeIs('manageclass') ? 'active' : '' }}"
       href="{{ route('manageclass') }}" style="font-size:18px;">
      <i class="ni ni-bullet-list-67 text-char"></i> Class Management
    </a>
  </li>

  <li class="nav-item {{ request()->routeIs('student.performance') ? 'active' : '' }}">
    <a class="nav-link {{ request()->routeIs('student.performance') ? 'active' : '' }}"
       href="{{ $teacherNavClassId ? route('student.performance', ['class' => $teacherNavClassId]) : route('manageclass') }}"
       style="font-size:18px;">
      <i class="ni ni-single-copy-04 text-char"></i> Student Performance
    </a>
  </li>

  <li class="nav-item {{ request()->routeIs('mock-boards.batch.dashboard') || request()->routeIs('mock-boards.index') ? 'active' : '' }}">
    <a class="nav-link {{ request()->routeIs('mock-boards.batch.dashboard') || request()->routeIs('mock-boards.index') ? 'active' : '' }}"
       href="{{ route('mock-boards.batch.dashboard') }}"
       style="font-size:18px;">
      <i class="ni ni-books text-char"></i> Mock Boards
    </a>
  </li>

  <li class="nav-item {{ request()->routeIs('profile') ? 'active' : '' }}">
    <a class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}"
       href="{{ route('profile') }}" style="font-size:18px;">
      <i class="ni ni-single-02 text-char"></i> User Profile
    </a>
  </li>

  <li class="nav-item {{ request()->routeIs('chat.index') ? 'active' : '' }}">
    <a class="nav-link {{ request()->routeIs('chat.index') ? 'active' : '' }}"
       href="{{ route('chat.index') }}" style="font-size:18px;">
      <i class="ni ni-chat-round text-char"></i> Chat
    </a>
  </li>

  <li class="nav-item {{ request()->routeIs('users.search') ? 'active' : '' }}">
    <a class="nav-link {{ request()->routeIs('users.search') ? 'active' : '' }}"
       href="{{ route('users.search') }}" style="font-size:18px;">
      <i class="ni ni-zoom-split-in text-char"></i> User Search
    </a>
  </li>

</ul>