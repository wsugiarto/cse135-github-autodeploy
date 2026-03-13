<?php
session_start();

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?? "";

function send_error_page(int $code): void {
  http_response_code($code);
  $file = __DIR__ . "/{$code}.html";
  if (file_exists($file)) {
    readfile($file);
  }
  exit;
}

if ($path === "/login.php") return;
if (empty($_SESSION["logged_in"])) {
  if (str_starts_with($path, "/api/")) {
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode([
      "error" => "unauthorized",
      "message" => "unauthorized, please login for further access",
      "login_url" => "/login.php",
    ]);
    exit;
  }
  header("Location: /login.php");
  exit;
}

function current_role(): string {
  return $_SESSION["role"] ?? "";
}

function has_role(string $role): bool {
  return current_role() === $role;
}

function require_roles(array $roles): void {
  if (!in_array(current_role(), $roles, true)) {
    send_error_page(403);
  }
}

function can_access_section(string $section): bool {
  $sections = $_SESSION["sections"] ?? [];
  if (in_array("*", $sections, true)) return true;
  return in_array($section, $sections, true);
}

function require_section(string $section): void {
  if (current_role() === "super_admin") return;

  if (current_role() === "viewer") {
    if ($section !== "saved_reports") {
      send_error_page(403);
    }
    return;
  }
  if (!can_access_section($section)) {
    send_error_page(403);
  }
}