// Modify src/websocket/server.js to add better handling
const WebSocket = require('ws');
const http = require('http');
const express = require('express');
const { takeScreenshot } = require('../system/screenshot');

function startWebSocketServer(port = 3001) {
  return new Promise((resolve) => {
    const app = express();
    const server = http.createServer(app);
    
    // Add a test endpoint
    app.get('/test-screenshot', async (req, res) => {
      try {
        console.log('Test screenshot endpoint called');
        const result = await takeScreenshot();
        res.send(`
          <html>
            <body>
              <h1>Screenshot Test</h1>
              <p>Screenshot size: ${result.image.length} characters</p>
              <img src="data:image/webp;base64,${result.image}" style="max-width: 100%">
            </body>
          </html>
        `);
      } catch (err) {
        res.status(500).send(`Error: ${err.message}`);
      }
    });
    
    const wss = new WebSocket.Server({ 
      server,
      maxPayload: 100 * 1024 * 1024
    });
    
    wss.on('connection', (ws) => {
      console.log('Client connected');
      
      ws.on('message', async (message) => {
        console.log(`Received message of length: ${message.length}`);
        
        try {
          const data = JSON.parse(message.toString());
          console.log(`Processing command: ${data.command} (ID: ${data.command_id})`);
          
          if (data.command === 'screenshot') {
            // Add timeout protection for the whole screenshot process
            const timeoutPromise = new Promise((_, reject) => {
              setTimeout(() => reject(new Error('Screenshot operation timed out')), 25000);
            });
            
            try {
              // Race the screenshot process against timeout
              const result = await Promise.race([
                takeScreenshot(data.params || {}),
                timeoutPromise
              ]);
              
              result.command_id = data.command_id;
              console.log(`Screenshot completed for ID: ${data.command_id}, success: ${result.success}`);
              
              if (result.success) {
                console.log(`Sending screenshot response (${result.image.length} chars)`);
              } else {
                console.log(`Sending error response: ${result.error}`);
              }
              
              ws.send(JSON.stringify(result));
            } catch (error) {
              console.error('Screenshot processing error:', error);
              ws.send(JSON.stringify({
                success: false,
                command_id: data.command_id,
                error: `Screenshot failed: ${error.message}`
              }));
            }
          } else {
            // Handle other commands
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
    
    // Log any WebSocket server errors
    wss.on('error', (error) => {
      console.error('WebSocket server error:', error);
    });
  });
}

module.exports = { startWebSocketServer };
