// Create a file: node/basic-test.js
const WebSocket = require('ws');

// Simple WebSocket server for testing
const wss = new WebSocket.Server({ port: 3002 });

console.log('Test WebSocket server running on port 3002');

wss.on('connection', function connection(ws) {
  console.log('Client connected to test server');
  
  ws.on('message', function incoming(message) {
    console.log('Received: %s', message);
    ws.send('Echo: ' + message);
  });
  
  ws.send('Hello from test server');
});

// Keep the process running
process.stdin.resume();
console.log('Press Ctrl+C to terminate the test server');
