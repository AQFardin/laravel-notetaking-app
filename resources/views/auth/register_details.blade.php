<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Complete Registration</title>
  @vite(['resources/css/auth.css'])
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo"><div class="logo-square"></div></div>
        <h2>Complete Registration</h2>
        <p>Full name, age & optional photo</p>
      </div>

      @if($errors->any())
        <div style="color:#dc3545;margin-bottom:10px;font-weight:700">{{ $errors->first() }}</div>
      @endif

      <form class="login-form" method="POST" action="{{ route('register.details.submit') }}" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
          <label class="form-label">Full Name</label>
          <div class="input-wrapper">
            <input type="text" name="full_name" value="{{ old('full_name') }}" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Age</label>
          <div class="input-wrapper">
            <input type="number" name="age" value="{{ old('age') }}" required min="1" max="120">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Profile Picture (optional)</label>
          <div class="input-wrapper" style="padding:8px">
            <input type="file" name="profile_pic" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>

        <button type="submit" class="login-btn">
          <span class="btn-text">Finish</span>
          <div class="btn-loader">
            <div class="loader-bar"></div><div class="loader-bar"></div><div class="loader-bar"></div>
          </div>
        </button>
      </form>
    </div>
  </div>
</body>
</html>
