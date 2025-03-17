from agents import AsyncComputer
import base64
import json
import asyncio
import websockets
import logging
from typing import Literal, List, Tuple

logger = logging.getLogger(__name__)

class WebSocketComputer(AsyncComputer):
    """Implementation of AsyncComputer that communicates with the Node.js service via WebSocket."""
    
    def __init__(self, websocket_url):
        self.websocket_url = websocket_url
        self.websocket = None
        self.command_counter = 0
        self.pending_commands = {}
        self._connected = False
        


    async def connect(self):
        """Connect to the WebSocket server."""
        if self.websocket is None:
            try:
                logger.info(f"Connecting to WebSocket at {self.websocket_url}")
                self.websocket = await websockets.connect(
                    self.websocket_url,
                    max_size=100 * 1024 * 1024,  # 100MB max message size
                    ping_interval=None,  # Disable ping to avoid timeouts
                    close_timeout=5.0    # 5 second close timeout
                )
                # Start the listener task
                self._listener_task = asyncio.create_task(self._message_listener())
                logger.info(f"Connected to WebSocket at {self.websocket_url}")
                self._connected = True
                return True
            except Exception as e:
                logger.error(f"Failed to connect to WebSocket: {e}")
                self._connected = False
                return False
        return self._connected
    
    async def _message_listener(self):
        """Listen for messages from the WebSocket server."""
        try:
            logger.info("WebSocket message listener started")
            async for message in self.websocket:
                try:
                    logger.info(f"Received WebSocket message of size: {len(message)} bytes")
                    data = json.loads(message)
                    command_id = data.get('command_id')
                    
                    if command_id and command_id in self.pending_commands:
                        logger.info(f"Processing response for command ID: {command_id}")
                        future = self.pending_commands[command_id]
                        future.set_result(data)
                        del self.pending_commands[command_id]
                    else:
                        logger.warning(f"Received message with unknown command ID: {command_id}")
                except json.JSONDecodeError as e:
                    logger.error(f"Failed to parse WebSocket message: {e}")
                except Exception as e:
                    logger.error(f"Error processing WebSocket message: {e}")
        except websockets.exceptions.ConnectionClosed as e:
            logger.error(f"WebSocket connection closed: {e}")
        except Exception as e:
            logger.error(f"WebSocket listener error: {e}")
        
        # Try to reconnect
        logger.info("WebSocket connection lost, attempting to reconnect...")
        self.websocket = None
        self._connected = False
        await asyncio.sleep(1)
        await self.connect()
    
            
        async def _message_listener(self):
            """Listen for messages from the WebSocket server."""
            try:
                logger.info("WebSocket message listener started")
                async for message in self.websocket:
                    logger.info(f"Received WebSocket message of size: {len(message)} bytes")
                    try:
                        data = json.loads(message)
                        command_id = data.get('command_id')
                        
                        if command_id and command_id in self.pending_commands:
                            logger.info(f"Processing response for command ID: {command_id}")
                            future = self.pending_commands[command_id]
                            future.set_result(data)
                            del self.pending_commands[command_id]
                        else:
                            logger.warning(f"Received message with unknown command ID: {command_id}")
                    except json.JSONDecodeError as e:
                        logger.error(f"Failed to parse WebSocket message: {e}")
            except Exception as e:
                logger.error(f"WebSocket listener error: {e}")
                # Try to reconnect
                logger.info("Attempting to reconnect WebSocket...")
                self.websocket = None
                self._connected = False
                await asyncio.sleep(1)
                await self.connect()
    
    async def _send_command(self, command, params=None):
        """Send a command to the WebSocket server and wait for a response."""
        await self.connect()
        
        self.command_counter += 1
        command_id = f"cmd_{self.command_counter}"
        
        message = {
            'command': command,
            'command_id': command_id,
            'params': params or {}
        }
        
        # Create a future to wait for the response
        future = asyncio.Future()
        self.pending_commands[command_id] = future
        
        try:
            # Send the command
            logger.info(f"Sending command: {command} with ID: {command_id}")
            await self.websocket.send(json.dumps(message))
            
            # Use a longer timeout for screenshot operations
            timeout = 30.0 if command == 'screenshot' else 10.0
            logger.info(f"Waiting for response with timeout of {timeout} seconds")
            
            # Wait for the response with the appropriate timeout
            response = await asyncio.wait_for(future, timeout=timeout)
            logger.info(f"Received response for command: {command} (ID: {command_id})")
            return response
        except asyncio.TimeoutError:
            logger.error(f"Command {command} (ID: {command_id}) timed out after {timeout} seconds")
            del self.pending_commands[command_id]
            return {'success': False, 'error': 'Command timed out'}
        except Exception as e:
            logger.error(f"Error sending command {command} (ID: {command_id}): {e}")
            if command_id in self.pending_commands:
                del self.pending_commands[command_id]
            return {'success': False, 'error': f'Command error: {str(e)}'}
    
    @property
    def environment(self) -> str:
        return "browser"
    
    @property
    def dimensions(self) -> Tuple[int, int]:
        return (1200, 800)
    
    async def screenshot(self) -> str:
        """Take a screenshot of the current screen."""
        try:
            response = await self._send_command('screenshot')
            if response.get('success', False):
                # Add proper data URL format to the base64 image
                image_data = response.get('image', '')
                if image_data:
                    return f"data:image/webp;base64,{image_data}"
                else:
                    logger.error("Empty image data received from screenshot command")
                    # Return a small placeholder image
                    return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAEPQJ+QY5sHAAAAABJRU5ErkJggg=="
            logger.error(f"Screenshot failed: {response.get('error')}")
            # Return a placeholder image
            return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAEPQJ+QY5sHAAAAABJRU5ErkJggg=="
        except Exception as e:
            logger.error(f"Screenshot error: {e}")
            # Return a placeholder image
            return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAEPQJ+QY5sHAAAAABJRU5ErkJggg=="
    
    async def click(self, x: int, y: int, button: str = "left") -> None:
        """Click at the specified coordinates."""
        await self._send_command('click', {'x': x, 'y': y, 'button': button})
    
    async def double_click(self, x: int, y: int) -> None:
        """Double-click at the specified coordinates."""
        await self._send_command('click', {'x': x, 'y': y, 'double': True})
    
    async def type(self, text: str) -> None:
        """Type the specified text."""
        await self._send_command('type', {'text': text})
    
    async def keypress(self, keys: List[str]) -> None:
        """Press the specified keys."""
        await self._send_command('keypress', {'keys': keys})
    
    async def wait(self) -> None:
        """Wait for a short period."""
        await asyncio.sleep(1)
    
    async def move(self, x: int, y: int) -> None:
        """Move the mouse to the specified coordinates."""
        await self._send_command('move', {'x': x, 'y': y})
    
    async def scroll(self, x: int, y: int, scroll_x: int, scroll_y: int) -> None:
        """Scroll at the specified coordinates."""
        await self._send_command('scroll', {
            'x': x, 
            'y': y, 
            'scroll_x': scroll_x, 
            'scroll_y': scroll_y
        })
    
    async def drag(self, path: List[Tuple[int, int]]) -> None:
        """Drag along the specified path."""
        if not path or len(path) < 2:
            return
            
        start = path[0]
        end = path[-1]
        await self._send_command('drag', {
            'startX': start[0],
            'startY': start[1],
            'endX': end[0],
            'endY': end[1]
        })
