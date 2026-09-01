   <ul class="navbar-nav">
          <li class="nav-item  active ">
      <a class="nav-link {{ request()->routeIs(patterns: 'dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" style="font-size:18px;">
                <i class="ni ni-tv-2 text-primary"></i> Dashboard
            </a>
          </li>

          <li class="nav-item {{ request()->routeIs('chat.index') ? 'active' : '' }}">
              <a class="nav-link {{ request()->routeIs('chat.index') ? 'active' : '' }}" href="{{ route('chat.index') }}" style="font-size:18px;">
                  <i class="ni ni-chat-round text-primary"></i> Chat
              </a>
          </li>

          <li class="nav-item {{ request()->routeIs('users.search') ? 'active' : '' }}">
              <a class="nav-link {{ request()->routeIs('users.search') ? 'active' : '' }}" href="{{ route('users.search') }}" style="font-size:18px;">
                  <i class="ni ni-zoom-split-in text-primary"></i> User Search
              </a>
          </li>

          <li class="nav-item">
    <a class="nav-link" href="{{ route('student.classes') }}" style="font-size:18px;">
        <i class="fas fa-book"></i> Lectures
    </a>
      </li>
          <li class="nav-item">
            <a class="nav-link " href="{{ route('progress') }}" style="font-size:18px;">
              <i class="ni ni-chart-bar-32 text-orange"></i> Progress Tracker
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link " href="{{ route('profile') }}"style="font-size:18px;">
              <i class="ni ni-single-02 text-yellow"></i> User profile
            </a>
          </li>
        
       
        </ul>
        <!-- Logout - Secure POST form -->
    

    