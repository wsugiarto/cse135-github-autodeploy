<?php
require __DIR__ . "/../auth.php";
require_section("reports");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Behavior Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px; background: #fafafa; color: #1a1a1a; }
    nav a { margin-right: 12px; }
    .card { border: 1px solid #ddd; border-radius: 10px; padding: 16px 18px; margin: 14px 0; max-width: 1100px; background: #fff; }
    .chart-wrap { position: relative; width: 100%; height: 380px; }
    .chart-wrap canvas { width: 100% !important; height: 100% !important; }
    textarea { width: 100%; min-height: 90px; }
  </style>
  <link rel="stylesheet" href="reports-style.css" />
</head>
<body>
  <nav>
    <a href="/reports.php">Reports</a>
    <a href="/reports/performance.php">Performance</a>
    <a href="/reports/behavior.php">Behavior</a>
    <a href="/reports/traffic.php">Traffic</a>
    <a href="/logout.php">Logout</a>
  </nav>

  <h1>Behavior Report</h1>
  <noscript>Please enable JavaScript in order to achieve full page functionality.</noscript>
  <div class="card">
    <h2>Activity Events by Type Across All Pages</h2>
    <div class="chart-wrap"><canvas id="eventsByType"></canvas></div>
  </div>

  <div class="card">
    <h2>Top 10 Clicks by Page</h2>
    <div class="chart-wrap"><canvas id="clicksByPage"></canvas></div>
  </div>

  <div class="card">
    <h2>Recent Activity Events</h2>
    <zing-grid id="evGrid" caption="Activity Events" pager page-size="25" sort filter>
      <zg-colgroup>
        <zg-column index="id" header="ID" type="number" width="70"></zg-column>
        <zg-column index="type" header="Event Type" width="120"></zg-column>
        <zg-column index="page" header="Page"></zg-column>
        <zg-column index="timestamp" header="Timestamp" width="180"></zg-column>
        <zg-column index="data_json" header="Details" width="220"></zg-column>
      </zg-colgroup>
    </zing-grid>
  </div>

  <div class="card">
    <h2>Analyst Comments</h2>
    <textarea placeholder="Write your interpretation here..."></textarea>
  </div>
  <button id="saveBtn">Save Report</button>
  <button onclick="window.print()">Export (Print to PDF)</button>

<script>
const PALETTE = ["#4e79a7","#f28e2b","#e15759","#76b7b2","#59a14f","#edc948","#b07aa1","#ff9da7","#9c755f","#bab0ac","#af7aa1","#d37295","#fabfd2","#b6992d","#499894"];
function truncateLabel(str, max) {
  if (!str) return "(unknown)";
  return str.length > max ? str.slice(0, max - 1) + "…" : str;
}

function formatTimestamp(tsMs) {
  if (!tsMs) return "—";
  const d = new Date(Number(tsMs));
  if (isNaN(d.getTime())) return String(tsMs);
  return d.toLocaleString("en-US", { month:"short", day:"numeric", hour:"2-digit", minute:"2-digit", second:"2-digit" });
}

async function fetchJson(url) {
  const r = await fetch(url, { credentials: "same-origin" });
  if (!r.ok) throw new Error(`${url} -> ${r.status}`);
  return r.json();
}

function topN(entries, n) {
  return entries.sort((a,b)=>b[1]-a[1]).slice(0,n);
}
const chartDefaults = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: "rgba(0,0,0,.8)",
      titleFont: { size: 13 },
      bodyFont: { size: 12 },
      cornerRadius: 6,
      padding: 10,
    },
  },
};

