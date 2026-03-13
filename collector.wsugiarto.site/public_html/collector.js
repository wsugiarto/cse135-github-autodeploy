// Sessioning Data
function uuid() {
  return (crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(16).slice(2)}`);
}

function getCookie(name) {
  const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
  return m ? decodeURIComponent(m[1]) : null;
}
function setCookie(name, value, maxAgeSeconds) {
  document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; Path=/; Max-Age=${maxAgeSeconds}; SameSite=Lax`;
}

function getOrCreateSessionId() {
  const COOKIE = "cse135_sid";
  let sid = getCookie(COOKIE);
  if (!sid) {
    sid = uuid();
    setCookie(COOKIE, sid, 60 * 30);
  }
  return sid;
}
const SESSION_ID = getOrCreateSessionId();
const PAGEVIEW_ID = uuid();
function detectImagesAllowed(timeoutMs = 800) {
  return new Promise((resolve) => {
    let done = false;
    const img = new Image();
    const finish = (val) => {
      if (done) return;
      done = true;
      resolve(val);
    };
    img.onload = () => finish(true);
    img.onerror = () => finish(false);
    img.src ="data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA="; 
    setTimeout(() => finish(false), timeoutMs);
  });
}

function detectCssAllowed() {
  const el = document.createElement("div");
  el.style.width = "100px";
  document.body.appendChild(el);
  const w = getComputedStyle(el).width;
  document.body.removeChild(el);
  return w === "100px";
}

function getStaticData(imagesAllowed, cssAllowed) {
  const nav = navigator;
  const conn = nav.connection || nav.mozConnection || nav.webkitConnection;
  return {
    session_id: SESSION_ID,
    pageview_id: PAGEVIEW_ID,
    page: location.href,
    time_start: Date.now(),
    user_agent: nav.userAgent,
    language: nav.language,
    cookies_enabled: nav.cookieEnabled,
    js_enabled: true,
    images_enabled: imagesAllowed,
    css_enabled: cssAllowed,
    screen: {
      width: screen.width,
      height: screen.height,
    },
    window: {
      innerWidth: window.innerWidth,
      innerHeight: window.innerHeight,
      outerWidth: window.outerWidth,
      outerHeight: window.outerHeight,
    },
    network: conn
      ? { effectiveType: conn.effectiveType || null }
      : { effectiveType: null },
  };
}

function sendToEndpoint(path, obj) {
  const url = `https://collector.wsugiarto.site${path}`;
  const body = JSON.stringify(obj);

  const blob = new Blob([body], { type: "application/json" });
  const ok = navigator.sendBeacon(url, blob);
  if (!ok) {
    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body,
      keepalive: true,
    }).catch(() => {});
  }
}

window.addEventListener("load", async () => {
  const imagesAllowed = await detectImagesAllowed();
  const cssAllowed = detectCssAllowed();
  const staticPayload = getStaticData(imagesAllowed, cssAllowed);
  const nav = performance.getEntriesByType("navigation")[0];
  let perf = null;
  if (nav) {
    const startRelMs = 0;
    const startAbsMs = performance.timeOrigin;
    const endRelMs = nav.loadEventEnd;
    const endAbsMs = startAbsMs + endRelMs;
    const totalLoadMs = endRelMs;
    perf = {
      timing: nav.toJSON ? nav.toJSON() : nav,
      page_start_loading_ms: { relative_start: startRelMs, absolute_start: startAbsMs },
      page_end_loading_ms: { relative_end: endRelMs, absolute_end: endAbsMs },
      load_time_ms: totalLoadMs,
    };
  } 
  staticPayload.performance = perf;
  sendToEndpoint("/log.php", staticPayload);
});

//ACtivity

const ACTIVITY_ENDPOINT = "/log.php";
const ACTIVITY_FLUSH_MS = 1000;          
const IDLE_THRESHOLD_MS = 2000;        

let activityQueue = [];
let lastActivityTs = Date.now();
let idleStartTs = null;

function pushActivity(evt) {
  activityQueue.push({
    session_id: SESSION_ID,
    pageview_id: PAGEVIEW_ID,
    page: location.href,
    time_start: Date.now(),
    ...evt,
  });
}

function flushActivity(reason = "interval") {
  if (activityQueue.length === 0) return;
  const payload = {
    kind: "activity_batch",
    session_id: SESSION_ID,
    pageview_id: PAGEVIEW_ID,
    page: location.href,
    sent_ts: Date.now(),
    reason,
    events: activityQueue,
  };

  activityQueue = [];
  sendToEndpoint(ACTIVITY_ENDPOINT, payload);
}

setInterval(() => flushActivity("interval"), ACTIVITY_FLUSH_MS);

