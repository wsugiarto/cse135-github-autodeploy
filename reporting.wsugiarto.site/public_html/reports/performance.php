<?php
require __DIR__ . "/../auth.php";
require_section("reports");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Performance Report</title>
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

  <h1>Performance Report</h1>
  <noscript>Please enable JavaScript in order to achieve full page functionality.</noscript>
  <div class="card">
    <h2>Top 10 Average Load Time by Page</h2>
    <div class="chart-wrap"><canvas id="avgLoadByPage"></canvas></div>
  </div>

  <div class="card">
    <h2>Distribution of Load Times Across Pages</h2>
    <div class="chart-wrap"><canvas id="loadBuckets"></canvas></div>
  </div>

  <div class="card">
    <h2>Recent Pageviews And Load Times</h2>
    <zing-grid id="pvGrid" caption="Recently Accessed Pages" pager page-size="15" sort filter>
      <zg-colgroup>
        <zg-column index="id" header="ID" type="number" width="70"></zg-column>
        <zg-column index="page" header="Page"></zg-column>
        <zg-column index="load_time_display" header="Load Time" width="120" sort-asc></zg-column>
        <zg-column index="received_display" header="Received At" width="180"></zg-column>
        <zg-column index="session_id" header="Session ID" width="110"></zg-column>
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
const BUCKET_COLORS = ["#59a14f","#76b7b2","#f28e2b","#e15759","#b07aa1"];

function truncateLabel(str, max) {
  if (!str) return "(unknown)";
  return str.length > max ? str.slice(0, max - 1) + "…" : str;
}

function formatLoadTime(ms) {
  if (ms == null || isNaN(ms)) return "—";
  if (ms < 1000) return Math.round(ms) + " ms";
  return (ms / 1000).toFixed(2) + " s";
}

function formatDate(str) {
  if (!str) return "—";
  const d = new Date(String(str).replace(" ", "T"));
  if (isNaN(d.getTime())) return String(str);
  return d.toLocaleString("en-US", { month:"short", day:"numeric", hour:"2-digit", minute:"2-digit" });
}

async function fetchJson(url) {
  const r = await fetch(url, { credentials: "same-origin" });
  if (!r.ok) throw new Error(`${url} -> ${r.status}`);
  return r.json();
}

function safeLoadMs(row) {
  if (!row) return null;
  let p = row.performance_json ?? null;
  if (p == null || p === "") return null;
  if (typeof p === "string") {
    try { p = JSON.parse(p); } catch { return null; }
  }
  if (!p || typeof p !== "object") return null;
  if (typeof p.load_time_ms === "number") return p.load_time_ms;
  if (typeof p.page_end_loading_ms?.relative_end === "number") return p.page_end_loading_ms.relative_end;
  if (typeof p.timing?.duration === "number") return p.timing.duration;
  return null;
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
  const pageviews = await fetchJson("/api/pageviews");

  const recent = pageviews.slice(0, 200).map(p => {
    const ms = safeLoadMs(p);
    return {
      id: p.id,
      page: p.page || "—",
      load_time_display: formatLoadTime(ms),
      received_display: formatDate(p.received_at),
      session_id: p.session_id || "—",
      pageview_id: p.pageview_id || "—",
    };
  });
  document.getElementById("pvGrid").data = recent;
  window.__perfTableRows = recent;

  const byPage = new Map();
  for (const p of pageviews) {
    const ms = safeLoadMs(p);
    if (!p.page || ms == null || Number.isNaN(ms)) continue;
    const cur = byPage.get(p.page) || { sum: 0, cnt: 0 };
    cur.sum += ms; cur.cnt += 1;
    byPage.set(p.page, cur);
  }
  const avgPairs = [];
  for (const [page, v] of byPage.entries()) {
    if (v.cnt >= 1) avgPairs.push([page, v.sum / v.cnt]);
  }
  const topAvg = topN(avgPairs, 10);

  new Chart(document.getElementById("avgLoadByPage"), {
    type: "bar",
    data: {
      labels: topAvg.map(x => truncateLabel(x[0].replace(/^https?:\/\//, ""), 55)),
      datasets: [{
        label: "Avg Load Time",
        data: topAvg.map(x => Math.round(x[1])),
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
          title: { display: true, text: "Average Load Time (ms)", font: { size: 13 } },
          ticks: { precision: 0 },
          grid: { color: "rgba(0,0,0,.06)" },
        },
        y: {
          grid: { display: false },
          afterFit: function(axis) {
            axis.width = Math.max(axis.width, 250);
          },
        }
      },
      plugins: {
        ...chartDefaults.plugins,
        tooltip: {
          ...chartDefaults.plugins.tooltip,
          callbacks: {
            title: function(items) {
              // CHANGED: strip protocol in tooltip
              return topAvg[items[0].dataIndex][0].replace(/^https?:\/\//, "");
            },
            label: function(ctx) {
              return "Avg: " + formatLoadTime(ctx.raw);
            }
          }
        }
      }
    }
  });

  const buckets = [
    { label: "0 – 500 ms", min: 0, max: 500, count: 0 },
    { label: "500 ms – 1 s", min: 500, max: 1000, count: 0 },
    { label: "1 – 2 s", min: 1000, max: 2000, count: 0 },
    { label: "2 – 4 s", min: 2000, max: 4000, count: 0 },
    { label: "4 s +", min: 4000, max: Infinity, count: 0 },
  ];
  for (const p of pageviews) {
    const ms = safeLoadMs(p);
    if (ms == null || Number.isNaN(ms)) continue;
    for (const b of buckets) {
      if (ms >= b.min && ms < b.max) { b.count++; break; }
    }
  }

  new Chart(document.getElementById("loadBuckets"), {
    type: "bar",
    data: {
      labels: buckets.map(b => b.label),
      datasets: [{
        label: "Pages",
        data: buckets.map(b => b.count),
        backgroundColor: BUCKET_COLORS,
        borderRadius: 4,
        maxBarThickness: 64,
      }]
    },
    options: {
      ...chartDefaults,
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: "Number of Pageviews", font: { size: 13 } },
          ticks: { precision: 0 },
          grid: { color: "rgba(0,0,0,.06)" },
        },
        x: {
          title: { display: true, text: "Load Time Range", font: { size: 13 } },
          grid: { display: false },
        }
      },
    }
  });

})().catch(e => {
  console.error(e);
  alert("Performance report failed. Check console.");
});

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
    category: "performance",
    title: "Performance Report Snapshot",
    table_title: "Recent Pageviews And Load Times",
    commentary: document.querySelector("textarea")?.value || "",
    table: window.__perfTableRows || [],
    charts: [
      { title: "Top 10 Average Load Time by Page", b64: canvasToB64ById("avgLoadByPage") },
      { title: "Distribution of Load Times Across Pages", b64: canvasToB64ById("loadBuckets") },
    ],
  };
  const out = await postSaveReport(payload);
  alert("Saved! " + out.view_url);
};
</script>
</body>
</html>
