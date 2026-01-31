<?php
header("Content-Type: text/html");

$method = $_SERVER["REQUEST_METHOD"] ?? "";
$protocol = $_SERVER["SERVER_PROTOCOL"] ?? "";
$query = $_SERVER["QUERY_STRING"] ?? "";
$host = $_SERVER["HTTP_HOST"] ?? "";
$useragent = $_SERVER["HTTP_USER_AGENT"] ?? "";
$ip = $_SERVER["REMOTE_ADDR"] ?? "";
$time = date("D M d H:i:s Y");

$body = "";
if (in_array($method, ["POST", "PUT", "DELETE"])) {
    $body = file_get_contents("php://input");
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>General Request Echo</title>
</head>
<body>
  <h1 align="center">General Request Echo PHP Version</h1>
  <hr>
  <p><b>HTTP Protocol:</b> <?= $protocol ?></p>
  <p><b>HTTP Method:</b> <?= $method ?></p>
  <p><b>Hostname:</b> <?= $host ?></p>
  <p><b>Time:</b> <?= $time ?></p>
  <p><b>User Agent:</b> <?= $useragent ?></p>
  <p><b>IP Address:</b> <?= $ip ?></p>
  <p><b>Query String:</b></p>
  <pre><?= htmlspecialchars($query) ?></pre>
  <p><b>Message Body:</b></p>
  <pre><?= htmlspecialchars($body) ?></pre>
</body>
</html>
