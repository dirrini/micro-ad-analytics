const { WebSocketServer } = require('ws');
const http = require('http');

// Create a standard HTTP server to handle both WebSockets and incoming webhooks from PHP
const server = http.createServer((req, res) => {
  if (req.method === 'POST' && req.url === '/broadcast') {
    let body = '';
    req.on('data', chunk => { body += chunk.toString(); });
    req.on('end', () => {
      // Broadcast the fresh tracking event data to all connected browser dashboards
      wss.clients.forEach(client => {
        if (client.readyState === 1) {
          client.send(body);
        }
      });
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ status: 'broadcasted' }));
    });
  } else {
    res.writeHead(404);
    res.end();
  }
});

const wss = new WebSocketServer({ noServer: true });

server.on('upgrade', (request, socket, head) => {
  wss.handleUpgrade(request, socket, head, (ws) => {
    wss.emit('connection', ws, request);
  });
});

server.listen(8085, () => {
  console.log('WebSocket broadcast microservice running on port 8087');
});