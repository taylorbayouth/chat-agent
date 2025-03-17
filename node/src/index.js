const WebSocket = require('ws');
const http = require('http');

console.log('Starting AI Agent System Service...');

// Create HTTP server
const server = http.createServer();

// Create WebSocket server
const wss = new WebSocket.Server({ server });

// Handle WebSocket connections
wss.on('connection', (ws) => {
    console.log('Client connected');
    
    ws.on('message', (message) => {
        console.log(`Received message: ${message}`);
        
        try {
            const data = JSON.parse(message);
            
            // Send a simple success response for any message
            const response = {
                command_id: data.command_id,
                success: true,
                message: 'Received message successfully'
            };
            
            // Special handling for screenshot command
            if (data.command === 'screenshot') {
                // Return a tiny transparent placeholder image
                response.image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAEPQJ+QY5sHAAAAABJRU5ErkJggg==';
            }
            
            ws.send(JSON.stringify(response));
        } catch (error) {
            console.error('Error handling message:', error);
            ws.send(JSON.stringify({ 
                success: false, 
                error: error.message 
            }));
        }
    });
    
    ws.on('close', () => {
        console.log('Client disconnected');
    });
});

// Start the server
server.listen(3001, () => {
    console.log('WebSocket server running on port 3001');
});
