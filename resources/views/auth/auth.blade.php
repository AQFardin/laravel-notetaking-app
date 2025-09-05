<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Auth</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
  <script src="{{ asset('js/auth.js') }}" defer></script>
</head>

{{-- One file that supports both Login and Register --}}
<body
  data-initial-mode="{{ isset($mode) ? $mode : (($errors->has('username') || $errors->has('name') || session('show_register')) ? 'register' : 'login') }}"
>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo"><div class="logo-square"></div></div>
        <h2 id="formTitle">Sign In</h2>
        <p id="formSubtitle">Enter your credentials</p>
      </div>

      {{-- LOGIN PANEL --}}
      <form class="login-form panel active" id="loginPanel" method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="form-group @error('email') error @enderror">
          <label for="email" class="form-label">Email</label>
          <div class="input-wrapper">
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com">
          </div>
          <span class="error-message {{ $errors->has('email') ? 'show' : '' }}" id="emailError">@error('email'){{ $message }}@enderror</span>
        </div>

        <div class="form-group @error('password') error @enderror">
          <label for="password" class="form-label">Password</label>
          <div class="input-wrapper password-wrapper">
            <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
              <span class="toggle-text">SHOW</span>
            </button>
          </div>
          <span class="error-message {{ $errors->has('password') ? 'show' : '' }}" id="passwordError">@error('password'){{ $message }}@enderror</span>
        </div>

        <div class="form-options">
          <label class="checkbox-wrapper">
            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <span class="checkbox-label">
              <div class="checkbox-box"></div>
              <span>Remember me</span>
            </span>
          </label>

          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
          @endif
        </div>

        <button type="submit" class="login-btn" id="loginBtn">
          <span class="btn-text">SIGN IN</span>
          <div class="btn-loader">
            <div class="loader-bar"></div>
            <div class="loader-bar"></div>
            <div class="loader-bar"></div>
          </div>
        </button>
      </form>

      {{-- REGISTER PANEL (same page) --}}
      @if (Route::has('register'))
      <form class="login-form panel" id="registerPanel" method="POST" action="{{ route('register') }}" novalidate>
        @csrf
        

        <div class="form-group @error('username') error @enderror">
          <label for="username" class="form-label">Username</label>
          <div class="input-wrapper">
            <input type="text" id="username" name="username" value="{{ old('username') }}" required placeholder="yourhandle">
          </div>
          <span class="error-message {{ $errors->has('username') ? 'show' : '' }}" id="usernameError">@error('username'){{ $message }}@enderror</span>
        </div>

        <div class="form-group @error('email') error @enderror">
          <label for="regEmail" class="form-label">Email</label>
          <div class="input-wrapper">
            <input type="email" id="regEmail" name="email" value="{{ old('email') }}" required placeholder="you@example.com">
          </div>
          <span class="error-message {{ $errors->has('email') ? 'show' : '' }}" id="regEmailError">@error('email'){{ $message }}@enderror</span>
        </div>

        <div class="form-group @error('password') error @enderror">
          <label for="regPassword" class="form-label">Password</label>
          <div class="input-wrapper password-wrapper">
            <input type="password" id="regPassword" name="password" required placeholder="Create a password">
            <button type="button" class="password-toggle" id="regPasswordToggle" aria-label="Toggle password visibility">
              <span class="toggle-text">SHOW</span>
            </button>
          </div>
          <span class="error-message {{ $errors->has('password') ? 'show' : '' }}" id="regPasswordError">@error('password'){{ $message }}@enderror</span>
        </div>

        <div class="form-group @error('password_confirmation') error @enderror">
          <label for="password_confirmation" class="form-label">Confirm Password</label>
          <div class="input-wrapper">
            <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Confirm password">
          </div>
          <span class="error-message {{ $errors->has('password_confirmation') ? 'show' : '' }}" id="regConfirmError">@error('password_confirmation'){{ $message }}@enderror</span>
        </div>

        <button type="submit" class="login-btn" id="registerBtn">
          <span class="btn-text">Create Account</span>
          <div class="btn-loader">
            <div class="loader-bar"></div>
            <div class="loader-bar"></div>
            <div class="loader-bar"></div>
          </div>
        </button>
      </form>
      @endif

      {{-- Switcher below (same as your original behavior) --}}
      <div class="signup-link" style="margin-top:6px;">
        <span id="switcherText">No account? </span>
        <a href="#" id="switcherLink">Create one</a>
      </div>

      {{-- Success (kept for CSS hook) --}}
      <div class="success-message" id="successMessage">
        <div class="success-icon">✓</div>
        <h3>Success</h3>
        <p>Redirecting…</p>
      </div>
    </div>
  </div>
</body>
</html>
