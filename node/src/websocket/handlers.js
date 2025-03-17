const { takeScreenshot } = require('../system/screenshot');
const { click, drag } = require('../system/mouse');
const { typeText, pressKey } = require('../system/keyboard');

// Utility function to split large responses
function splitIntoChunks(data, maxSize = 500 * 1024) { // 500KB chunks
  const chunks = [];
  let index = 0;
  
  while (index < data.length) {
    chunks.push(data.slice(index, index + maxSize));
    index += maxSize;
  }
  
  return chunks;
}

function setupSystemHandlers(wss) {
  wss.on('connection', (ws) => {
    console.log(`Client connected: ${ws.id}`);
    
    ws.on('message', async (message) => {
      try {
        console.log(`Received message from client ${ws.id}`);
        let data;
        
        try {
          data = JSON.parse(message);
        } catch (parseError) {
          console.error('Failed to parse message:', parseError);
          ws.send(JSON.stringify({
            success: false,
            error: 'Invalid JSON message'
          }));
          return;
        }
        
        const { command, command_id, params = {} } = data;
        
        console.log(`Processing command: ${command}, id: ${command_id}`);
        
        let response = {
          command_id,
          success: false,
          error: 'Unknown command'
        };
        
        // Route the command to the appropriate handler
        switch (command) {
          case 'screenshot':
            console.log('Taking screenshot...');
            response = await handleScreenshot(params, command_id);
            console.log(`Screenshot response ready: success=${response.success}`);
            break;
          case 'click':
            response = await handleClick(params, command_id);
            break;
          case 'type':
            response = await handleType(params, command_id);
            break;
          case 'keypress':
            response = await handleKeypress(params, command_id);
            break;
          case 'move':
            response = await handleMove(params, command_id);
            break;
          case 'scroll':
            response = await handleScroll(params, command_id);
            break;
          case 'drag':
            response = await handleDrag(params, command_id);
            break;
          default:
            console.error(`Unknown command: ${command}`);
        }
        
        // Send response back to client
        if (response.image && response.image.length > 1000) {
          console.log(`Sending large response (${response.image.length} bytes)`);
        } else {
          console.log(`Sending response for command: ${command}`);
        }
        
        ws.send(JSON.stringify(response));
      } catch (error) {
        console.error('Error handling message:', error);
        
        // Try to extract command_id from the message
        let command_id = null;
        try {
          const data = JSON.parse(message);
          command_id = data.command_id;
        } catch (e) {
          // Ignore parsing error
        }
        
        // Send error response
        ws.send(JSON.stringify({
          command_id,
          success: false,
          error: error.message
        }));
      }
    });
  });
}

// Merged implementation of handleScreenshot with both timeout and chunking
async function handleScreenshot(params, command_id) {
  try {
    console.log('Executing screenshot with params:', params);
    
    // Set a more aggressive timeout to ensure we respond quickly
    const timeoutPromise = new Promise((_, reject) => {
      setTimeout(() => reject(new Error('Screenshot operation timed out')), 5000);
    });
    
    // Run the screenshot with a timeout
    const result = await Promise.race([
      takeScreenshot(params),
      timeoutPromise
    ]);
    
    console.log(`Screenshot taken: success=${result.success}, image size=${result.image ? result.image.length : 'none'}`);
    
    if (result.success && result.image) {
      // For large images, send in chunks if needed
      if (result.image.length > 500 * 1024) { // If larger than 500KB
        const chunks = splitIntoChunks(result.image);
        console.log(`Splitting image into ${chunks.length} chunks`);
        
        // For now, we're not implementing chunk transfer - just send the whole image
        // This might be large but should work for testing
        return {
          command_id,
          success: true,
          image: result.image,
          width: result.width,
          height: result.height,
          format: result.format
        };
      }
      
      // For smaller images, send directly
      return {
        command_id,
        success: true,
        image: result.image,
        width: result.width,
        height: result.height,
        format: result.format
      };
    } else {
      console.error('Screenshot failed:', result.error);
      return {
        command_id,
        success: false,
        error: result.error || 'Unknown screenshot error'
      };
    }
  } catch (error) {
    console.error('Screenshot error:', error);
    
    // Return a fallback response with an error
    return {
      command_id,
      success: false,
      error: error.message
    };
  }
}


async function handleClick(params, command_id) {
  try {
    console.log('Executing click with params:', params);
    const result = await click(params);
    return {
      command_id,
      ...result
    };
  } catch (error) {
    console.error('Click error:', error);
    return {
      command_id,
      success: false,
      error: error.message
    };
  }
}

async function handleType(params, command_id) {
  try {
    console.log('Executing type with params:', params);
    const result = await typeText(params);
    return {
      command_id,
      ...result
    };
  } catch (error) {
    console.error('Type error:', error);
    return {
      command_id,
      success: false,
      error: error.message
    };
  }
}

async function handleKeypress(params, command_id) {
  try {
    console.log('Executing keypress with params:', params);
    const result = await pressKey(params);
    return {
      command_id,
      ...result
    };
  } catch (error) {
    console.error('Keypress error:', error);
    return {
      command_id,
      success: false,
      error: error.message
    };
  }
}

async function handleMove(params, command_id) {
  try {
    console.log('Executing move with params:', params);
    const { x, y } = params;
    const robot = require('robotjs');
    
    robot.moveMouse(x, y);
    
    return {
      command_id,
      success: true
    };
  } catch (error) {
    console.error('Move error:', error);
    return {
      command_id,
      success: false,
      error: error.message
    };
  }
}

async function handleScroll(params, command_id) {
  try {
    console.log('Executing scroll with params:', params);
    const { scroll_x = 0, scroll_y = 0 } = params;
    const robot = require('robotjs');
    
    // RobotJS scroll is implemented differently depending on the platform
    robot.scrollMouse(scroll_x, scroll_y);
    
    return {
      command_id,
      success: true
    };
  } catch (error) {
    console.error('Scroll error:', error);
    return {
      command_id,
      success: false,
      error: error.message
    };
  }
}

async function handleDrag(params, command_id) {
  try {
    console.log('Executing drag with params:', params);
    const result = await drag(params);
    return {
      command_id,
      ...result
    };
  } catch (error) {
    console.error('Drag error:', error);
    return {
      command_id,
      success: false,
      error: error.message
    };
  }
}

module.exports = { setupSystemHandlers };
