<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error   = "";

if (isset($_POST['save'])) {
    $exercise = trim($_POST['exercise_name']);
    $sets     = (int)$_POST['sets'];
    $reps     = (int)$_POST['reps'];
    $weight   = (float)$_POST['weight'];

    if ($exercise == "" || $sets <= 0 || $reps <= 0 || $weight < 0) {
        $error = "Please fill in all fields correctly.";
    } else {
        $conn->query("INSERT INTO workouts (user_id, exercise_name, sets, reps, weight) VALUES ('$user_id', '$exercise', '$sets', '$reps', '$weight')");
        $success = "Workout logged successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FitTrack — Add Workout</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg: #0d0d0f;
    --surface: #141416;
    --surface2: #1c1c1f;
    --border: rgba(255,255,255,0.07);
    --border2: rgba(255,255,255,0.12);
    --text: #f0f0ee;
    --muted: #888884;
    --accent: #c8f55a;
    --amber: #f5c85a;
    --danger: #f55a5a;
    --sidebar-w: 220px;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
  }

  /* ── SIDEBAR (same as dashboard) ── */
  .sidebar {
    width: var(--sidebar-w);
    background: var(--surface);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    padding: 2rem 1.25rem;
    position: fixed;
    top: 0; left: 0; bottom: 0;
  }

  .logo {
    font-family: 'Syne', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--accent);
    letter-spacing: -0.02em;
    margin-bottom: 2.5rem;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .logo-dot {
    width: 8px; height: 8px;
    background: var(--accent);
    border-radius: 50%;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
  }

  nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }

  nav a {
    text-decoration: none;
    color: var(--muted);
    font-size: 0.875rem;
    padding: 0.6rem 0.875rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.15s;
  }

  nav a:hover { background: var(--surface2); color: var(--text); }
  nav a.active { background: rgba(200,245,90,0.1); color: var(--accent); }

  .icon { width: 18px; height: 18px; opacity: 0.7; flex-shrink: 0; }
  nav a.active .icon { opacity: 1; }

  .sidebar-footer {
    border-top: 1px solid var(--border);
    padding-top: 1rem;
    font-size: 0.8rem;
    color: var(--muted);
  }

  .user-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 0.75rem;
  }

  .avatar {
    width: 30px; height: 30px;
    background: rgba(200,245,90,0.15);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--accent);
  }

  .logout-btn {
    display: block;
    text-decoration: none;
    color: var(--muted);
    font-size: 0.8rem;
    padding: 0.4rem 0.875rem;
    border-radius: 6px;
    transition: background 0.15s, color 0.15s;
  }

  .logout-btn:hover { background: rgba(245,90,90,0.1); color: var(--danger); }

  /* ── MAIN ── */
  .main {
    margin-left: var(--sidebar-w);
    flex: 1;
    padding: 2rem 2.5rem;
    max-width: 600px;
  }

  .page-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    margin-bottom: 0.25rem;
  }

  .page-sub {
    font-size: 0.82rem;
    color: var(--muted);
    margin-bottom: 2rem;
  }

  /* ── FORM CARD ── */
  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.75rem;
  }

  .field { margin-bottom: 1.25rem; }

  label {
    display: block;
    font-size: 0.78rem;
    color: var(--muted);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  input[type="text"],
  input[type="number"] {
    width: 100%;
    padding: 0.7rem 0.875rem;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.15s;
  }

  input:focus { border-color: var(--accent); }

  select {
    width: 100%;
    padding: 0.7rem 0.875rem;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    outline: none;
    cursor: pointer;
    transition: border-color 0.15s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888884' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.875rem center;
  }

  select:focus { border-color: var(--accent); }

  optgroup { color: var(--muted); font-size: 0.78rem; }
  option   { background: var(--surface2); color: var(--text); }

  /* 3 column row for sets/reps/weight */
  .three-col {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
  }

  .save-btn {
    width: 100%;
    padding: 0.8rem;
    background: var(--accent);
    border: none;
    border-radius: 8px;
    color: #0d0d0f;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    margin-top: 0.5rem;
    transition: opacity 0.15s;
  }

  .save-btn:hover { opacity: 0.88; }

  /* Messages */
  .msg {
    padding: 0.7rem 1rem;
    border-radius: 8px;
    font-size: 0.84rem;
    margin-bottom: 1.25rem;
  }

  .msg.error   { background: rgba(245,90,90,0.12); color: #f55a5a; border: 1px solid rgba(245,90,90,0.2); }
  .msg.success { background: rgba(200,245,90,0.1); color: var(--accent); border: 1px solid rgba(200,245,90,0.2); }

  /* Recent workouts table */
  .recent-title {
    font-family: 'Syne', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    margin: 2rem 0 1rem;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
  }

  th {
    text-align: left;
    color: var(--muted);
    font-weight: 400;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0 0.75rem 0.6rem;
    border-bottom: 1px solid var(--border);
  }

  td {
    padding: 0.7rem 0.75rem;
    border-bottom: 1px solid var(--border);
    color: var(--text);
  }

  tr:last-child td { border-bottom: none; }

  .delete-btn {
    background: none;
    border: 1px solid rgba(245,90,90,0.3);
    color: #f55a5a;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: background 0.15s;
  }

  .delete-btn:hover { background: rgba(245,90,90,0.1); }

  .empty { color: var(--muted); font-size: 0.85rem; padding: 1rem 0; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="logo">
    <span class="logo-dot"></span>
    FitTrack
  </div>
  <nav>
    <a href="dashboard.php">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
      </svg>
      Dashboard
    </a>
    <a href="add_workout.php" class="active">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M6 12h12M12 6v12"/><circle cx="12" cy="12" r="9"/>
      </svg>
      Add Workout
    </a>
    <a href="add_meal.php">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
        <path d="M8 12h8M12 8v8"/>
      </svg>
      Add Meal
    </a>
    <a href="history.php">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>
      </svg>
      History
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
      <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <a href="logout.php" class="logout-btn">Sign out</a>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="page-title">Log a Workout</div>
  <div class="page-sub">Add an exercise to today's log</div>

  <?php if ($error):   ?><div class="msg error"><?php echo $error; ?></div><?php endif; ?>
  <?php if ($success): ?><div class="msg success"><?php echo $success; ?></div><?php endif; ?>

  <div class="card">
    <form method="POST">
      <div class="field">
        <label>Exercise Name</label>
        <select name="exercise_name" required>
          <option value="" disabled selected>Select an exercise</option>

          <optgroup label="Chest">
            <option>Bench Press</option>
            <option>Incline Bench Press</option>
            <option>Decline Bench Press</option>
            <option>Chest Flyes</option>
            <option>Push Ups</option>
          </optgroup>

          <optgroup label="Back">
            <option>Deadlift</option>
            <option>Pull Ups</option>
            <option>Barbell Row</option>
            <option>Lat Pulldown</option>
            <option>Seated Cable Row</option>
          </optgroup>

          <optgroup label="Shoulders">
            <option>Overhead Press</option>
            <option>Lateral Raises</option>
            <option>Front Raises</option>
            <option>Arnold Press</option>
            <option>Face Pulls</option>
          </optgroup>

          <optgroup label="Legs">
            <option>Squat</option>
            <option>Leg Press</option>
            <option>Romanian Deadlift</option>
            <option>Leg Curl</option>
            <option>Leg Extension</option>
            <option>Calf Raises</option>
            <option>Lunges</option>
          </optgroup>

          <optgroup label="Biceps">
            <option>Barbell Curl</option>
            <option>Dumbbell Curl</option>
            <option>Hammer Curl</option>
            <option>Preacher Curl</option>
          </optgroup>

          <optgroup label="Triceps">
            <option>Tricep Pushdown</option>
            <option>Skull Crushers</option>
            <option>Overhead Tricep Extension</option>
            <option>Dips</option>
          </optgroup>

          <optgroup label="Core">
            <option>Plank</option>
            <option>Crunches</option>
            <option>Leg Raises</option>
            <option>Cable Crunch</option>
          </optgroup>
        </select>
      </div>

      <div class="three-col">
        <div class="field">
          <label>Sets</label>
          <input type="number" name="sets" placeholder="4" min="1" required>
        </div>
        <div class="field">
          <label>Reps</label>
          <input type="number" name="reps" placeholder="10" min="1" required>
        </div>
        <div class="field">
          <label>Weight (kg)</label>
          <input type="number" name="weight" placeholder="60" min="0" step="0.5" required>
        </div>
      </div>

      <button type="submit" name="save" class="save-btn">Save Workout</button>
    </form>
  </div>

  <!-- Today's workouts -->
  <div class="recent-title">Today's Workouts</div>

  <?php
  $today = date('Y-m-d');

  // Handle delete
  if (isset($_GET['delete'])) {
      $del_id = (int)$_GET['delete'];
      $conn->query("DELETE FROM workouts WHERE id = $del_id AND user_id = $user_id");
      header("Location: add_workout.php");
      exit();
  }

  $result = $conn->query("SELECT * FROM workouts WHERE user_id = $user_id AND DATE(created_at) = '$today' ORDER BY created_at DESC");
  ?>

  <?php if ($result->num_rows === 0): ?>
    <div class="empty">No workouts logged today yet.</div>
  <?php else: ?>
    <div class="card" style="padding: 0.25rem 0;">
      <table>
        <thead>
          <tr>
            <th>Exercise</th>
            <th>Sets</th>
            <th>Reps</th>
            <th>Weight</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['exercise_name']); ?></td>
              <td><?php echo $row['sets']; ?></td>
              <td><?php echo $row['reps']; ?></td>
              <td><?php echo $row['weight']; ?> kg</td>
              <td><a href="?delete=<?php echo $row['id']; ?>"><button class="delete-btn">Delete</button></a></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</main>
</body>
</html>
