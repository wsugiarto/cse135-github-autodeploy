<?php
require __DIR__ . "/auth.php";
require_section("saved_reports");

$id = $_GET["id"] ?? "";
if (!preg_match('/^[A-Za-z0-9_]+$/', $id)) { http_response_code(400); echo "Bad id"; exit; }

$file = __DIR__ . "/saved_reports_data/$id.json";
if (!file_exists($file)) { http_response_code(404); echo "Not found"; exit; }

$r = json_decode(file_get_contents($file), true);
if (!is_array($r)) { http_response_code(500); echo "Corrupt report"; exit; }

$table  = $r["table"] ?? [];
$charts = $r["charts"] ?? [];

$columnMap = [
  "performance" => [
    "id"                => "ID",
    "page"              => "Page",
    "load_time_display" => "Load Time",
    "received_display"  => "Received At",
    "session_id"        => "Session ID",
  ],
  "behavior" => [
    "id"        => "ID",
    "type"      => "Event Type",
    "page"      => "Page",
    "timestamp" => "Timestamp",
    "data_json" => "Details",
  ],
  "traffic" => [
    "rank"  => "#",
    "page"  => "Page Path",
    "count" => "Pageviews",
  ],
];

$cols = $columnMap[$r["category"] ?? ""] ?? null;
if (!$cols && !empty($table) && is_array($table[0])) {
  $cols = [];
  foreach (array_keys($table[0]) as $k) {
    $cols[$k] = $k;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($r["title"] ?? "Saved Report") ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      line-height: 1.5;
      margin: 0;
      padding: 32px 24px;
      background: #fafafa;
      color: #1a1a1a;
      -webkit-font-smoothing: antialiased;
    }

    h1 {
      font-size: 1.5rem;
      font-weight: 600;
      margin: 0 0 4px;
    }

    .meta {
      font-size: 0.84rem;
      color: #777;
      margin: 0 0 24px;
    }

    h2 {
      font-size: 1rem;
      font-weight: 600;
      color: #333;
      margin: 28px 0 10px;
      padding-bottom: 6px;
      border-bottom: 1px solid #e0e0e0;
    }

    .chart-block {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 14px 16px;
      margin: 0 0 14px;
      max-width: 1100px;
    }

    .chart-block h3 {
      font-size: 0.9rem;
      font-weight: 600;
      color: #444;
      margin: 0 0 10px;
    }

    .chart-block img {
      width: 100%;
      max-width: 100%;
      border-radius: 4px;
    }

    .table-card {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 14px 16px;
      margin: 0 0 14px;
      max-width: 1100px;
      overflow-x: auto;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      font-size: 0.8125rem;
    }

    th {
      background: #f5f6f8;
      color: #333;
      font-weight: 600;
      font-size: 0.8125rem;
      text-align: left;
      padding: 8px 10px;
      border-bottom: 2px solid #d0d0d0;
      white-space: nowrap;
    }

    td {
      padding: 7px 10px;
      border-bottom: 1px solid #e5e5e5;
      vertical-align: top;
      color: #333;
      word-break: break-word;
    }

    tbody tr:hover {
      background: #f0f4f8;
    }

    tbody tr:nth-child(even) {
      background: #f9fafb;
    }

    tbody tr:nth-child(even):hover {
      background: #edf1f5;
    }

    .empty {
      color: #999;
      font-style: italic;
      font-size: 0.875rem;
    }

    .row-note {
      font-size: 0.8rem;
      color: #888;
      margin: 8px 0 0;
    }

    .commentary {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 14px 16px;
      max-width: 1100px;
      white-space: pre-wrap;
      font-size: 0.875rem;
      color: #333;
      line-height: 1.6;
    }

    .actions {
      margin: 24px 0;
      display: flex;
      gap: 8px;
    }

    .actions a {
      display: inline-block;
      text-decoration: none;
      font-size: 0.84rem;
      font-weight: 500;
      padding: 8px 18px;
      border-radius: 6px;
      transition: background 0.15s;
    }

    .btn-primary {
      color: #fff;
      background: #4e79a7;
    }
    .btn-primary:hover {
      background: #3d6289;
    }

    .btn-secondary {
      color: #555;
      background: #e8ecf0;
    }
    .btn-secondary:hover {
      background: #dce1e7;
    }
  </style>
</head>
<body>

<h1><?= htmlspecialchars($r["title"] ?? "") ?></h1>
<p class="meta"><?= htmlspecialchars($r["category"] ?? "") ?> report &middot; <?= htmlspecialchars($r["created_at"] ?? "") ?><?php if (!empty($r["created_by"])): ?> &middot; by <?= htmlspecialchars($r["created_by"]) ?><?php endif; ?></p>

<h2>Charts</h2>
<?php foreach ($charts as $c): ?>
  <div class="chart-block">
    <h3><?= htmlspecialchars($c["title"]) ?></h3>
    <img src="data:image/png;base64,<?= htmlspecialchars($c["b64"]) ?>" alt="<?= htmlspecialchars($c["title"]) ?>">
  </div>
<?php endforeach; ?>

<h2><?= htmlspecialchars($r["table_title"] ?? "Data Table") ?></h2>
<?php if (!empty($table) && is_array($table) && $cols): ?>
  <div class="table-card">
    <table>
      <thead>
        <tr>
          <?php foreach ($cols as $key => $header): ?>
            <th><?= htmlspecialchars($header) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $maxRows = 100;
        $total = count($table);
        $displayed = array_slice($table, 0, $maxRows);
        ?>
        <?php foreach ($displayed as $row): ?>
          <tr>
            <?php foreach ($cols as $key => $header): ?>
              <td><?php
                $val = $row[$key] ?? "—";
                echo htmlspecialchars(is_scalar($val) ? (string)$val : json_encode($val));
              ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if ($total > $maxRows): ?>
      <p class="row-note">Showing <?= $maxRows ?> of <?= $total ?> rows.</p>
    <?php endif; ?>
  </div>
<?php else: ?>
  <p class="empty">No table data.</p>
<?php endif; ?>

<h2>Analyst Comments</h2>
<?php if (!empty($r["commentary"])): ?>
  <div class="commentary"><?= htmlspecialchars($r["commentary"]) ?></div>
<?php else: ?>
  <p class="empty">No comments added.</p>
<?php endif; ?>

<div class="actions">
  <a class="btn-primary" href="/export_saved_report.php?id=<?= htmlspecialchars($id) ?>">Export PDF</a>
  <a class="btn-secondary" href="/saved_reports.php">&larr; All Reports</a>
</div>

</body>
</html>