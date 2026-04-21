<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- DB CONNECTION ---
$conn = new mysqli("localhost", "root", "", "fitness_tracker");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? "Athlete";
$today = date('Y-m-d');

// --- TODAY'S STATS ---
$cal_query = $conn->query("SELECT SUM(calories) as total_cal, SUM(protein) as total_protein FROM meals WHERE user_id = $user_id AND DATE(created_at) = '$today'");
$cal_data = $cal_query->fetch_assoc();
$total_calories = $cal_data['total_cal'] ?? 0;
$total_protein  = $cal_data['total_protein'] ?? 0;

$workout_query = $conn->query("SELECT COUNT(*) as total_workouts FROM workouts WHERE user_id = $user_id AND DATE(created_at) = '$today'");
$workout_data = $workout_query->fetch_assoc();
$total_workouts = $workout_data['total_workouts'] ?? 0;

// --- RECENT ACTIVITY (last 5 combined) ---
$activity_query = $conn->query("
    (SELECT 'workout' AS type, exercise_name AS name, created_at FROM workouts WHERE user_id = $user_id)
    UNION ALL
    (SELECT 'meal' AS type, food_name AS name, created_at FROM meals WHERE user_id = $user_id)
    ORDER BY created_at DESC LIMIT 5
");

// --- WEEKLY CALORIES (for chart) ---
$weekly_query = $conn->query("
    SELECT DATE(created_at) as day, SUM(calories) as cal
    FROM meals
    WHERE user_id = $user_id AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$weekly_labels = [];
$weekly_values = [];
while ($row = $weekly_query->fetch_assoc()) {
    $weekly_labels[] = date('D', strtotime($row['day']));
    $weekly_values[] = (int)$row['cal'];
}

$protein_goal = 150; // grams — you can make this dynamic later
$protein_pct = min(100, round(($total_protein / $protein_goal) * 100));

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FitTrack — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
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
    --accent2: #5af5c8;
    --danger: #f55a5a;
    --amber: #f5c85a;
    --sidebar-w: 220px;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
  }

  /* ── SIDEBAR ── */
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
    font-weight: 400;
    padding: 0.6rem 0.875rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.15s;
  }

  nav a:hover { background: var(--surface2); color: var(--text); }
  nav a.active { background: rgba(200, 245, 90, 0.1); color: var(--accent); }

  nav a .icon {
    width: 18px; height: 18px;
    opacity: 0.7;
    flex-shrink: 0;
  }

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
    max-width: calc(100% - var(--sidebar-w));
  }

  .topbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
  }

  .topbar-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: var(--text);
  }

  .topbar-sub {
    font-size: 0.8rem;
    color: var(--muted);
    margin-top: 2px;
  }

  .date-badge {
    background: var(--surface);
    border: 1px solid var(--border2);
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 0.78rem;
    color: var(--muted);
  }

  /* ── STAT CARDS ── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    position: relative;
    overflow: hidden;
    transition: border-color 0.2s;
  }

  .stat-card:hover { border-color: var(--border2); }

  .stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    border-radius: 14px 14px 0 0;
  }

  .stat-card.cal::before { background: var(--accent); }
  .stat-card.protein::before { background: var(--accent2); }
  .stat-card.workouts::before { background: var(--amber); }

  .stat-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted);
    margin-bottom: 0.5rem;
  }

  .stat-value {
    font-family: 'Syne', sans-serif;
    font-size: 2.2rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.04em;
  }

  .stat-card.cal .stat-value { color: var(--accent); }
  .stat-card.protein .stat-value { color: var(--accent2); }
  .stat-card.workouts .stat-value { color: var(--amber); }

  .stat-unit {
    font-size: 0.8rem;
    color: var(--muted);
    margin-left: 4px;
  }

  /* ── PROTEIN PROGRESS ── */
  .progress-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
  }

  .progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
  }

  .progress-title {
    font-size: 0.8rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }

  .progress-pct {
    font-family: 'Syne', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: var(--accent2);
  }

  .progress-bar-bg {
    background: var(--surface2);
    border-radius: 100px;
    height: 8px;
    overflow: hidden;
  }

  .progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent2), var(--accent));
    border-radius: 100px;
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .progress-sub {
    font-size: 0.72rem;
    color: var(--muted);
    margin-top: 6px;
  }

  /* ── BOTTOM GRID ── */
  .bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  .panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
  }

  .panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .panel-title {
    font-family: 'Syne', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text);
  }

  .panel-link {
    font-size: 0.75rem;
    color: var(--accent);
    text-decoration: none;
    opacity: 0.7;
    transition: opacity 0.15s;
  }

  .panel-link:hover { opacity: 1; }

  /* Activity feed */
  .activity-list { display: flex; flex-direction: column; gap: 8px; }

  .activity-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
  }

  .activity-item:last-child { border-bottom: none; }

  .activity-badge {
    width: 28px; height: 28px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
    flex-shrink: 0;
  }

  .activity-badge.workout { background: rgba(245,200,90,0.15); color: var(--amber); }
  .activity-badge.meal { background: rgba(90,245,200,0.15); color: var(--accent2); }

  .activity-name {
    font-size: 0.85rem;
    color: var(--text);
    flex: 1;
  }

  .activity-time {
    font-size: 0.72rem;
    color: var(--muted);
  }

  .empty-state {
    text-align: center;
    color: var(--muted);
    font-size: 0.82rem;
    padding: 1.5rem 0;
  }

  /* Quick actions */
  .quick-actions { display: flex; flex-direction: column; gap: 10px; }

  .quick-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0.875rem 1rem;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 400;
    transition: all 0.15s;
    cursor: pointer;
  }

  .quick-btn:hover { border-color: var(--border2); background: rgba(255,255,255,0.04); }

  .quick-btn .btn-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
  }

  .quick-btn.add-workout .btn-icon { background: rgba(245,200,90,0.15); }
  .quick-btn.add-meal .btn-icon { background: rgba(90,245,200,0.15); }
  .quick-btn.view-history .btn-icon { background: rgba(200,245,90,0.1); }

  .quick-btn .btn-arrow { margin-left: auto; color: var(--muted); font-size: 0.8rem; }

  /* Chart container */
  .chart-wrap { position: relative; height: 160px; }
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
    <a href="dashboard.php" class="active">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
      </svg>
      Dashboard
    </a>
    <a href="add_workout.php">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M6 12h12M12 6v12"/>
        <circle cx="12" cy="12" r="9"/>
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
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 7v5l3 3"/>
      </svg>
      History
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="avatar"><?php echo strtoupper(substr($username, 0, 2)); ?></div>
      <span><?php echo htmlspecialchars($username); ?></span>
    </div>
    <a href="logout.php" class="logout-btn">Sign out</a>
  </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main">

  <!-- Topbar -->
  <div class="topbar">
    <div>
      <div class="topbar-title">Good <?php
        $hour = date('H');
        if ($hour < 12) echo 'morning';
        elseif ($hour < 18) echo 'afternoon';
        else echo 'evening';
      ?>, <?php echo htmlspecialchars(explode(' ', $username)[0]); ?></div>
      <div class="topbar-sub">Here's your overview for today</div>
    </div>
    <div class="date-badge"><?php echo date('D, M j'); ?></div>
  </div>

  <!-- Stat Cards -->
  <div class="stats-grid">
    <div class="stat-card cal">
      <div class="stat-label">Calories Today</div>
      <div class="stat-value"><?php echo number_format($total_calories); ?><span class="stat-unit">kcal</span></div>
    </div>
    <div class="stat-card protein">
      <div class="stat-label">Protein Today</div>
      <div class="stat-value"><?php echo number_format($total_protein); ?><span class="stat-unit">g</span></div>
    </div>
    <div class="stat-card workouts">
      <div class="stat-label">Workouts Logged</div>
      <div class="stat-value"><?php echo $total_workouts; ?><span class="stat-unit">sets</span></div>
    </div>
  </div>

  <!-- Protein Progress Bar -->
  <div class="progress-section">
    <div class="progress-header">
      <span class="progress-title">Protein Goal — <?php echo $protein_goal; ?>g daily</span>
      <span class="progress-pct"><?php echo $protein_pct; ?>%</span>
    </div>
    <div class="progress-bar-bg">
      <div class="progress-bar-fill" style="width: <?php echo $protein_pct; ?>%"></div>
    </div>
    <div class="progress-sub"><?php echo $total_protein; ?>g consumed · <?php echo max(0, $protein_goal - $total_protein); ?>g remaining</div>
  </div>

  <!-- Bottom Grid -->
  <div class="bottom-grid">

    <!-- Recent Activity -->
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Recent Activity</span>
        <a href="history.php" class="panel-link">View all →</a>
      </div>
      <div class="activity-list">
        <?php if ($activity_query->num_rows === 0): ?>
          <div class="empty-state">No activity yet. Log your first workout or meal!</div>
        <?php else: ?>
          <?php while ($row = $activity_query->fetch_assoc()): ?>
            <div class="activity-item">
              <div class="activity-badge <?php echo $row['type']; ?>">
                <?php echo $row['type'] === 'workout' ? 'W' : 'M'; ?>
              </div>
              <span class="activity-name"><?php echo htmlspecialchars($row['name']); ?></span>
              <span class="activity-time"><?php echo date('H:i', strtotime($row['created_at'])); ?></span>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right panel: Chart + Quick Actions -->
    <div style="display: flex; flex-direction: column; gap: 1rem;">

      <!-- Weekly Calories Chart -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Calories — Last 7 Days</span>
        </div>
        <div class="chart-wrap">
          <canvas id="caloriesChart"></canvas>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Quick Actions</span>
        </div>
        <div class="quick-actions">
          <a href="add_workout.php" class="quick-btn add-workout">
            <div class="btn-icon">🏋️</div>
            Log a Workout
            <span class="btn-arrow">→</span>
          </a>
          <a href="add_meal.php" class="quick-btn add-meal">
            <div class="btn-icon">🥗</div>
            Add a Meal
            <span class="btn-arrow">→</span>
          </a>
          <a href="history.php" class="quick-btn view-history">
            <div class="btn-icon">📋</div>
            View History
            <span class="btn-arrow">→</span>
          </a>
        </div>
      </div>

    </div>
  </div>

</main>

<script>
  const labels = <?php echo json_encode($weekly_labels ?: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']); ?>;
  const values = <?php echo json_encode($weekly_values ?: [0,0,0,0,0,0,0]); ?>;

  const ctx = document.getElementById('caloriesChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        data: values,
        backgroundColor: 'rgba(200, 245, 90, 0.25)',
        borderColor: '#c8f55a',
        borderWidth: 1.5,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: {
        backgroundColor: '#1c1c1f',
        borderColor: 'rgba(255,255,255,0.1)',
        borderWidth: 1,
        titleColor: '#f0f0ee',
        bodyColor: '#c8f55a',
        callbacks: { label: ctx => ctx.raw + ' kcal' }
      }},
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#888884', font: { size: 11 } } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#888884', font: { size: 11 } }, beginAtZero: true }
      }
    }
  });
</script>

</body>
</html>
