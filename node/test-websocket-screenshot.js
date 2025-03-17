// Create a new file in node directory: test-websocket-screenshot.js
const WebSocket = require('ws');
const { v4: uuidv4 } = require('uuid');

// Create a WebSocket client
const ws = new WebSocket('ws://localhost:3001');

ws.on('open', function open() {
  console.log('Connected to WebSocket server');
  
  // Send a screenshot command
  const commandId = uuidv4();
  const command = {
    command: 'screenshot',
    command_id: commandId,
    params: {}
  };
  
  console.log(`Sending command with ID: ${commandId}`);
  ws.send(JSON.stringify(command));
});

ws.on('message', function incoming(data) {
  console.log('Received response:');
  const response = JSON.parse(data);
  console.log(`- Success: ${response.success}`);
  console.log(`- Command ID: ${response.command_id}`);
  if (response.image) {
    console.log(`- Image size: ${response.image.length} characters`);
  }
  if (response.error) {
    console.log(`- Error: ${response.error}`);
  }
  
  // Close the connection
  ws.close();
});

ws.on('error', function error(err) {
  console.error('WebSocket error:', err);
});
