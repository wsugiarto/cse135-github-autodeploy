const fs = require("fs");
const crypto = require("crypto");
const COOKIE = "NodeSessionID";
const dir = "/tmp";

function sessFile(sid) {
    return `${dir}/node_sess_${sid}.txt`;
}

function parseForm(body) {
    const m = body.match(/(?:^|&)name=([^&]*)/);
    return m ? decodeURIComponent(m[1].replace(/\+/g, " ")) : "";
}

module.exports = (req, res, {parseCookies, readBody, base}) => {
    const cookies = parseCookies(req);
    let sid = cookies[COOKIE];
    let setCookie = false;

    if (!sid) {
        sid = crypto.randomUUID();
        setCookie = true;
    }

    const headers = { "Content-Type": "text/html" };
    if (setCookie) {
        headers["Set-Cookie"] = `${COOKIE}=${sid}; Path=/`;
    }
    if (req.method === "POST") {
        return readBody(req, body => {
        const name = parseForm(body).trim();
        if (name) fs.writeFileSync(sessFile(sid), name);
        res.writeHead(200, headers);
        res.end(`
    <!DOCTYPE html>
    <html>
    <head>
    <title>NodeJS Sessioning</title>
    </head>
    <body>
    <h1>NodeJS Sessioning Save Page</h1>
    <form method="POST" action="${base}/state-nodejs">
    Name: <input type="text" name="name">
    <button type="submit">Save</button>
    </form>
    <br>
    <a href="${base}/state-2-nodejs">Goto Saved Data</a>
    </body>
    </html>`);
        });
    }

    res.writeHead(200, headers);
    res.end(`<!DOCTYPE html>
    <html>
    <head>
    <title>NodeJS Sessioning</title>
    </head>
    <body>
    <h1>NodeJS Sessioning Save Page</h1>
    <form method="POST" action="${base}/state-nodejs">
    Name: <input type="text" name="name">
    <button type="submit">Save</button>
    </form>
    <br>
    <a href="${base}/state-2-nodejs">Goto Saved Data</a>
    </body>
    </html>`);
};
