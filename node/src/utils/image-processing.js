const sharp = require('sharp');

async function optimizeImage(imageBuffer, options = {}) {
  const {
    format = 'webp',
    quality = 80,
    maxWidth = 1200,
    maxHeight = 900
  } = options;
  
  try {
    // Create sharp instance
    let processor = sharp(imageBuffer);
    
    // Get image metadata
    const metadata = await processor.metadata();
    
    // Resize if needed
    if (metadata.width > maxWidth || metadata.height > maxHeight) {
      processor = processor.resize({
        width: maxWidth,
        height: maxHeight,
        fit: 'inside',
        withoutEnlargement: true
      });
    }
    
    // Set format and quality
    let outputOptions = {};
    
    if (format === 'webp') {
      outputOptions = { quality };
      processor = processor.webp(outputOptions);
    } else if (format === 'jpeg' || format === 'jpg') {
      outputOptions = { quality };
      processor = processor.jpeg(outputOptions);
    } else if (format === 'png') {
      outputOptions = { compressionLevel: 9 };
      processor = processor.png(outputOptions);
    }
    
    // Get processed buffer
    const outputBuffer = await processor.toBuffer({ resolveWithObject: true });
    
    return {
      buffer: outputBuffer.data,
      base64: outputBuffer.data.toString('base64'),
      width: outputBuffer.info.width,
      height: outputBuffer.info.height,
      format: outputBuffer.info.format,
      size: outputBuffer.data.length
    };
  } catch (error) {
    console.error('Image optimization error:', error);
    throw error;
  }
}

module.exports = { optimizeImage };

