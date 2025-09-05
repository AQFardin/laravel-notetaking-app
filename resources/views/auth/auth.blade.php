<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth</title>
    @vite(['resources/css/auth.css','resources/js/auth.js'])
</head>

{{-- If there are username/email errors, start on register panel --}}
<body data-initial-mode="{{ ($errors->has('username') || $errors->has('email') || session('show_register')) ? 'register' : 'login' }}">
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo"><div class="logo-square"></div></div>
        <h2 id="formTitle">Sign In</h2>
        <p id="formSubtitle">Enter your credentials</p>
      </div>

      {{-- LOGIN FORM --}}
      <form class="login-form panel active" id="loginPanel" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
          <label for="email" class="form-label">Email or Username</label>
          <div class="input-wrapper">
            <input type="text" id="email" name="email" value="{{ old('email') }}" required autocomplete="username">
          </div>
          @if($errors->has('login'))
            <span class="error-message show" id="emailError">{{ $errors->first('login') }}</span>
          @else
            <span class="error-message" id="emailError"></span>
          @endif
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <div class="input-wrapper password-wrapper">
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
              <span class="toggle-text">SHOW</span>
            </button>
          </div>
          <span class="error-message" id="passwordError"></span>
        </div>

        <div class="form-options">
          <label class="checkbox-wrapper">
            <input type="checkbox" id="remember" name="remember">
            <span class="checkbox-label">
              <span class="checkbox-box"></span>
              Remember me
            </span>
          </label>
          <a href="#" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="login-btn" id="loginBtn">
          <span class="btn-text">SIGN IN</span>
          <div class="btn-loader">
            <div class="loader-bar"></div><div class="loader-bar"></div><div class="loader-bar"></div>
          </div>
        </button>

        @if(session('status'))
          <div class="success-message show" style="margin-top:8px">
            <div class="success-icon">✓</div>
            <h3>{{ session('status') }}</h3>
            <p>Proceed to login</p>
          </div>
        @endif
      </form>

      {{-- REGISTER FORM (Step 1) --}}
      <form class="login-form panel" id="registerPanel" method="POST" action="{{ route('register') }}">
        @csrf
        <div class="form-group">
          <label for="regUsername" class="form-label">Username</label>
          <div class="input-wrapper">
            <input type="text" id="regUsername" name="username" value="{{ old('username') }}" required autocomplete="username">
          </div>
          <span class="error-message {{ $errors->has('username') ? 'show' : '' }}" id="regUsernameError">
            {{ $errors->first('username') }}
          </span>
        </div>

        <div class="form-group">
          <label for="regEmail" class="form-label">Email</label>
          <div class="input-wrapper">
            <input type="email" id="regEmail" name="email" value="{{ old('email') }}" required autocomplete="email">
          </div>
          <span class="error-message {{ $errors->has('email') ? 'show' : '' }}" id="regEmailError">
            {{ $errors->first('email') }}
          </span>
        </div>

        <div class="form-group">
          <label for="regPassword" class="form-label">Password</label>
          <div class="input-wrapper password-wrapper">
            <input type="password" id="regPassword" name="password" required autocomplete="new-password">
            <button type="button" class="password-toggle" id="regPasswordToggle" aria-label="Toggle password visibility">
              <span class="toggle-text">SHOW</span>
            </button>
          </div>
          <span class="error-message {{ $errors->has('password') ? 'show' : '' }}" id="regPasswordError">
            {{ $errors->first('password') }}
          </span>
        </div>

        <button type="submit" class="login-btn" id="registerBtn">
          <span class="btn-text">CREATE ACCOUNT</span>
          <div class="btn-loader">
            <div class="loader-bar"></div><div class="loader-bar"></div><div class="loader-bar"></div>
          </div>
        </button>
      </form>

      {{-- bottom link --}}
      <div class="signup-link" id="switcher">
        <span id="switcherText">No account? </span>
        <a href="#" id="switcherLink">Create one</a>
      </div>
    </div>
  </div>
</body>
</html>
