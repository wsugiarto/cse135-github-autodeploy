<?php
declare(strict_types=1);
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  header("Content-Type: application/json");
  echo json_encode(["error" => "Method not allowed"]);
  exit;
}

$raw = file_get_contents("php://input");
if (!$raw) {
  http_response_code(400);
  header("Content-Type: application/json");
  echo json_encode(["error" => "Empty body"]);
  exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  header("Content-Type: application/json");
  echo json_encode(["error" => "Invalid JSON"]);
  exit;
}

$dbHost = "localhost";
$dbName = "cse135_collector";
$dbUser = "root";
$dbPass = "bobuseaccount123";
try {
  $pdo = new PDO(
    "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
  $kind = $data["kind"] ?? null;

  if ($kind === "activity_batch") {
    $sessionId  = (string)($data["session_id"] ?? "");
    $pageviewId = (string)($data["pageview_id"] ?? "");
    $page       = $data["page"] ?? null;
    $reason     = $data["reason"] ?? null;
    $sentTs     = $data["sent_ts"] ?? null;

    $events = $data["events"] ?? [];
    if (!is_array($events)) $events = [];

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
      INSERT INTO activity_batches
        (session_id, pageview_id, page, reason, sent_ts_ms, events_count, raw_json)
      VALUES
        (:session_id, :pageview_id, :page, :reason, :sent_ts_ms, :events_count, CAST(:raw_json AS JSON))
    ");
    $stmt->execute([
      ":session_id" => $sessionId,
      ":pageview_id" => $pageviewId,
      ":page" => $page,
      ":reason" => $reason,
      ":sent_ts_ms" => is_numeric($sentTs) ? (int)$sentTs : null,
      ":events_count" => count($events),
      ":raw_json" => $raw,
    ]);
    $batchId = (int)$pdo->lastInsertId();
    $evtStmt = $pdo->prepare("
      INSERT INTO activity_events
        (batch_id, session_id, pageview_id, type, ts_ms, page, data_json)
      VALUES
        (:batch_id, :session_id, :pageview_id, :type, :ts_ms, :page, CAST(:data_json AS JSON))
    ");
    foreach ($events as $evt) {
      if (!is_array($evt)) continue;
      $type = (string)($evt["type"] ?? "unknown");
      $tsMs = $evt["time_start"] ?? null;
      $evtPage = $evt["page"] ?? $page;
      $evtStmt->execute([
        ":batch_id" => $batchId,
        ":session_id" => (string)($evt["session_id"] ?? $sessionId),
        ":pageview_id" => (string)($evt["pageview_id"] ?? $pageviewId),
        ":type" => $type,
        ":ts_ms" => is_numeric($tsMs) ? (int)$tsMs : null,
        ":page" => $evtPage,
        ":data_json" => json_encode($evt, JSON_UNESCAPED_SLASHES),
      ]);
    }

    $pdo->commit();
    http_response_code(204);
    exit;
  }

  $sessionId  = (string)($data["session_id"] ?? "");
  $pageviewId = (string)($data["pageview_id"] ?? "");
  $page       = $data["page"] ?? null;

  $stmt = $pdo->prepare("
    INSERT INTO pageviews
      (session_id, pageview_id, page, time_start_ms, user_agent, language,
       cookies_enabled, js_enabled, images_enabled, css_enabled,
       screen_json, window_json, network_json, performance_json)
    VALUES
      (:session_id, :pageview_id, :page, :time_start_ms, :user_agent, :language,
       :cookies_enabled, :js_enabled, :images_enabled, :css_enabled,
       CAST(:screen_json AS JSON), CAST(:window_json AS JSON),
       CAST(:network_json AS JSON), CAST(:performance_json AS JSON))
    ON DUPLICATE KEY UPDATE
      received_at = CURRENT_TIMESTAMP
  ");

  $stmt->execute([
    ":session_id" => $sessionId,
    ":pageview_id" => $pageviewId,
    ":page" => $page,
    ":time_start_ms" => is_numeric($data["time_start"] ?? null) ? (int)$data["time_start"] : null,
    ":user_agent" => $data["user_agent"] ?? null,
    ":language" => $data["language"] ?? null,
    ":cookies_enabled" => isset($data["cookies_enabled"]) ? (int)(bool)$data["cookies_enabled"] : null,
    ":js_enabled" => isset($data["js_enabled"]) ? (int)(bool)$data["js_enabled"] : null,
    ":images_enabled" => isset($data["images_enabled"]) ? (int)(bool)$data["images_enabled"] : null,
    ":css_enabled" => isset($data["css_enabled"]) ? (int)(bool)$data["css_enabled"] : null,
    ":screen_json" => json_encode($data["screen"] ?? null, JSON_UNESCAPED_SLASHES),
    ":window_json" => json_encode($data["window"] ?? null, JSON_UNESCAPED_SLASHES),
    ":network_json" => json_encode($data["network"] ?? null, JSON_UNESCAPED_SLASHES),
    ":performance_json" => json_encode($data["performance"] ?? null, JSON_UNESCAPED_SLASHES),
  ]);

  http_response_code(204);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  header("Content-Type: application/json");
  echo json_encode(["error" => "Server error", "detail" => $e->getMessage()]);
  exit;
}