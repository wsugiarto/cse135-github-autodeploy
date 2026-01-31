<?php
header("Content-Type: text/html");
header("Cache-Control: no-cache");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Environment Variables</title>
</head>
<body>
  <h1 align="center">Environment Variables PHP Version</h1>
  <hr>
<?php
ksort($_SERVER);
foreach ($_SERVER as $key => $value) {
  echo "<b>$key:</b> $value<br />";
}
?>
</body>
</html>
