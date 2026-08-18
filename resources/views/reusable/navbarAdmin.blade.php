<ul class="navbar-nav">
    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="ni ni-tv-2 text-primary"></i> Dashboard
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('admin.users') || request()->routeIs('users.search') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('admin.users') }}">
            <i class="ni ni-single-02 text-primary"></i>
            <span>User Management</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('admin.approvals') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('admin.approvals') }}">
            <i class="fas fa-user-check text-success"></i>
            <span>Approvals</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('admin.class-ai-settings') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('admin.class-ai-settings') }}">
            <i class="fas fa-sliders-h text-info"></i>
            <span>Class AI Settings</span>
        </a>
    </li>

    @if(Auth::user()?->role === 'superadmin')
    <li class="nav-item {{ request()->routeIs('superadmin.ai-settings') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('superadmin.ai-settings') }}">
            <i class="fas fa-brain text-warning"></i>
            <span>Global AI Settings</span>
        </a>
    </li>
    @endif

    @if(Auth::user()?->role === 'superadmin')
    <li class="nav-item {{ request()->routeIs('superadmin.admins') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('superadmin.admins') }}">
            <i class="fas fa-crown text-warning"></i>
            <span>Manage Admins</span>
        </a>
    </li>
    @endif
    
</ul>