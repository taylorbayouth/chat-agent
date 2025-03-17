const { execSync } = require('child_process');
const sharp = require('sharp');

async function takeScreenshot({ format = 'webp', quality = 50 } = {}) {
  try {
    console.log('Taking optimized screenshot...');
    
    let screenshotBuffer;
    
    // On macOS, use the built-in screencapture command with stdout
    if (process.platform === 'darwin') {
      try {
        console.log('Capturing screenshot using macOS native method...');
        // Capture directly to stdout instead of a file
        screenshotBuffer = execSync('screencapture -x -t png -', { 
          encoding: null,  // Return raw buffer 
          maxBuffer: 100 * 1024 * 1024, // 100MB max buffer
          timeout: 5000 // 5 second timeout
        });
        console.log(`Screenshot captured directly, size: ${screenshotBuffer.length} bytes`);
      } catch (error) {
        console.error('Error with direct screenshot capture:', error);
        throw error;
      }
    } else {
      // For other platforms, use screenshot-desktop
      console.log('Using screenshot-desktop for non-macOS platform');
      const screenshot = require('screenshot-desktop');
      screenshotBuffer = await screenshot();
      console.log(`Screenshot captured with screenshot-desktop, size: ${screenshotBuffer.length} bytes`);
    }
    
    // Process with sharp for optimal compression but with lower effort for speed
    const processedImage = await sharp(screenshotBuffer)
      .resize({ width: 800, height: 600, fit: 'inside' })
      .webp({ quality, effort: 2 }) // Lower effort (2 instead of 6) for faster processing
      .toBuffer();
    
    console.log(`Processed image size: ${processedImage.length} bytes`);
    
    // Return base64 encoded image
    const base64Image = processedImage.toString('base64');
    
    return {
      success: true,
      image: base64Image,
      width: 800,
      height: 600,
      format: 'webp'
    };
  } catch (error) {
    console.error('Screenshot error:', error);
    return { 
      success: false, 
      error: error.message
    };
  }
}

module.exports = { takeScreenshot };
