module.exports = (req, res, { readBody }, parsed) => {
    let ip = req.headers["x-forwarded-for"] || req.socket.remoteAddress || "";
    ip = ip.split(",")[0].trim();
    if (ip.startsWith("::ffff:")) ip = ip.slice(7);
    readBody(req, body => {
        const protocol = "HTTP/" + (req.httpVersion || "1.1");

        res.writeHead(200, { "Content-Type": "text/html" });
        res.end(`<!DOCTYPE html>
        <html>
        <head>
        <title>General Request Echo</title>
        </head>
        <body>
        <h1 align="center">General Request Echo NodeJS Version</h1>
        <hr>
        <p><b>HTTP Protocol:</b> ${protocol}</p>
        <p><b>HTTP Method:</b> ${req.method}</p>
        <p><b>Hostname:</b> ${req.headers.host || ""}</p>
        <p><b>Time:</b> ${new Date().toString()}</p>
        <p><b>User Agent:</b> ${req.headers["user-agent"] || ""}</p>
        <p><b>IP Address:</b> ${ip}</p>
        <p><b>Query String:</b></p>
        <p>${parsed.query ? JSON.stringify(parsed.query) : ""}</p>
        <p><b>Message Body:</b></p>
        <p>${body || ""}</p>
        </body>
        </html>`);
        });
};
