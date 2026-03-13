<?php
require __DIR__ . "/auth.php";
require_section("saved_reports"); 

$dir = __DIR__ . "/saved_reports_data";
$files = glob($dir . "/*.json") ?: [];
rsort($files);

$reports = [];
foreach ($files as $f) {
  $j = json_decode(file_get_contents($f), true);
  if (is_array($j)) $reports[] = $j;
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Saved Reports</title>
<link rel="stylesheet" href="saved-style.css">
<link rel="stylesheet" href="/reports/reports-style.css">
</head>
<body>
  <nav>
    <a href="/logout.php">Logout</a>
  </nav>
<h1>Saved Reports</h1>
<noscript>Please enable JavaScript in order to achieve full page functionality.</noscript>
<ul>
<?php foreach ($reports as $r): ?>
  <li>
    <a href="/saved_report_view.php?id=<?= htmlspecialchars($r["id"]) ?>">
      [<?= htmlspecialchars($r["category"]) ?>] <?= htmlspecialchars($r["title"]) ?>
    </a>
    (<?= htmlspecialchars($r["created_at"] ?? "") ?>)
    <?php if (!empty($r["pdf_url"])): ?>
      - <a href="<?= htmlspecialchars($r["pdf_url"]) ?>">PDF</a>
    <?php endif; ?>
  </li>
<?php endforeach; ?>
</ul>
</body>
</html>