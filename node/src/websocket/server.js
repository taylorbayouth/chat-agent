const WebSocket = require('ws');
const http = require('http');

function startWebSocketServer(port = 3001) {
  // Create an HTTP server
  const server = http.createServer();
  
  // Create WebSocket server attached to the HTTP server
  const wss = new WebSocket.Server({ server });

  // Connection event handler
  wss.on('connection', (ws) => {
    console.log('New WebSocket client connected');
    
    // Message event handler
    ws.on('message', async (rawMessage) => {
      try {
        // Parse the incoming message
        const message = JSON.parse(rawMessage.toString());
        console.log('Received message:', message);
        
        // Handle the command
        const response = await handleCommand(message);
        
        // Send response back to client
        ws.send(JSON.stringify(response));
      } catch (error) {
        console.error('Error processing WebSocket message:', error);
        ws.send(JSON.stringify({ 
          error: true, 
          message: error.message 
        }));
      }
    });

    // Error event handler
    ws.on('error', (error) => {
      console.error('WebSocket client error:', error);
    });

    // Close event handler
    ws.on('close', () => {
      console.log('WebSocket client disconnected');
    });
  });

  // Server error handling
  server.on('error', (error) => {
    console.error('HTTP server error:', error);
  });

  // Start the server
  server.listen(port, () => {
    console.log(`WebSocket server running on port ${port}`);
  });

  return wss;
}

// Command handling function
async function handleCommand(data) {
  console.log('Handling command:', data);

  try {
    switch (data.command) {
      case 'screenshot':
        return await require('../system/screenshot').takeScreenshot(data.params || {});
      case 'click':
        return await require('../system/mouse').click(data.params || {});
      case 'type':
        return await require('../system/keyboard').typeText(data.params || {});
      default:
        throw new Error(`Unknown command: ${data.command}`);
    }
  } catch (error) {
    console.error(`Error in handleCommand for ${data.command}:`, error);
    return {
      error: true,
      message: error.message
    };
  }
}

module.exports = { startWebSocketServer };
