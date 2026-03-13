<?php
require __DIR__ . "/auth.php";
require_section("reports");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo "Method not allowed";
  exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo "Invalid JSON";
  exit;
}

$category = $data["category"] ?? "";
$title = $data["title"] ?? "";
$commentary = $data["commentary"] ?? "";
$table = $data["table"] ?? null;
$charts = $data["charts"] ?? null;


if (!is_array($charts) || count($charts) === 0) {
  http_response_code(400); echo "Missing charts"; exit;
}
foreach ($charts as $c) {
  if (!is_array($c) || empty($c["b64"]) || empty($c["title"])) {
    http_response_code(400); echo "Bad chart format"; exit;
  }
}

if ($category === "" || $title === "" || !is_array($table) || !is_array($charts)) {
  http_response_code(400);
  echo "Missing fields";
  exit;
}

$id = date("Ymd_His") . "_" . bin2hex(random_bytes(4));
$payload = [
  "id" => $id,
  "category" => $category,
  "title" => $title,
  "table_title" => $data["table_title"] ?? "Data Table",
  "commentary" => $commentary,
  "table" => $table,
  "charts" => $charts,
  "created_by" => ($_SESSION["username"] ?? null),
  "created_at" => date("c"),
];

$dir = __DIR__ . "/saved_reports_data";
$file = "$dir/$id.json";

if (file_put_contents($file, json_encode($payload, JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
  http_response_code(500);
  echo "Failed to write saved report";
  exit;
}

header("Content-Type: application/json");
echo json_encode(["ok" => true, "id" => $id, "view_url" => "/saved_report_view.php?id=$id"]);