module.exports = (req, res) => {
    let ip = req.headers["x-forwarded-for"] || req.socket.remoteAddress || "";
    ip = ip.split(",")[0].trim();
    if (ip.startsWith("::ffff:")) ip = ip.slice(7);
    const msg = {
        title: "Hello, NodeJS!",
        heading: "Hello, NodeJS!",
        message: "This page was generated with the NodeJS programming language",
        time: new Date().toString(),
        IP: ip
    };

    res.writeHead(200, { "Content-Type": "application/json", "Cache-Control": "no-cache" });
    res.end(JSON.stringify(msg));
};
