// module.exports = (req, res) => {
//   res.writeHead(200, { "Content-Type": "text/html", "Cache-Control": "no-cache" });

//   let out = `<!DOCTYPE html>
// <html>
// <head>
// <title>Environment Variables</title>
// </head>
// <body>
// <h1 align="center">Environment Variables</h1>
// <hr>`;

//   Object.keys(process.env).sort().forEach(k => {
//     out += `<b>${k}:</b> ${process.env[k]}<br />`;
//   });

//   out += `</body></html>`;
//   res.end(out);
// };

module.exports = (req, res) => {
    let ip = req.headers["x-forwarded-for"] || req.socket.remoteAddress || "";
    ip = ip.split(",")[0].trim();
    if (ip.startsWith("::ffff:")) ip = ip.slice(7);
    res.writeHead(200, { "Content-Type": "text/html" });

    let out = `
    <!DOCTYPE html>
    <html>
    <head>
    <title>Environment Variables</title>
    </head>
    <body>
    <h1 align="center">Environment Variables</h1
    ><hr>`;

    Object.keys(req.headers).sort().forEach(k => {
      out += `<b>${k}:</b> ${req.headers[k]}<br>`;
    });

    out += `
    <b>Method:</b> ${req.method}<br>
    <b>URL:</b> ${req.url}<br>
    <b>HTTP Version:</b> ${req.httpVersion}<br>
    <b>Remote Address:</b> ${ip}<br>
    </body></html>`;

    res.end(out);
};