(async function render() {
  const events = await fetchJson("/api/activity?limit=2000");

  const typeCounts = new Map();
  for (const e of events) {
    if (!e.type) continue;
    typeCounts.set(e.type, (typeCounts.get(e.type) || 0) + 1);
  }
  const typePairs = topN([...typeCounts.entries()], 15);

  new Chart(document.getElementById("eventsByType"), {
    type: "bar",
    data: {
      labels: typePairs.map(p => p[0]),
      datasets: [{
        label: "Events",
        data: typePairs.map(p => p[1]),
        backgroundColor: typePairs.map((_, i) => PALETTE[i % PALETTE.length]),
        borderRadius: 4,
        maxBarThickness: 48,
      }]
    },
    options: {
      ...chartDefaults,
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: "Number of Events", font: { size: 13 } },
          ticks: { precision: 0 },
          grid: { color: "rgba(0,0,0,.06)" },
        },
        x: {
          title: { display: true, text: "Event Type", font: { size: 13 } },
          ticks: { maxRotation: 45, minRotation: 0 },
          grid: { display: false },
        }
      },
    }
  });

  const clickCounts = new Map();
  for (const e of events) {
    if (e.type !== "click") continue;
    const page = e.page || "(unknown)";
    clickCounts.set(page, (clickCounts.get(page) || 0) + 1);
  }
  const clickPairs = topN([...clickCounts.entries()], 10);

  new Chart(document.getElementById("clicksByPage"), {
    type: "bar",
    data: {
      labels: clickPairs.map(p => truncateLabel(p[0], 55)),
      datasets: [{
        label: "Clicks",
        data: clickPairs.map(p => p[1]),
        backgroundColor: "#4e79a7",
        borderRadius: 4,
        maxBarThickness: 48,
      }]
    },
    options: {
      ...chartDefaults,
      indexAxis: "y",
      scales: {
        x: {
          beginAtZero: true,
          title: { display: true, text: "Click Count", font: { size: 13 } },
          ticks: { precision: 0 },
          grid: { color: "rgba(0,0,0,.06)" },
        },
        y: {
          title: { display: false },
          grid: { display: false },
        }
      },
      plugins: {
        ...chartDefaults.plugins,
        tooltip: {
          ...chartDefaults.plugins.tooltip,
          callbacks: {
            title: function(items) {
              const idx = items[0].dataIndex;
              return clickPairs[idx][0];
            }
          }
        }
      }
    }
  });

  const interesting = new Set(["click","scroll","keydown","keyup","page_enter","page_leave","idle_end","error","unhandledrejection"]);
  const rows = events
    .filter(e => interesting.has(e.type))
    .slice(0, 300)
    .map(e => ({
      id: e.id,
      batch_id: e.batch_id,
      type: e.type,
      timestamp: formatTimestamp(e.ts_ms),
      page: e.page || "—",
      data_json: e.data_json || "—",
    }));

  document.getElementById("evGrid").data = rows;
  window.__behaviorTableRows = rows;

})().catch(e => {
  console.error(e);
  alert("Behavior report failed. Check console.");
});
</script>

<script>
  function canvasToB64ById(id) {
    const c = document.getElementById(id);
    if (!c) throw new Error("Missing canvas: " + id);
    return c.toDataURL("image/png").split(",")[1];
  }

  async function postSaveReport(payload) {
    const res = await fetch("/save_report.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify(payload),
    });
    const text = await res.text();
    let out;
    try { out = JSON.parse(text); } catch { out = { ok: false, error: text }; }
    if (!res.ok || !out.ok) throw new Error(out.error || "Save failed");
    return out;
  }

  document.getElementById("saveBtn").onclick = async () => {
    const payload = {
      category: "behavior",
      title: "Behavior Report Snapshot",
      table_title: "Recent Activity Events",
      commentary: document.querySelector("textarea")?.value || "",
      table: window.__behaviorTableRows || [],
      charts: [
        { title: "Activity Events by Type Across All Pages", b64: canvasToB64ById("eventsByType") },
        { title: "Top 10 Clicks by Page", b64: canvasToB64ById("clicksByPage") },
      ],
    };
    const out = await postSaveReport(payload);
    alert("Saved! " + out.view_url);
  };
</script>
</body>
</html>