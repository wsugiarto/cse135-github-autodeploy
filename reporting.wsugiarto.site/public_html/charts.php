<?php
require __DIR__ . "/auth.php";
require_section("reports");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Charts</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body { 
      font-family: system-ui, Arial, sans-serif; 
      margin: 2em; 
    }
    nav a {
      margin-right: 1.5em;
    }
    .wrap { 
      max-width: 900px; 
    }
    .card { 
      margin: 18px 0 28px; 
      padding: 14px 16px; 
      border: 10px solid #00000010; 
      border-radius: 30px; 
    }
    canvas { 
      width: 100%; 
      height: 420px; 
    }
  </style>
</head>
<body>
  <div class="wrap">
    <nav>
      <a href="/reports.php">Reports</a>
      <a href="/tables.php">Tables</a>
      <a href="/charts.php">Charts</a>
      <a href="/logout.php">Logout</a>
    </nav>

    <h1>Charts</h1>

    <div class="card">
      <h2>Most Common Activity Events</h2>
      <p>Source: /api/activity?limit=2000</p>
      <canvas id="activityChart"></canvas>
    </div>

    <div class="card">
      <h2>Most Viewed Pages</h2>
      <p>Source: /api/pageviews</p>
      <canvas id="pageviewsChart"></canvas>
    </div>
  </div>

  <script>
    async function fetchJson(url) {
      const res = await fetch(url, { credentials: "same-origin" });
      if (!res.ok) {
        const text = await res.text().catch(() => "");
        throw new Error(`Fetch failed ${res.status} for ${url}: ${text}`);
      }
      return res.json();
    }

    function countBy(arr, keyFn) {
      const map = new Map();
      for (const item of arr) {
        const k = keyFn(item);
        if (!k) continue;
        map.set(k, (map.get(k) || 0) + 1);
      }
      return map;
    }

    function topNFromMap(map, n) {
      return [...map.entries()]
        .sort((a, b) => b[1] - a[1])
        .slice(0, n);
    }

    async function render() {
      const activity = await fetchJson("/api/activity?limit=2000");
      const typeCounts = countBy(activity, (e) => e.type);
      const typePairs = topNFromMap(typeCounts, 20);

      const activityLabels = typePairs.map(([t]) => t);
      const activityValues = typePairs.map(([_, c]) => c);

      new Chart(document.getElementById("activityChart"), {
        type: "bar",
        data: {
          labels: activityLabels,
          datasets: [{
            label: "Event Count",
            data: activityValues
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: {
              ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 }
            },
            y: {
              beginAtZero: true,
              title: { display: true, text: "Count" }
            }
          }
        }
      });
      const pageviews = await fetchJson("/api/pageviews");
      const pageCounts = countBy(pageviews, (p) => p.page);
      const pagePairs = topNFromMap(pageCounts, 10);

      const pageLabels = pagePairs.map(([p]) => p.replace(/^https:\/\//, ''));
      const pageValues = pagePairs.map(([_, c]) => c);

      new Chart(document.getElementById("pageviewsChart"), {
        type: "bar",
        data: {
          labels: pageLabels,
          datasets: [{
            label: "Pageviews",
            data: pageValues
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: {
              ticks: { autoSkip: false, maxRotation: 60, minRotation: 0 }
            },
            y: {
              beginAtZero: true,
              title: { display: true, text: "Count" }
            }
          }
        }
      });
    }

    render().catch((err) => {
      console.error(err);
      alert("Charts failed to load. Check console for details.");
    });
  </script>
</body>
</html>