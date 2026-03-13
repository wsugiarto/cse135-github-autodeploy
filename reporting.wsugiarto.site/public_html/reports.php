<?php
require __DIR__ . "/auth.php";
require_roles(["super_admin", "analyst"]);
require_section("reports");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Reports</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <noscript>Please enable JavaScript in order to achieve full page functionality.</noscript>
  <h1>Reports</h1>
  <p>Logged in as <?= htmlspecialchars($_SESSION["username"] ?? "unknown") ?></p>

  <ul>
    <li><a href="/api/pageviews">API: pageviews</a></li>
    <li><a href="/api/activity">API: activity</a></li>
    <li><a href="/reports/performance.php">Performance Report</a></li>
    <li><a href="/reports/behavior.php">Behavior Report</a></li>
    <li><a href="/reports/traffic.php">Traffic Report</a></li>
    <li><a href="/saved_reports.php">Saved Reports For Viewers</a></li>
  </ul>
  
  <?php if (current_role() === "super_admin"): ?>
  <li><a href="/manage_users.php">Manage Users</a></li>
  <?php endif; ?>
  <h2>Deprecated HW4 Items</h2>
  <ul>
    <li><a href="/tables.php">Tables</a></li>
    <li><a href="/charts.php">Charts</a></li>
  </ul>
  <p><a href="/logout.php">Logout</a></p>
</body>
</html>