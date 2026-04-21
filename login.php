<?php
session_start();
require 'db.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error   = "";
$success = "";

// ── SIGNUP ──
if (isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Check if email already exists
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = "Email already registered. Please login.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed')");
        $success = "Account created! You can now login.";
    }
}

// ── LOGIN ──
if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Wrong password. Try again.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FitTrack — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: #0d0d0f;
    color: #f0f0ee;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .box {
    background: #141416;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    padding: 2.5rem 2rem;
    width: 100%;
    max-width: 400px;
  }

  .logo {
    font-family: 'Syne', sans-serif;
    font-size: 1.4rem;
    color: #c8f55a;
    margin-bottom: 0.25rem;
  }

  .sub {
    font-size: 0.82rem;
    color: #888;
    margin-bottom: 2rem;
  }

  /* Tabs */
  .tabs {
    display: flex;
    background: #1c1c1f;
    border-radius: 8px;
    padding: 4px;
    margin-bottom: 1.5rem;
  }

  .tab-btn {
    flex: 1;
    padding: 0.5rem;
    background: none;
    border: none;
    border-radius: 6px;
    color: #888;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.15s;
  }

  .tab-btn.active {
    background: #c8f55a;
    color: #0d0d0f;
    font-weight: 500;
  }

  /* Forms */
  .form { display: none; flex-direction: column; gap: 0.875rem; }
  .form.active { display: flex; }

  label {
    font-size: 0.78rem;
    color: #888;
    margin-bottom: 4px;
    display: block;
  }

  input[type="text"],
  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: 0.65rem 0.875rem;
    background: #1c1c1f;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    color: #f0f0ee;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.875rem;
    outline: none;
    transition: border-color 0.15s;
  }

  input:focus { border-color: #c8f55a; }

  .submit-btn {
    width: 100%;
    padding: 0.75rem;
    background: #c8f55a;
    border: none;
    border-radius: 8px;
    color: #0d0d0f;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    margin-top: 0.25rem;
    transition: opacity 0.15s;
  }

  .submit-btn:hover { opacity: 0.88; }

  /* Messages */
  .msg {
    padding: 0.65rem 0.875rem;
    border-radius: 8px;
    font-size: 0.82rem;
    margin-bottom: 1rem;
  }

  .msg.error   { background: rgba(245,90,90,0.12); color: #f55a5a; border: 1px solid rgba(245,90,90,0.2); }
  .msg.success { background: rgba(200,245,90,0.1); color: #c8f55a; border: 1px solid rgba(200,245,90,0.2); }
</style>
</head>
<body>

<div class="box">
  <div class="logo">FitTrack</div>
  <div class="sub">Track your workouts and meals</div>

  <?php if ($error):   ?><div class="msg error"><?php echo $error; ?></div><?php endif; ?>
  <?php if ($success): ?><div class="msg success"><?php echo $success; ?></div><?php endif; ?>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab-btn active" onclick="showTab('login')">Login</button>
    <button class="tab-btn"        onclick="showTab('signup')">Sign Up</button>
  </div>

  <!-- Login Form -->
  <form class="form active" id="login" method="POST">
    <div>
      <label>Email</label>
      <input type="email" name="email" placeholder="you@email.com" required>
    </div>
    <div>
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" name="login" class="submit-btn">Login</button>
  </form>

  <!-- Signup Form -->
  <form class="form" id="signup" method="POST">
    <div>
      <label>Name</label>
      <input type="text" name="username" placeholder="Jasbir Singh" required>
    </div>
    <div>
      <label>Email</label>
      <input type="email" name="email" placeholder="you@email.com" required>
    </div>
    <div>
      <label>Password</label>
      <input type="password" name="password" placeholder="Min 6 characters" required>
    </div>
    <button type="submit" name="signup" class="submit-btn">Create Account</button>
  </form>
</div>

<script>
  function showTab(tab) {
    document.querySelectorAll('.form').forEach(f => f.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tab).classList.add('active');
    event.target.classList.add('active');
  }

  // If there's an error/success from signup, show signup tab
  <?php if ($success || (isset($_POST['signup']) && $error)): ?>
    showTab('signup');
    document.querySelectorAll('.tab-btn')[1].classList.add('active');
    document.querySelectorAll('.tab-btn')[0].classList.remove('active');
  <?php endif; ?>
</script>

</body>
</html>