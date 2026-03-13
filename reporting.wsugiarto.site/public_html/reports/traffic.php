<?php
require __DIR__ . "/../auth.php";
require_section("reports");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Traffic Report</title>
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

  <h1>Traffic Report</h1>
  <noscript>Please enable JavaScript in order to achieve full page functionality.</noscript>
  <div class="card">
    <h2>Top 10 Pageviews by Page</h2>
    <div class="chart-wrap"><canvas id="pvByPage"></canvas></div>
  </div>

  <div class="card">
    <h2>Daily Pageviews Across Site Over Time</h2>
    <div class="chart-wrap"><canvas id="pvByDay"></canvas></div>
  </div>

  <div class="card">
    <h2>All Time Top Pages </h2>
    <zing-grid id="topPagesGrid" caption="All Time Top Pages " pager page-size="25" sort filter>
      <zg-colgroup>
        <zg-column index="rank" header="#" type="number" width="50"></zg-column>
        <zg-column index="page" header="Page Path"></zg-column>
        <zg-column index="count" header="Pageviews" type="number" width="120" sort-desc></zg-column>
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
const PALETTE = ["#4e79a7","#f28e2b","#e15759","#76b7b2","#59a14f","#edc948","#b07aa1","#ff9da7","#9c755f","#bab0ac"];

function truncateLabel(str, max) {
  if (!str) return "(unknown)";
  let clean = str.replace(/^https?:\/\//, "");
  return clean.length > max ? clean.slice(0, max - 1) + "…" : clean;
}

async function fetchJson(url) {
  const r = await fetch(url, { credentials: "same-origin" });
  if (!r.ok) throw new Error(`${url} -> ${r.status}`);
  return r.json();
}

function topN(entries, n) {
  return entries.sort((a,b)=>b[1]-a[1]).slice(0,n);
}

function dayKey(receivedAt) {
  if (!receivedAt) return null;
  const s = String(receivedAt).replace(" ", "T");
  const d = new Date(s);
  if (Number.isNaN(d.getTime())) return null;
  return d.toISOString().slice(0,10);
}

function formatDayLabel(isoDay) {
  const d = new Date(isoDay + "T00:00:00");
  return d.toLocaleDateString("en-US", { month: "short", day: "numeric" });
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
  const pageviews = await fetchJson("/api/pageviews");

  const pageCounts = new Map();
  for (const p of pageviews) {
    const page = p.page || "(unknown)";
    pageCounts.set(page, (pageCounts.get(page) || 0) + 1);
  }
  const topPages = topN([...pageCounts.entries()], 10);

  new Chart(document.getElementById("pvByPage"), {
    type: "bar",
    data: {
      labels: topPages.map(p => truncateLabel(p[0].replace(/^https?:\/\//, ""), 55)),
      datasets: [{
        label: "Pageviews",
        data: topPages.map(p => p[1]),
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
          title: { display: true, text: "Pageview Count", font: { size: 13 } },
          ticks: { precision: 0 },
          grid: { color: "rgba(0,0,0,.06)" },
        },
        y: {
          grid: { display: false },
          afterFit: function(axis) {
            axis.width = Math.max(axis.width, 220);
          },
        }
      },
      plugins: {
        ...chartDefaults.plugins,
        tooltip: {
          ...chartDefaults.plugins.tooltip,
          callbacks: {
            title: function(items) {
              return topPages[items[0].dataIndex][0].replace(/^https?:\/\//, "");
            }
          }
        }
      }
    }
  });

  const byDay = new Map();
  for (const p of pageviews) {
    const k = dayKey(p.received_at);
    if (!k) continue;
    byDay.set(k, (byDay.get(k) || 0) + 1);
  }
  const dayPairs = [...byDay.entries()].sort((a,b)=>a[0].localeCompare(b[0]));

  new Chart(document.getElementById("pvByDay"), {
    type: "line",  
    data: {
      labels: dayPairs.map(d => formatDayLabel(d[0])),
      datasets: [{
        label: "Pageviews",
        data: dayPairs.map(d => d[1]),
        borderColor: "#4e79a7",
        backgroundColor: "rgba(78,121,167,.1)",
        fill: true,
        tension: 0.3,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: "#4e79a7",
      }]
    },
    options: {
      ...chartDefaults,
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: "Pageviews", font: { size: 13 } },
          ticks: { precision: 0 },
          grid: { color: "rgba(0,0,0,.06)" },
        },
        x: {
          title: { display: true, text: "Date", font: { size: 13 } },
          ticks: { maxRotation: 45, minRotation: 0 },
          grid: { display: false },
        }
      },
    }
  });

  const tableRows = topPages.map(([page, count], i) => ({
    rank: i + 1,
    page,
    count,
  }));
  document.getElementById("topPagesGrid").data = tableRows;
  window.__trafficTableRows = tableRows;

})().catch(e => {
  console.error(e);
  alert("Traffic report failed. Check console.");
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
      category: "traffic",
      title: "Traffic Report Snapshot",
      table_title: "All Time Top Pages",
      commentary: document.querySelector("textarea")?.value || "",
      table: window.__trafficTableRows || [],
      charts: [
        { title: "Top 10 Pageviews by Page", b64: canvasToB64ById("pvByPage") },
        { title: "Daily Pageviews Across Site Over Time", b64: canvasToB64ById("pvByDay") },
      ],
    };
    const out = await postSaveReport(payload);
    alert("Saved! " + out.view_url);
  };
</script>
</body>
</html>