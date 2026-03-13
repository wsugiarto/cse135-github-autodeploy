<?php
declare(strict_types=1);

function db(): PDO {
  $dbHost = "localhost";
  $dbName = "cse135_collector";
  $dbUser = "root";
  $dbPass = "bobuseaccount123";

  return new PDO(
    "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
}

function json_out($data, int $status = 200): void {
  http_response_code($status);
  header("Content-Type: application/json");
  echo json_encode($data, JSON_UNESCAPED_SLASHES);
  exit;
}

function read_json_body(): array {
  $raw = file_get_contents("php://input");
  if (!$raw) json_out(["error" => "Empty body"], 400);

  $data = json_decode($raw, true);
  if (!is_array($data)) json_out(["error" => "Invalid JSON"], 400);
  return $data;
}