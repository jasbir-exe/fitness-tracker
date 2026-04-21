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

// Common Indian + general foods with calories and protein per serving
$foods = [
    // Meals
    ["name" => "Dal Tadka (1 bowl)",         "calories" => 180, "protein" => 9],
    ["name" => "Rajma (1 bowl)",              "calories" => 230, "protein" => 13],
    ["name" => "Chole (1 bowl)",              "calories" => 270, "protein" => 14],
    ["name" => "Paneer Bhurji (1 serving)",   "calories" => 280, "protein" => 18],
    ["name" => "Chicken Curry (1 serving)",   "calories" => 320, "protein" => 28],
    ["name" => "Egg Bhurji (2 eggs)",         "calories" => 220, "protein" => 14],
    ["name" => "Boiled Eggs (2)",             "calories" => 140, "protein" => 12],
    ["name" => "Omelette (2 eggs)",           "calories" => 180, "protein" => 13],
    ["name" => "Grilled Chicken (100g)",      "calories" => 165, "protein" => 31],
    ["name" => "Tuna (1 can)",                "calories" => 130, "protein" => 28],
    // Carbs
    ["name" => "Rice (1 cup cooked)",         "calories" => 200, "protein" => 4],
    ["name" => "Roti (2 rotis)",              "calories" => 180, "protein" => 5],
    ["name" => "Paratha (1)",                 "calories" => 260, "protein" => 5],
    ["name" => "Oats (1 bowl)",               "calories" => 150, "protein" => 5],
    ["name" => "Bread (2 slices)",            "calories" => 140, "protein" => 5],
    ["name" => "Poha (1 bowl)",               "calories" => 250, "protein" => 4],
    ["name" => "Upma (1 bowl)",               "calories" => 200, "protein" => 4],
    // Protein / Dairy
    ["name" => "Whey Protein Shake (1 scoop)","calories" => 120, "protein" => 24],
    ["name" => "Paneer (100g)",               "calories" => 265, "protein" => 18],
    ["name" => "Curd / Dahi (1 bowl)",        "calories" => 100, "protein" => 7],
    ["name" => "Milk (1 glass 250ml)",        "calories" => 150, "protein" => 8],
    ["name" => "Greek Yogurt (1 bowl)",       "calories" => 100, "protein" => 10],
    // Snacks / Fruits
    ["name" => "Banana (1)",                  "calories" => 90,  "protein" => 1],
    ["name" => "Apple (1)",                   "calories" => 80,  "protein" => 0],
    ["name" => "Peanut Butter (2 tbsp)",      "calories" => 190, "protein" => 8],
    ["name" => "Almonds (20g)",               "calories" => 120, "protein" => 4],
    ["name" => "Sprouts (1 bowl)",            "calories" => 90,  "protein" => 7],
];

if (isset($_POST['save'])) {
    $food_name = trim($_POST['food_name']);
    $calories  = (int)$_POST['calories'];
    $protein   = (float)$_POST['protein'];

    if ($food_name == "" || $calories <= 0) {
        $error = "Please select a food item.";
    } else {
        $conn->query("INSERT INTO meals (user_id, food_name, calories, protein) VALUES ('$user_id', '$food_name', '$calories', '$protein')");
        $success = "Meal logged successfully!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM meals WHERE id = $del_id AND user_id = $user_id");
    header("Location: add_meal.php");
    exit();
}

