<?php
require __DIR__ . "/auth.php";
require_section("saved_reports");

require __DIR__ . "/vendor/autoload.php";
use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET["id"] ?? "";
if (!preg_match('/^[A-Za-z0-9_]+$/', $id)) {
  http_response_code(400);
  echo "Bad id";
  exit;
}

$jsonFile = __DIR__ . "/saved_reports_data/$id.json";
if (!file_exists($jsonFile)) {
  http_response_code(404);
  echo "Saved report not found";
  exit;
}

$r = json_decode(file_get_contents($jsonFile), true);
if (!is_array($r)) {
  http_response_code(500);
  echo "Corrupt saved report";
  exit;
}

$title    = htmlspecialchars($r["title"] ?? "Saved Report");
$category = htmlspecialchars($r["category"] ?? "");
$createdAt = htmlspecialchars($r["created_at"] ?? "");
$commentary = $r["commentary"] ?? "";
$table    = $r["table"] ?? [];
$charts   = $r["charts"] ?? [];

$columnMap = [
  "performance" => [
    "id"                => "ID",
    "page"              => "Page",
    "load_time_display" => "Load Time",
    "received_display"  => "Received At",
    "session_id"        => "Session ID",
    "pageview_id"       => "Pageview ID",
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

ob_start();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= $title ?></title>
  <style>
    @page {
      margin: 40px 36px;
    }

    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      font-size: 11px;
      color: #1a1a1a;
      line-height: 1.4;
    }

    h1 {
      font-size: 18px;
      font-weight: 600;
      margin: 0 0 4px;
    }

    .meta {
      font-size: 10px;
      color: #666;
      margin: 0 0 18px;
    }

    h2 {
      font-size: 13px;
      font-weight: 600;
      color: #333;
      margin: 18px 0 8px;
      padding-bottom: 4px;
      border-bottom: 1px solid #e0e0e0;
    }

    .chart-block {
      margin: 0 0 14px;
      page-break-inside: avoid;
    }

    .chart-block h3 {
      font-size: 11px;
      font-weight: 600;
      color: #444;
      margin: 0 0 6px;
    }

    .chart-block img {
      width: 100%;
      max-width: 860px;
      border: 1px solid #ddd;
      border-radius: 6px;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      font-size: 9px;
      table-layout: fixed;   
      word-wrap: break-word;    
      overflow-wrap: break-word;
    }

    thead {
      display: table-header-group; 
    }

    th {
      background: #f5f6f8;
      color: #333;
      font-weight: 600;
      font-size: 9px;
      text-align: left;
      padding: 5px 6px;
      border: 1px solid #d0d0d0;
    }

    td {
      padding: 4px 6px;
      border: 1px solid #d0d0d0;
      vertical-align: top;
      color: #333;
    }

    tbody tr:nth-child(even) {
      background: #f9fafb;
    }

    tr {
      page-break-inside: avoid;
    }

    .commentary {
      background: #f9fafb;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      padding: 10px 12px;
      white-space: pre-wrap;
      font-size: 10px;
      color: #333;
      margin-top: 6px;
    }

    .empty {
      color: #999;
      font-style: italic;
    }
  </style>
</head>
<body>
  <h1><?= $title ?></h1>
  <p class="meta"><?= $category ?> report &middot; <?= $createdAt ?></p>

  <?php if (!empty($charts)): ?>
    <h2>Charts</h2>
    <?php foreach ($charts as $c): ?>
      <div class="chart-block">
        <h3><?= htmlspecialchars($c["title"]) ?></h3>
        <img src="data:image/png;base64,<?= htmlspecialchars($c["b64"]) ?>">
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <h2><?= htmlspecialchars($r["table_title"] ?? "Data Table") ?></h2>
  <?php if (!empty($table) && is_array($table) && $cols): ?>
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
      <p class="empty">Showing <?= $maxRows ?> of <?= $total ?> rows. View the full dataset in the application.</p>
    <?php endif; ?>
  <?php else: ?>
    <p class="empty">No table data.</p>
  <?php endif; ?>

  <?php if (!empty($commentary)): ?>
    <h2>Analyst Comments</h2>
    <div class="commentary"><?= htmlspecialchars($commentary) ?></div>
  <?php else: ?>
    <h2>Analyst Comments</h2>
    <p class="empty">No comments added.</p>
  <?php endif; ?>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set("isRemoteEnabled", true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$exportsDir = __DIR__ . "/exports";
if (!is_dir($exportsDir)) {
  mkdir($exportsDir, 0755, true);
}

$pdfRel = "/exports/saved_report_{$id}.pdf";
$pdfAbs = __DIR__ . $pdfRel;
file_put_contents($pdfAbs, $dompdf->output(), LOCK_EX);

$r["pdf_url"] = $pdfRel;
file_put_contents($jsonFile, json_encode($r, JSON_UNESCAPED_SLASHES), LOCK_EX);

header("Location: $pdfRel");
exit;