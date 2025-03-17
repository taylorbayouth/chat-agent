const WebSocket = require('ws');
const http = require('http');
const express = require('express');
const { v4: uuidv4 } = require('uuid');
const { takeScreenshot } = require('../system/screenshot');

function startWebSocketServer(port = 3001) {
  return new Promise((resolve) => {
    const app = express();
    const server = http.createServer(app);
    
    const wss = new WebSocket.Server({ 
      server,
      maxPayload: 100 * 1024 * 1024
    });
    
    wss.on('connection', (ws) => {
      console.log('Client connected');
      
      // Handle incoming messages - THIS IS CRITICAL
      ws.on('message', async (message) => {
        // Log every message received
        console.log(`Received WebSocket message of length: ${message.length}`);
        
        try {
          // Parse the message
          const data = JSON.parse(message.toString());
          console.log(`Processing command: ${data.command} with ID: ${data.command_id}`);
          
          // Handle screenshot command
          if (data.command === 'screenshot') {
            console.log('Taking screenshot...');
            const result = await takeScreenshot(data.params || {});
            result.command_id = data.command_id;
            
            // Send response with the same command_id
            console.log(`Sending screenshot response for ID: ${data.command_id}`);
            const responseStr = JSON.stringify(result);
            console.log(`Response size: ${responseStr.length} bytes`);
            ws.send(responseStr);
          } else {
            // Handle other commands
            console.log(`Sending generic response for command: ${data.command}`);
            ws.send(JSON.stringify({
              success: true,
              command_id: data.command_id,
              message: `Command ${data.command} processed`
            }));
          }
        } catch (error) {
          console.error('Error processing message:', error);
          try {
            ws.send(JSON.stringify({
              success: false,
              error: error.message
            }));
          } catch (sendError) {
            console.error('Error sending error response:', sendError);
          }
        }
      });
    });
    
    server.listen(port, () => {
      console.log(`WebSocket server running on port ${port}`);
      resolve(wss);
    });
  });
}

module.exports = { startWebSocketServer };
