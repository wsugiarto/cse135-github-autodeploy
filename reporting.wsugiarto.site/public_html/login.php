<?php
session_start();
$users = require __DIR__ . "/users.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $u = $_POST["username"] ?? "";
  $p = $_POST["password"] ?? "";

  if (isset($users[$u]) && hash_equals($users[$u]["password"], $p)) {
    $_SESSION["logged_in"] = true;
    $_SESSION["username"] = $u;
    $_SESSION["role"] = $users[$u]["role"];
    $_SESSION["sections"] = $users[$u]["sections"];
    
    $role = $_SESSION["role"] ?? "";
    if ($role === "viewer") {
      header("Location: /saved_reports.php");
    } else {
      header("Location: /reports.php");
    }
    exit;

  } else {
    $error = "Invalid username/password";
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title>
<link rel="stylesheet" href="/login.css" />
</head>
<body>
  <h1>Login</h1>
  <noscript>Please enable JavaScript in order to achieve full page functionality.</noscript>
  <?php if ($error) { ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php } ?>
  <form method="POST">
    <label>Username <input name="username" autocomplete="username"></label><br>
    <label>Password <input name="password" type="password" autocomplete="current-password"></label><br>
    <button type="submit">Login</button>
  </form>
</body>
</html>