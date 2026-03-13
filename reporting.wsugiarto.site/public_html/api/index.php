<?php
declare(strict_types=1);

require_once __DIR__ . "/db.php";
require __DIR__ . "/../auth.php";
require_section("reports");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
  http_response_code(204);
  exit;
}

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?: "";

$parts = array_values(array_filter(explode("/", $path)));
$apiIndex = array_search("api", $parts, true);
$parts = ($apiIndex === false) ? $parts : array_slice($parts, $apiIndex + 1);

$resource = $parts[0] ?? "";
$id = $parts[1] ?? null;

if (!in_array($resource, ["pageviews", "activity"], true)) {
  json_out(["error" => "Unknown resource"], 404);
}

$pdo = db();
function require_numeric_id($id): int {
  if ($id === null || !ctype_digit((string)$id)) json_out(["error" => "Invalid id"], 400);
  return (int)$id;
}
// PAge views
if ($resource === "pageviews") {

  if ($method === "GET" && $id === null) {
    $stmt = $pdo->query("
      SELECT id, session_id, pageview_id, page, time_start_ms, received_at, session_id, pageview_id, page, time_start_ms, user_agent, language,
           cookies_enabled, js_enabled, images_enabled, css_enabled,
           screen_json, window_json, network_json, performance_json
      FROM pageviews
      ORDER BY id DESC
    ");
    json_out($stmt->fetchAll());
  }

  if ($method === "GET" && $id !== null) {
    $nid = require_numeric_id($id);
    $stmt = $pdo->prepare("SELECT * FROM pageviews WHERE id = ?");
    $stmt->execute([$nid]);
    $row = $stmt->fetch();
    if (!$row) json_out(["error" => "Not found"], 404);
    json_out($row);
  }

  if ($method === "POST" && $id === null) {
    $data = read_json_body();

    $sessionId = (string)($data["session_id"] ?? "");
    $pageviewId = (string)($data["pageview_id"] ?? "");
    if ($sessionId === "" || $pageviewId === "") {
      json_out(["error" => "session_id and pageview_id are required"], 400);
    }

    try {
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
      ");

      $stmt->execute([
        ":session_id" => $sessionId,
        ":pageview_id" => $pageviewId,
        ":page" => $data["page"] ?? null,
        ":time_start_ms" => is_numeric($data["time_start_ms"] ?? null) ? (int)$data["time_start_ms"] : null,
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

      json_out(["ok" => true, "id" => (int)$pdo->lastInsertId()], 201);
    } catch (PDOException $e) {
      if (str_contains($e->getMessage(), "Duplicate")) {
        json_out(["error" => "Duplicate pageview_id"], 409);
      }
      throw $e;
    }
  }

  if ($method === "PUT" && $id !== null) {
    $nid = require_numeric_id($id);
    $data = read_json_body();

    $stmt = $pdo->prepare("
      UPDATE pageviews SET
        page = COALESCE(:page, page),
        user_agent = COALESCE(:user_agent, user_agent),
        language = COALESCE(:language, language),
        cookies_enabled = COALESCE(:cookies_enabled, cookies_enabled),
        js_enabled = COALESCE(:js_enabled, js_enabled),
        images_enabled = COALESCE(:images_enabled, images_enabled),
        css_enabled = COALESCE(:css_enabled, css_enabled),
        screen_json = COALESCE(CAST(:screen_json AS JSON), screen_json),
        window_json = COALESCE(CAST(:window_json AS JSON), window_json),
        network_json = COALESCE(CAST(:network_json AS JSON), network_json),
        performance_json = COALESCE(CAST(:performance_json AS JSON), performance_json)
      WHERE id = :id
    ");

    $stmt->execute([
      ":id" => $nid,
      ":page" => $data["page"] ?? null,
      ":user_agent" => $data["user_agent"] ?? null,
      ":language" => $data["language"] ?? null,
      ":cookies_enabled" => array_key_exists("cookies_enabled", $data) ? (int)(bool)$data["cookies_enabled"] : null,
      ":js_enabled" => array_key_exists("js_enabled", $data) ? (int)(bool)$data["js_enabled"] : null,
      ":images_enabled" => array_key_exists("images_enabled", $data) ? (int)(bool)$data["images_enabled"] : null,
      ":css_enabled" => array_key_exists("css_enabled", $data) ? (int)(bool)$data["css_enabled"] : null,
      ":screen_json" => array_key_exists("screen", $data) ? json_encode($data["screen"], JSON_UNESCAPED_SLASHES) : null,
      ":window_json" => array_key_exists("window", $data) ? json_encode($data["window"], JSON_UNESCAPED_SLASHES) : null,
      ":network_json" => array_key_exists("network", $data) ? json_encode($data["network"], JSON_UNESCAPED_SLASHES) : null,
      ":performance_json" => array_key_exists("performance", $data) ? json_encode($data["performance"], JSON_UNESCAPED_SLASHES) : null,
    ]);

    json_out(["ok" => true, "updated" => $stmt->rowCount()]);
  }

  if ($method === "DELETE" && $id !== null) {
    $nid = require_numeric_id($id);
    $stmt = $pdo->prepare("DELETE FROM pageviews WHERE id = ?");
    $stmt->execute([$nid]);
    json_out(["ok" => true, "deleted" => $stmt->rowCount()]);
  }

  json_out(["error" => "Bad request"], 400);
}

//Activity
if ($resource === "activity") {

  if ($method === "GET" && $id === null) {
    $limit = 200;
    if (isset($_GET["limit"]) && ctype_digit((string)$_GET["limit"])) {
      $limit = max(1, min(2000, (int)$_GET["limit"]));
    }

    $stmt = $pdo->prepare("
      SELECT id, batch_id, session_id, pageview_id, type, ts_ms, page, data_json, received_at
      FROM activity_events
      ORDER BY id DESC
      LIMIT $limit
    ");
    $stmt->execute();
    json_out($stmt->fetchAll());
  }

  if ($method === "GET" && $id !== null) {
    $nid = require_numeric_id($id);
    $stmt = $pdo->prepare("SELECT * FROM activity_events WHERE id = ?");
    $stmt->execute([$nid]);
    $row = $stmt->fetch();
    if (!$row) json_out(["error" => "Not found"], 404);
    json_out($row);
  }

  if ($method === "POST" && $id === null) {
    $data = read_json_body();

    $batchId = $data["batch_id"] ?? null;
    $sessionId = (string)($data["session_id"] ?? "");
    $pageviewId = (string)($data["pageview_id"] ?? "");
    $type = (string)($data["type"] ?? "");

    if (!is_numeric($batchId) || (int)$batchId <= 0) json_out(["error" => "batch_id (number) is required"], 400);
    if ($sessionId === "" || $pageviewId === "" || $type === "") json_out(["error" => "session_id, pageview_id, type are required"], 400);

    $stmt = $pdo->prepare("
      INSERT INTO activity_events
        (batch_id, session_id, pageview_id, type, ts_ms, page, data_json)
      VALUES
        (:batch_id, :session_id, :pageview_id, :type, :ts_ms, :page, CAST(:data_json AS JSON))
    ");

    $stmt->execute([
      ":batch_id" => (int)$batchId,
      ":session_id" => $sessionId,
      ":pageview_id" => $pageviewId,
      ":type" => $type,
      ":ts_ms" => is_numeric($data["ts_ms"] ?? null) ? (int)$data["ts_ms"] : null,
      ":page" => $data["page"] ?? null,
      ":data_json" => json_encode($data["data"] ?? null, JSON_UNESCAPED_SLASHES),
    ]);

    json_out(["ok" => true, "id" => (int)$pdo->lastInsertId()], 201);
  }

  if ($method === "PUT" && $id !== null) {
    $nid = require_numeric_id($id);
    $data = read_json_body();

    $stmt = $pdo->prepare("
      UPDATE activity_events SET
        type = COALESCE(:type, type),
        ts_ms = COALESCE(:ts_ms, ts_ms),
        page = COALESCE(:page, page),
        data_json = COALESCE(CAST(:data_json AS JSON), data_json)
      WHERE id = :id
    ");

    $stmt->execute([
      ":id" => $nid,
      ":type" => array_key_exists("type", $data) ? (string)$data["type"] : null,
      ":ts_ms" => array_key_exists("ts_ms", $data) && is_numeric($data["ts_ms"]) ? (int)$data["ts_ms"] : null,
      ":page" => array_key_exists("page", $data) ? $data["page"] : null,
      ":data_json" => array_key_exists("data", $data) ? json_encode($data["data"], JSON_UNESCAPED_SLASHES) : null,
    ]);

    json_out(["ok" => true, "updated" => $stmt->rowCount()]);
  }

  if ($method === "DELETE" && $id !== null) {
    $nid = require_numeric_id($id);
    $stmt = $pdo->prepare("DELETE FROM activity_events WHERE id = ?");
    $stmt->execute([$nid]);
    json_out(["ok" => true, "deleted" => $stmt->rowCount()]);
  }

  json_out(["error" => "Bad request"], 400);
}

json_out(["error" => "Bad request"], 400);