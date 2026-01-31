<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? "");
    if ($name != "") {
        $_SESSION["name"] = $name;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>PHP Sessioning</title>
</head>
<body>
  <h1>PHP Sessioning Save Page</h1>
  <form method="POST" action="state-php.php">
    <label>Name:</label>
    <input type="text" name="name">
    <button type="submit">Save</button>
  </form>
  <br>
  <a href="state-2-php.php">Goto Saved Data</a>
</body>
</html>
