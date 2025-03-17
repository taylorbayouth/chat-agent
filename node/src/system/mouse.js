const robot = require('robotjs');

async function click({ x, y, button = 'left', double = false }) {
  try {
    // Move mouse to position
    robot.moveMouse(x, y);
    
    // Perform click
    if (double) {
      robot.mouseClick(button, true);
    } else {
      robot.mouseClick(button);
    }
    
    return { success: true };
  } catch (error) {
    console.error('Mouse click error:', error);
    return { 
      success: false, 
      error: error.message 
    };
  }
}

async function drag({ startX, startY, endX, endY }) {
  try {
    // Move to start position
    robot.moveMouse(startX, startY);
    
    // Perform drag
    robot.mouseToggle('down');
    
    // Smooth movement
    const steps = 10;
    const dx = (endX - startX) / steps;
    const dy = (endY - startY) / steps;
    
    for (let i = 1; i <= steps; i++) {
      const x = Math.round(startX + dx * i);
      const y = Math.round(startY + dy * i);
      robot.moveMouse(x, y);
      
      // Small delay for smoother motion
      await new Promise(resolve => setTimeout(resolve, 5));
    }
    
    robot.mouseToggle('up');
    
    return { success: true };
  } catch (error) {
    console.error('Mouse drag error:', error);
    return { 
      success: false, 
      error: error.message 
    };
  }
}

module.exports = { click, drag };
