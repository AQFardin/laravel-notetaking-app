<!doctype html>
<html>
<body>
<h2>Register</h2>
<form method="POST" action="/register" enctype="multipart/form-data">
  @csrf
  <input name="username" placeholder="Username" required><br>
  <input name="full_name" placeholder="Full Name" required><br>
  <input type="number" name="age" placeholder="Age" required><br>
  <input type="email" name="email" placeholder="Email" required><br>
  <input type="password" name="password" placeholder="Password" required><br>
  <label>Profile Picture (optional)</label><br>
  <input type="file" name="profile_pic" accept="image/*"><br><br>
  <button>Register</button>
</form>
<a href="/login">Already have an account? Login</a>
</body>
</html>
