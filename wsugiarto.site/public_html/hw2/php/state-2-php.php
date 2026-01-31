<?php
session_start();
if (isset($_GET["clear"])) {
    unset($_SESSION["name"]);
}
$name = $_SESSION["name"] ?? "";
?>
<!DOCTYPE html>
<html>
<head>
  <title>PHP Sessioning</title>
</head>
<body>
  <h1>PHP Sessiong view data</h1>

  <p>
    <b>Saved Name:</b>
    <?php
    if ($name != ""){
        echo htmlspecialchars($name);
    }
    else{
        echo "No Name yet";
    }
    ?>
  </p>

  <a href="state-php.php">Go back to input page</a>
  <br><br>
  <a href="state-2-php.php?clear=CLEARTHISNOW">Clear Data</a>
</body>
</html>
