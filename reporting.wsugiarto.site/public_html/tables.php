<?php
require __DIR__ . "/auth.php";
require_section("reports");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Tables</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>

  <style>
    body { 
      font-family: system-ui, Arial, sans-serif; 
      margin: 2em; 
    }
    nav a {
      margin-right: 1.5em;
    }
  </style>
</head>
<body>
  <nav>
    <a href="/reports.php">Reports</a>
    <a href="/tables.php">Tables</a>
    <a href="/charts.php">Charts</a>
    <a href="/logout.php">Logout</a>
    
  </nav>

  <h1>Data Tables</h1>
  <noscript>Please enable JavaScript in order to achieve full page functionality.</noscript>
  <h2>Pageviews</h2>
  <p>Data coming from: /api/pageviews</p>
  <zing-grid
    src="/api/pageviews"
    caption="Pageviews"
    pager
    page-size="25"
    sort
    filter
  ></zing-grid>

  <h2>Activity</h2>
  <p>Data coming from: /api/activity?limit=500</p>
  <zing-grid
    src="/api/activity?limit=500"
    caption="Activity Events"
    pager
    page-size="25"
    sort
    filter
  ></zing-grid>

</body>
</html>