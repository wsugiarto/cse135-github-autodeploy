const http = require("http");
const url = require("url");

const helloHtml = require("./hello-html-nodejs");
const helloJson = require("./hello-json-nodejs");
const environment = require("./environment-nodejs");
const echo = require("./echo-nodejs");
const state = require("./state-nodejs");
const state2 = require("./state-2-nodejs");

const port = 3000;
const base = "/hw2/nodejs";

function parseCookies(req) {
  const out = {};
  const raw = req.headers.cookie || "";
  raw.split(";").forEach(p => {
    const s = p.trim();
    if (!s) return;
    const i = s.indexOf("=");
    if (i === -1) return;
    out[s.slice(0, i)] = decodeURIComponent(s.slice(i + 1));
  });
  return out;
}

function readBody(req, cb) {
  let body = "";
  req.on("data", c => body += c);
  req.on("end", () => cb(body));
}

function send(res, code, headers, body) {
  res.writeHead(code, headers);
  res.end(body);
}

function relPath(pathname) {
  if (pathname.startsWith(base)) {
    const p = pathname.slice(base.length);
    return p === "" ? "/" : p;
  }
  return pathname;
}

const context = {parseCookies, readBody, send, base};
http.createServer((req, res) => {
  const parsed = url.parse(req.url, true);
  const p = relPath(parsed.pathname);

  if (p === "/hello-html-nodejs") return helloHtml(req, res, context, parsed);
  if (p === "/hello-json-nodejs") return helloJson(req, res, context, parsed);
  if (p === "/environment-nodejs") return environment(req, res, context, parsed);
  if (p === "/echo-nodejs") return echo(req, res, context, parsed);
  if (p === "/state-nodejs") return state(req, res, context, parsed);
  if (p === "/state-2-nodejs") return state2(req, res, context, parsed);

  return send(res, 404, { "Content-Type": "text/plain" }, "Not Found");
}).listen(port);
