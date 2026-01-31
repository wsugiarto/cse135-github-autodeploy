module.exports = (req, res, { send }) => {
    let ip = req.headers["x-forwarded-for"] || req.socket.remoteAddress || "";
    ip = ip.split(",")[0].trim();
    if (ip.startsWith("::ffff:")) ip = ip.slice(7);
    send(res, 200, { "Content-Type": "text/html" }, `
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Hello HTML NodeJS</title>
</head>
<body>
    <h1 align="center">Hello HTML World</h1><hr>
    <p>This page was generated with the NodeJS programming language</p>
    <p>This program was generated at: ${new Date().toString()}</p>
    <p>Your current IP Address is: ${ip}</p>
</body>
</html>`);
};