//cursor coordinates
const MOUSEMOVE_THROTTLE_MS = 250; 
const SCROLL_THROTTLE_MS = 250; 
let lastMouseSentTs = 0;
let lastScrollSentTs = 0
document.addEventListener("mousemove", (e) => {
  lastActivityTs = Date.now();
  if (idleStartTs !== null) {
    const idleEnd = lastActivityTs;
    pushActivity({
      type: "idle_end",
      idle_end_ts: idleEnd,
      idle_duration_ms: idleEnd - idleStartTs,
    });
    idleStartTs = null;
  }
  const now = lastActivityTs;
  if (now - lastMouseSentTs < MOUSEMOVE_THROTTLE_MS) return;
  lastMouseSentTs = now;
  pushActivity({
    type: "mousemove",
    x: e.clientX,
    y: e.clientY,
  });
}, { passive: true });

//Clicks
document.addEventListener("click", (e) => {
  lastActivityTs = Date.now();
  if (idleStartTs !== null) {
    const idleEnd = lastActivityTs;
    pushActivity({
      type: "idle_end",
      idle_end_ts: idleEnd,
      idle_duration_ms: idleEnd - idleStartTs,
    });
    idleStartTs = null;
  }

  pushActivity({
    type: "click",
    x: e.clientX,
    y: e.clientY,
    button: e.button, 
  });
}, { passive: true });

//coordinates of the scroll
window.addEventListener("scroll", () => {
  lastActivityTs = Date.now();
  if (idleStartTs !== null) {
    const idleEnd = lastActivityTs;
    pushActivity({
      type: "idle_end",
      idle_end_ts: idleEnd,
      idle_duration_ms: idleEnd - idleStartTs,
    });
    idleStartTs = null;
  }
  const now = lastActivityTs;
  if (now - lastScrollSentTs < SCROLL_THROTTLE_MS) return;
  lastScrollSentTs = now;
  pushActivity({
    type: "scroll",
    scrollX: window.scrollX,
    scrollY: window.scrollY,
  });
}, { passive: true });
//Keyboard
document.addEventListener("keydown", (e) => {
  lastActivityTs = Date.now();
  if (idleStartTs !== null) {
    const idleEnd = lastActivityTs;
    pushActivity({
      type: "idle_end",
      idle_end_ts: idleEnd,
      idle_duration_ms: idleEnd - idleStartTs,
    });
    idleStartTs = null;
  }

  pushActivity({
    type: "keydown",
    key: e.key,
    code: e.code,
  });
});

document.addEventListener("keyup", (e) => {
  lastActivityTs = Date.now();
  if (idleStartTs !== null) {
    const idleEnd = lastActivityTs;
    pushActivity({
      type: "idle_end",
      idle_end_ts: idleEnd,
      idle_duration_ms: idleEnd - idleStartTs,
    });
    idleStartTs = null;
  }

  pushActivity({
    type: "keyup",
    key: e.key,
    code: e.code,
  });
});
// Errors
window.addEventListener("error", (e) => {
  const err= e.error;
  pushActivity({
    type: "error",
    message: e.message || null,
    filename: e.filename || null,
    lineno: e.lineno || null,
    colno: e.colno || null,
    error: err
      ? {
          name: err.name ?? null,
          message: err.message ?? null,
          stack: err.stack ?? null,
        }: null,
  });
});

window.addEventListener("unhandledrejection", (e) => {
  pushActivity({
    type: "unhandledrejection",
    reason: (e.reason && (e.reason.stack || e.reason.message)) ? (e.reason.stack || e.reason.message) : String(e.reason),
  });
});

setInterval(() => {
  const now = Date.now();
  const inactiveFor = now - lastActivityTs;
  if (inactiveFor >= IDLE_THRESHOLD_MS && idleStartTs === null) {
    idleStartTs = lastActivityTs;
  }
}, 250);

// Page enter
pushActivity({
  type: "page_enter",
  page: location.href,
});


// Page leaving

let leaveSent = false;
function sendLeave(reason) {
  if (leaveSent) return;
  leaveSent = true;

  pushActivity({
    type: "page_leave",
    page: location.href,
    reason,
  });


  const payload = {
    kind: "activity_batch",
    reason,
    session_id: SESSION_ID,
    pageview_id: PAGEVIEW_ID,
    page: location.href,
    sent_ts: Date.now(),
    events: activityQueue,
  };

  activityQueue = [];
  try {
    const url = `https://collector.wsugiarto.site/log.php`;
    const blob = new Blob([JSON.stringify(payload)], { type: "application/json" });
    navigator.sendBeacon(url, blob);
  } catch {}
}

document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "hidden") {
    sendLeave("visibilitychange");
  }
});

window.addEventListener("beforeunload", () => {
  sendLeave("beforeunload");
});

window.addEventListener("pagehide", () => {
  sendLeave("pagehide");
});