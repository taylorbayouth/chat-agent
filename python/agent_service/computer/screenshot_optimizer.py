from PIL import Image
import io
import base64
import numpy as np

def optimize_screenshot(image_data, quality=80, max_width=1200, format="WEBP"):
    """
    Optimize a screenshot for transmission to OpenAI.
    
    Args:
        image_data: Base64 encoded image data
        quality: Image quality (0-100)
        max_width: Maximum width of the image
        format: Image format (WEBP, JPEG, PNG)
    
    Returns:
        Base64 encoded optimized image
    """
    # Decode base64 image
    image_bytes = base64.b64decode(image_data)
    image = Image.open(io.BytesIO(image_bytes))
    
    # Resize if needed
    if image.width > max_width:
        ratio = max_width / image.width
        new_height = int(image.height * ratio)
        image = image.resize((max_width, new_height), Image.LANCZOS)
    
    # Convert to RGB if necessary (WebP requires RGB)
    if image.mode != 'RGB':
        image = image.convert('RGB')
    
    # Save to buffer with desired format and quality
    buffer = io.BytesIO()
    
    if format.upper() == "WEBP":
        image.save(buffer, format="WEBP", quality=quality)
    elif format.upper() == "JPEG":
        image.save(buffer, format="JPEG", quality=quality)
    else:  # Default to PNG
        image.save(buffer, format="PNG", optimize=True)
    
    # Get base64 encoded image
    optimized_bytes = buffer.getvalue()
    return base64.b64encode(optimized_bytes).decode('utf-8')
