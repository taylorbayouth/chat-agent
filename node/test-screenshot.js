const screenshot = require('screenshot-desktop');
const sharp = require('sharp');
const fs = require('fs');

async function test() {
  try {
    console.log('Taking screenshot...');
    const buffer = await screenshot();
    console.log('Screenshot taken, size:', buffer.length);
    
    // Save the raw screenshot for inspection
    fs.writeFileSync('test-raw.png', buffer);
    console.log('Raw screenshot saved to test-raw.png');
    
    // Process with sharp
    console.log('Processing with sharp...');
    const processed = await sharp(buffer)
      .webp({ quality: 80 })
      .toBuffer();
    
    console.log('Processing complete, size:', processed.length);
    
    // Save the processed image
    fs.writeFileSync('test-processed.webp', processed);
    console.log('Processed image saved to test-processed.webp');
    
    console.log('Test successful!');
  } catch (error) {
    console.error('Test failed:', error);
  }
}

test();
