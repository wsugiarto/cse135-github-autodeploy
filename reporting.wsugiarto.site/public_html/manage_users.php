<?php
require __DIR__ . "/auth.php";
require_roles(["super_admin"]);

$usersPath = __DIR__ . "/users.php";
$users = require $usersPath;

function write_users_file(string $path, array $users): bool {
  $export = var_export($users, true);
  $content = "<?php\nreturn " . $export . ";\n";
  return file_put_contents($path, $content, LOCK_EX) !== false;
}

$msg = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $target = $_POST["target_user"] ?? "";
  if (!isset($users[$target])) {
    $error = "Unknown user.";
  } else {
    $newPass = trim($_POST["password"] ?? "");
    $newRole = trim($_POST["role"] ?? "");
    $sectionsRaw = trim($_POST["sections"] ?? "");

    if ($newPass !== "") $users[$target]["password"] = $newPass;

    if (in_array($newRole, ["super_admin", "analyst", "viewer"], true)) {
      $users[$target]["role"] = $newRole;
    }
    if ($sectionsRaw !== "") {
      $sections = array_values(array_filter(array_map("trim", explode(",", $sectionsRaw))));
      $users[$target]["sections"] = $sections;
    }
    if (write_users_file($usersPath, $users)) {
      $msg = "Updated user: " . htmlspecialchars($target);
      $users = require $usersPath;
    } else {
      $error = "Failed to write users.php (check file permissions).";
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Manage Users</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px; }
    nav a { margin-right: 12px; }
    .card { border: 1px solid #ddd; border-radius: 10px; padding: 12px 14px; margin: 12px 0; max-width: 800px; }
    label { display:block; margin: 6px 0 2px; }
    input { width: 90%; padding: 8px; }
    select { width: 93%; padding: 8px; }
    button { margin-top: 10px; padding: 8px 12px; }
    .ok { color: green; }
    .err { color: red; }
    code { background:#f4f4f4; padding:2px 6px; border-radius:6px; }
  </style>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <nav>
    <a href="/reports.php">Reports</a>
    <a href="/reports/performance.php">Performance</a>
    <a href="/reports/behavior.php">Behavior</a>
    <a href="/reports/traffic.php">Traffic</a>
    <a href="/logout.php">Logout</a>
  </nav>

  <h1>Manage Users (Admin Only)</h1>
  <noscript>Please enable JavaScript in order to achieve full page functionality.</noscript>
  <p>Admins and Analyst have access to 'reports' sections, viewers only have access to 'saved_reports' sections.
    If changing to or from the viewer role make sure to add or remove access to 'reports' section. 
  </p>
  <?php if ($msg) echo "<p class='ok'>$msg</p>"; ?>
  <?php if ($error) echo "<p class='err'>$error</p>"; ?>

  <?php foreach ($users as $uname => $info): ?>
    <div class="card">
      <h3><?= htmlspecialchars($uname) ?></h3>
      <p>Current role: <code><?= htmlspecialchars($info["role"] ?? "") ?></code></p>
      <p>Current sections: <code><?= htmlspecialchars(implode(",", $info["sections"] ?? [])) ?></code></p>

      <form method="POST">
        <input type="hidden" name="target_user" value="<?= htmlspecialchars($uname) ?>" />

        <label>New password (leave blank to keep)</label>
        <input name="password" type="text" />

        <label>Role</label>
        <select name="role">
          <?php
            $roles = ["super_admin", "analyst", "viewer"];
            foreach ($roles as $r) {
              $sel = (($info["role"] ?? "") === $r) ? "selected" : "";
              echo "<option value='$r' $sel>$r</option>";
            }
          ?>
        </select>

        <label>Sections (comma-separated)</label>
        <input name="sections" type="text" value="<?= htmlspecialchars(implode(",", $info["sections"] ?? [])) ?>" />

        <button type="submit">Update</button>
      </form>
    </div>
  <?php endforeach; ?>
</body>
</html>