const fs = require('fs');
const http = require('http');
const https = require('https');

const listenHost = '0.0.0.0';
const listenPort = 8443;
const targetHost = '127.0.0.1';
const targetPort = 8000;

const pfx = fs.readFileSync(__dirname + '/local-https/ransapos-local.pfx');
const passphrase = process.env.RANSAPOS_HTTPS_PFX_PASSWORD || 'ransapos-local';

const server = https.createServer({ pfx, passphrase }, (req, res) => {
  const headers = { ...req.headers };
  headers.host = req.headers.host || `${targetHost}:${targetPort}`;
  headers['x-forwarded-proto'] = 'https';
  headers['x-forwarded-host'] = req.headers.host || '';
  headers['x-forwarded-ssl'] = 'on';
  headers['front-end-https'] = 'on';

  const proxyReq = http.request({
    host: targetHost,
    port: targetPort,
    method: req.method,
    path: req.url,
    headers,
  }, (proxyRes) => {
    const responseHeaders = { ...proxyRes.headers };
    if (responseHeaders.location && req.headers.host) {
      responseHeaders.location = String(responseHeaders.location)
        .replace(`http://${req.headers.host}`, `https://${req.headers.host}`)
        .replace(`http://${targetHost}:${targetPort}`, `https://${req.headers.host}`);
    }

    const contentType = String(responseHeaders['content-type'] || '');
    const shouldRewriteBody = req.headers.host
      && (contentType.includes('text/html') || contentType.includes('application/javascript') || contentType.includes('text/css'));

    if (!shouldRewriteBody) {
      res.writeHead(proxyRes.statusCode || 502, responseHeaders);
      proxyRes.pipe(res);
      return;
    }

    const chunks = [];
    proxyRes.on('data', (chunk) => chunks.push(chunk));
    proxyRes.on('end', () => {
      let body = Buffer.concat(chunks).toString('utf8');
      body = body
        .replaceAll(`http://${req.headers.host}`, `https://${req.headers.host}`)
        .replaceAll(`http://${targetHost}:${targetPort}`, `https://${req.headers.host}`);

      delete responseHeaders['content-length'];
      res.writeHead(proxyRes.statusCode || 502, responseHeaders);
      res.end(body);
    });
  });

  proxyReq.on('error', (error) => {
    res.writeHead(502, { 'content-type': 'text/plain; charset=utf-8' });
    res.end(`HTTPS proxy error: ${error.message}`);
  });

  req.pipe(proxyReq);
});

server.listen(listenPort, listenHost, () => {
  console.log(`Ransapos HTTPS proxy listening on https://${listenHost}:${listenPort}`);
});
