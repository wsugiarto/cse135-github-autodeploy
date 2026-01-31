<?php
header("Content-Type: application/json");
header("Cache-Control: no-cache");
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$time = date("D M d H:i:s Y");

echo json_encode([
  "title" => "Hello, PHP!",
  "heading" => "Hello, PHP!",
  "message" => "This page was generated with the PHP programming language",
  "time" => $time,
  "IP" => $ip
]);