$today = date('Y-m-d');
$result = $conn->query("SELECT * FROM meals WHERE user_id = $user_id AND DATE(created_at) = '$today' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FitTrack — Add Meal</title>
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
    --accent2: #5af5c8;
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

  .user-chip { display: flex; align-items: center; gap: 8px; margin-bottom: 0.75rem; }

  .avatar {
    width: 30px; height: 30px;
    background: rgba(200,245,90,0.15);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 600; color: var(--accent);
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

  .main {
    margin-left: var(--sidebar-w);
    flex: 1;
    padding: 2rem 2.5rem;
    max-width: 640px;
  }

  .page-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    margin-bottom: 0.25rem;
  }

  .page-sub { font-size: 0.82rem; color: var(--muted); margin-bottom: 2rem; }

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

  select:focus { border-color: var(--accent2); }
  option { background: var(--surface2); color: var(--text); }

  /* Auto-fill preview */
  .preview {
    display: none;
    margin-top: 0.75rem;
    background: var(--surface2);
    border: 1px solid var(--border2);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    display: none;
    gap: 1.5rem;
  }

  .preview.visible { display: flex; }

  .preview-item { font-size: 0.82rem; color: var(--muted); }
  .preview-item span { display: block; font-size: 1rem; font-weight: 500; color: var(--text); margin-top: 2px; }
  .preview-item span.cal  { color: var(--accent); }
  .preview-item span.pro  { color: var(--accent2); }

  .save-btn {
    width: 100%;
    padding: 0.8rem;
    background: var(--accent2);
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

  .msg {
    padding: 0.7rem 1rem;
    border-radius: 8px;
    font-size: 0.84rem;
    margin-bottom: 1.25rem;
  }

  .msg.error   { background: rgba(245,90,90,0.12); color: #f55a5a; border: 1px solid rgba(245,90,90,0.2); }
  .msg.success { background: rgba(90,245,200,0.08); color: var(--accent2); border: 1px solid rgba(90,245,200,0.2); }

  .recent-title {
    font-family: 'Syne', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    margin: 2rem 0 1rem;
  }

  table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }

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

  .cal-val { color: var(--accent); font-weight: 500; }
  .pro-val { color: var(--accent2); font-weight: 500; }

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

  /* Today's totals bar */
  .totals-bar {
    display: flex;
    gap: 1.5rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 0.875rem 1.25rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
  }

  .totals-bar .t-label { color: var(--muted); }
  .totals-bar .t-val   { font-weight: 500; margin-left: 6px; }
  .totals-bar .t-cal   { color: var(--accent); }
  .totals-bar .t-pro   { color: var(--accent2); }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="logo"><span class="logo-dot"></span>FitTrack</div>
  <nav>
    <a href="dashboard.php">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
      </svg>Dashboard
    </a>
    <a href="add_workout.php">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M6 12h12M12 6v12"/><circle cx="12" cy="12" r="9"/>
      </svg>Add Workout
    </a>
    <a href="add_meal.php" class="active">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
        <path d="M8 12h8M12 8v8"/>
      </svg>Add Meal
    </a>
    <a href="history.php">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>
      </svg>History
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
  <div class="page-title">Log a Meal</div>
  <div class="page-sub">Select a food to add to today's log</div>

  <?php if ($error):   ?><div class="msg error"><?php echo $error; ?></div><?php endif; ?>
  <?php if ($success): ?><div class="msg success"><?php echo $success; ?></div><?php endif; ?>

  <div class="card">
    <form method="POST">
      <!-- Hidden fields filled by JS -->
      <input type="hidden" name="food_name" id="food_name">
      <input type="hidden" name="calories"  id="calories_val">
      <input type="hidden" name="protein"   id="protein_val">

      <div class="field">
        <label>Food Item</label>
        <select id="food_select" onchange="fillFood(this)" required>
          <option value="" disabled selected>Select a food</option>
          <?php foreach ($foods as $f): ?>
            <option
              value="<?php echo htmlspecialchars($f['name']); ?>"
              data-cal="<?php echo $f['calories']; ?>"
              data-pro="<?php echo $f['protein']; ?>">
              <?php echo htmlspecialchars($f['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <!-- Preview box shown after selection -->
        <div class="preview" id="preview">
          <div class="preview-item">Calories<span class="cal" id="show_cal">—</span></div>
          <div class="preview-item">Protein<span class="pro" id="show_pro">—</span></div>
        </div>
      </div>

      <button type="submit" name="save" class="save-btn">Save Meal</button>
    </form>
  </div>

  <!-- Today's totals -->
  <?php
  $totals = $conn->query("SELECT SUM(calories) as tc, SUM(protein) as tp FROM meals WHERE user_id = $user_id AND DATE(created_at) = '$today'");
  $t = $totals->fetch_assoc();
  $tc = $t['tc'] ?? 0;
  $tp = $t['tp'] ?? 0;
  ?>

  <div class="recent-title">Today's Meals</div>

  <div class="totals-bar">
    <div><span class="t-label">Total Calories</span><span class="t-val t-cal"><?php echo $tc; ?> kcal</span></div>
    <div><span class="t-label">Total Protein</span><span class="t-val t-pro"><?php echo $tp; ?>g</span></div>
  </div>

  <?php if ($result->num_rows === 0): ?>
    <div class="empty">No meals logged today yet.</div>
  <?php else: ?>
    <div class="card" style="padding: 0.25rem 0;">
      <table>
        <thead>
          <tr>
            <th>Food</th>
            <th>Calories</th>
            <th>Protein</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['food_name']); ?></td>
              <td class="cal-val"><?php echo $row['calories']; ?> kcal</td>
              <td class="pro-val"><?php echo $row['protein']; ?>g</td>
              <td><a href="?delete=<?php echo $row['id']; ?>"><button class="delete-btn">Delete</button></a></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</main>

<script>
  function fillFood(select) {
    const opt = select.options[select.selectedIndex];
    const cal = opt.dataset.cal;
    const pro = opt.dataset.pro;

    document.getElementById('food_name').value   = opt.value;
    document.getElementById('calories_val').value = cal;
    document.getElementById('protein_val').value  = pro;

    document.getElementById('show_cal').textContent = cal + ' kcal';
    document.getElementById('show_pro').textContent = pro + 'g';
    document.getElementById('preview').classList.add('visible');
  }
</script>

</body>
</html>
