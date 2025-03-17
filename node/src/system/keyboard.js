const robot = require('robotjs');

const KEY_MAPPING = {
  '/': 'slash',
  '\\': 'backslash',
  'alt': 'alt',
  'arrowdown': 'down',
  'arrowleft': 'left',
  'arrowright': 'right',
  'arrowup': 'up',
  'backspace': 'backspace',
  'capslock': 'capslock',
  'cmd': 'command',
  'ctrl': 'control',
  'delete': 'delete',
  'end': 'end',
  'enter': 'enter',
  'esc': 'escape',
  'home': 'home',
  'insert': 'insert',
  'option': 'alt',
  'pagedown': 'pagedown',
  'pageup': 'pageup',
  'shift': 'shift',
  'space': 'space',
  'super': 'command',
  'tab': 'tab',
  'win': 'command'
};

async function typeText({ text }) {
  try {
    // Type the text
    robot.typeString(text);
    return { success: true };
  } catch (error) {
    console.error('Keyboard error:', error);
    return { 
      success: false, 
      error: error.message 
    };
  }
}

async function pressKey({ keys }) {
  try {
    for (const key of keys) {
      // Map the key if necessary
      const mappedKey = KEY_MAPPING[key.toLowerCase()] || key;
      
      // Press the key
      robot.keyTap(mappedKey);
      
      // Small delay between keypresses
      await new Promise(resolve => setTimeout(resolve, 10));
    }
    
    return { success: true };
  } catch (error) {
    console.error('Key press error:', error);
    return { 
      success: false, 
      error: error.message 
    };
  }
}

module.exports = { typeText, pressKey };
