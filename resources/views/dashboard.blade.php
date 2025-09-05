@extends('layouts.app') {{-- This tells Laravel to use your new layout --}}

@section('content') {{-- This puts the following HTML into the @yield('content') section --}}
    
    <h1>Welcome, {{ $u->username }}!</h1>
    <p>This is your main dashboard content area.</p>

    {{-- You can now add the dashboard-specific HTML from the previous step here --}}
    
    {{-- Example from before:
    <h2>Recent Notes</h2>
    <div class="notes-grid">
        ...
    </div>
    --}}

@endsection