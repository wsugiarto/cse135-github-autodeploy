const fs = require("fs");

const COOKIE = "NodeSessionID";
const dir = "/tmp";

function sessFile(sid) {
  return `${dir}/node_sess_${sid}.txt`;
}

module.exports = (req, res, { parseCookies, base }, parsed) => {
    const cookies = parseCookies(req);
    const sid = cookies[COOKIE] || "";
    const file = sid ? sessFile(sid) : "";

    if (parsed.query && parsed.query.clear && file && fs.existsSync(file)) {
        try { fs.unlinkSync(file); } catch {}
    }

    let name = "";
    if (file && fs.existsSync(file)) {
        name = fs.readFileSync(file, "utf8").trim();
    }

    res.writeHead(200, { "Content-Type": "text/html" });
    res.end(`<!DOCTYPE html>
    <html>
    <head>
    <title>NodeJS Sessioning</title>
    </head>
    <body>
    <h1>NodeJS Sessioning view Data</h1>
    <p><b>Saved Name:</b> ${name ? name : "Not set"}</p>
    <a href="${base}/state-nodejs">Back to Save Page</a><br><br>
    <a href="${base}/state-2-nodejs?clear=CLEARDATA">Clear Data</a>
    </body>
    </html>`);
};
