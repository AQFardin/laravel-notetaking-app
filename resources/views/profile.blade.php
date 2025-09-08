@extends('layouts.app')

@section('content')
<div style="padding:16px; max-width:720px;">
  <h1 style="margin:0 0 12px;">Profile</h1>

  @if (session('status'))
    <div style="border:2px solid #000; background:#fff3bf; padding:10px; margin-bottom:12px;">
      {{ session('status') }}
    </div>
  @endif

  {{-- Account Details --}}
  <section style="border:3px solid #000; box-shadow:6px 6px 0 #000; background:#fff; margin-bottom:16px;">
    <div style="padding:10px; border-bottom:2px solid #000;"><strong>Account Details</strong></div>
    <form method="POST" action="{{ route('profile.update') }}" style="padding:12px;">
      @csrf

      <div style="display:grid; grid-template-columns:160px 1fr; gap:10px; align-items:center; margin-bottom:8px;">
        <label style="font-weight:900;">Username</label>
        <input type="text" value="{{ $user->username }}" readonly
               style="border:2px solid #000; padding:8px 10px; background:#f3f3f3;">
      </div>

      <div style="display:grid; grid-template-columns:160px 1fr; gap:10px; align-items:center; margin-bottom:8px;">
        <label style="font-weight:900;">Full name</label>
        <input name="full_name" type="text" value="{{ old('full_name', $user->full_name) }}"
               style="border:2px solid #000; padding:8px 10px;">
      </div>

      <div style="display:grid; grid-template-columns:160px 1fr; gap:10px; align-items:center; margin-bottom:8px;">
        <label style="font-weight:900;">Email</label>
        <input name="email" type="email" value="{{ old('email', $user->email) }}"
               style="border:2px solid #000; padding:8px 10px;">
      </div>

      <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:10px;">
        <button class="btn-base" style="border:2px solid #000; background:#000; color:#fff; padding:8px 12px;">Save</button>
      </div>
    </form>
  </section>

  {{-- Change Password --}}
  <section style="border:3px solid #000; box-shadow:6px 6px 0 #000; background:#fff;">
    <div style="padding:10px; border-bottom:2px solid #000;"><strong>Change Password</strong></div>
    <form method="POST" action="{{ route('profile.password') }}" style="padding:12px;">
      @csrf
      <div style="display:grid; grid-template-columns:200px 1fr; gap:10px; align-items:center; margin-bottom:8px;">
        <label style="font-weight:900;">Current password</label>
        <input name="current_password" type="password" required
               style="border:2px solid #000; padding:8px 10px;">
      </div>

      <div style="display:grid; grid-template-columns:200px 1fr; gap:10px; align-items:center; margin-bottom:8px;">
        <label style="font-weight:900;">New password</label>
        <input name="password" type="password" required
               style="border:2px solid #000; padding:8px 10px;">
      </div>

      <div style="display:grid; grid-template-columns:200px 1fr; gap:10px; align-items:center; margin-bottom:8px;">
        <label style="font-weight:900;">Confirm new password</label>
        <input name="password_confirmation" type="password" required
               style="border:2px solid #000; padding:8px 10px;">
      </div>

      <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:10px;">
        <button class="btn-base" style="border:2px solid #000; background:#000; color:#fff; padding:8px 12px;">Update Password</button>
      </div>
    </form>
  </section>
</div>
@endsection
