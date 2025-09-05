<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account</title>
  @vite(['resources/css/auth.css','resources/js/auth.js'])
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo"><div class="logo-square"></div></div>
        <h2>Create Account</h2>
        <p>Fill your details</p>
      </div>

      <form class="login-form" id="registerForm" method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="form-group @error('name') error @enderror">
          <label for="name" class="form-label">Full Name</label>
          <div class="input-wrapper">
            <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Your Name">
          </div>
          <span class="error-message {{ $errors->has('name') ? 'show' : '' }}" id="nameError">
            @error('name') {{ $message }} @enderror
          </span>
        </div>

        <div class="form-group @error('username') error @enderror">
          <label for="username" class="form-label">Username</label>
          <div class="input-wrapper">
            <input type="text" id="username" name="username" value="{{ old('username') }}" required placeholder="yourhandle">
          </div>
          <span class="error-message {{ $errors->has('username') ? 'show' : '' }}" id="usernameError">
            @error('username') {{ $message }} @enderror
          </span>
        </div>

        <div class="form-group @error('email') error @enderror">
          <label for="regEmail" class="form-label">Email</label>
          <div class="input-wrapper">
            <input type="email" id="regEmail" name="email" value="{{ old('email') }}" required placeholder="you@example.com">
          </div>
          <span class="error-message {{ $errors->has('email') ? 'show' : '' }}" id="regEmailError">
            @error('email') {{ $message }} @enderror
          </span>
        </div>

        <div class="form-group @error('password') error @enderror">
          <label for="regPassword" class="form-label">Password</label>
          <div class="input-wrapper password-wrapper">
            <input type="password" id="regPassword" name="password" required placeholder="Create a password">
            <button type="button" class="password-toggle" id="regPasswordToggle" aria-label="Toggle password visibility">
              <span class="toggle-text">SHOW</span>
            </button>
          </div>
          <span class="error-message {{ $errors->has('password') ? 'show' : '' }}" id="regPasswordError">
            @error('password') {{ $message }} @enderror
          </span>
        </div>

        <div class="form-group @error('password_confirmation') error @enderror">
          <label for="password_confirmation" class="form-label">Confirm Password</label>
          <div class="input-wrapper">
            <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Confirm password">
          </div>
          <span class="error-message {{ $errors->has('password_confirmation') ? 'show' : '' }}" id="regConfirmError">
            @error('password_confirmation') {{ $message }} @enderror
          </span>
        </div>

        <button type="submit" class="login-btn" id="registerSubmitBtn">
          <span class="btn-text">Create Account</span>
          <div class="btn-loader">
            <div class="loader-bar"></div>
            <div class="loader-bar"></div>
            <div class="loader-bar"></div>
          </div>
        </button>
      </form>

      <div class="signup-link">
        <span>Already have an account? </span>
        <a href="{{ route('login') }}">Sign in</a>
      </div>

      <div class="success-message" id="successMessage">
        <div class="success-icon">✓</div>
        <h3>Success</h3>
        <p>Redirecting…</p>
      </div>
    </div>
  </div>
</body>
</html>
