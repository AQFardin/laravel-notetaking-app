<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyNotes App</title>

    {{-- CSRF for AJAX (notes.js uses this) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Font Awesome for icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    {{-- Load CSS from public/css/app.css --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="app-container">
        <nav class="sidebar">
            <ul class="sidebar-top">
                <li>
                    <a href="{{ route('dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-grip"></i><span>Dashboard</span>
                    </a>
                </li>

                {{-- NOTES tab --}}
                <li>
                    <a href="{{ route('notes.index') }}" class="{{ request()->is('notes') ? 'active' : '' }}">
                        <i class="fas fa-sticky-note"></i><span>Notes</span>
                    </a>
                </li>

                

                <li><a href="#"><i class="fas fa-users"></i><span>Group</span></a></li>
                <li><a href="#"><i class="fas fa-flag"></i><span>Events</span></a></li>
                <li><a href="#"><i class="fas fa-calendar-alt"></i><span>Calendar</span></a></li>
            </ul>

            <ul class="sidebar-bottom">
                <li><a href="#"><i class="fas fa-user-circle"></i><span>Profile</span></a></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="icon-button">
                            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            @yield('content')
        </main>
    </div>
</body>
</html>