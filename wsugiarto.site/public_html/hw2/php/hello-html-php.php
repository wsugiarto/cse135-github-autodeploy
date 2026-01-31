<?php
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$time = date("D M d H:i:s Y");
header("Content-Type: text/html");
header("Cache-Control: no-cache");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Hello HTML PHP</title>
</head>
<body>
  <h1 align="center">Hello HTML World</h1>
  <hr>
  <p>Hello World, this is Wilson Sugiarto</p>
  <p>This page was generated with the PHP programming language</p>
  <p>This program was generated at: <?= $time ?></p>
  <p>Your current IP Address is: <?= $ip ?></p>
</body>
</html>
