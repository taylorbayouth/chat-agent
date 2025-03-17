from agents import Agent, Runner, ComputerTool, trace
from agents.model_settings import ModelSettings
import os
import logging
import asyncio
import traceback

# Import the AsyncComputer implementation
from ..computer.async_computer import WebSocketComputer

logger = logging.getLogger(__name__)

class AgentManager:
    """Manages AI agents and their execution."""
    
    def __init__(self):
        self.agents = {}
        self.websocket_url = os.environ.get('WEBSOCKET_SERVER', 'ws://localhost:3001')
        
    async def create_agent(self, agent_id, config):
        """Create a new agent with the specified configuration."""
        try:
            # Log API key status (don't log the actual key!)
            api_key = os.environ.get('OPENAI_API_KEY', '')
            logger.info(f"Using OpenAI API key: {'[Set]' if api_key else '[Not Set]'}")
            
            # Get the requested model
            model = config.get('model', 'gpt-4o')
            logger.info(f"Requested model: {model}")
            
            # Initialize tools array
            tools = []
            
            # Only add ComputerTool for computer-capable models
            need_computer_tool = model == 'computer-use-preview'
            computer = None
            
            if need_computer_tool:
                try:
                    # Create computer instance
                    computer = WebSocketComputer(self.websocket_url)
                    connected = await computer.connect()
                    
                    if not connected:
                        return {
                            'success': False,
                            'error': 'Failed to connect to WebSocket server'
                        }
                    
                    # Add ComputerTool to tools
                    tools.append(ComputerTool(computer))
                    logger.info("Added ComputerTool to agent")
                except Exception as e:
                    logger.error(f"Failed to create computer tool: {e}")
                    return {
                        'success': False,
                        'error': f"Failed to create computer tool: {str(e)}"
                    }
            
            # Create agent
            agent = Agent(
                name=config.get('name', 'AI Assistant'),
                instructions=config.get('instructions', 'You are a helpful AI assistant.'),
                model=model,
                model_settings=ModelSettings(truncation="auto"),
                tools=tools
            )
            
            # Store agent and computer
            self.agents[agent_id] = {
                'agent': agent,
                'computer': computer,
                'config': config
            }
            
            logger.info(f"Created agent {agent_id} with model {model}")
            return {
                'success': True,
                'agent_id': agent_id,
                'model': model,
                'has_computer_tool': len(tools) > 0
            }
            
        except Exception as e:
            error_traceback = traceback.format_exc()
            logger.error(f"Failed to create agent: {e}\n{error_traceback}")
            return {
                'success': False,
                'error': f"Failed to create agent: {str(e)}",
                'details': error_traceback
            }
    
    async def run_agent(self, agent_id, input_text):
        """Run an agent with the specified input."""
        if agent_id not in self.agents:
            return {
                'success': False,
                'error': f"Agent {agent_id} not found"
            }
            
        try:
            agent_data = self.agents[agent_id]
            agent = agent_data['agent']
            
            # Use a trace for this run
            with trace(f"Agent {agent_id} run"):
                result = await Runner.run(agent, input_text)
                
            return {
                'success': True,
                'output': result.final_output,
                'trace_id': result.trace_id if hasattr(result, 'trace_id') else None
            }
            
        except Exception as e:
            logger.error(f"Failed to run agent {agent_id}: {e}")
            return {
                'success': False,
                'error': str(e)
            }
    
    def get_agent_status(self, agent_id):
        """Get the status of an agent."""
        if agent_id not in self.agents:
            return {
                'exists': False
            }
            
        return {
            'exists': True,
            'config': self.agents[agent_id]['config']
        }
    
    async def stop_agent(self, agent_id):
        """Stop and remove an agent."""
        if agent_id not in self.agents:
            return {
                'success': False,
                'error': f"Agent {agent_id} not found"
            }
            
        try:
            # Clean up any resources
            computer = self.agents[agent_id].get('computer')
            if computer and computer.websocket:
                await computer.websocket.close()
                
            # Remove agent
            del self.agents[agent_id]
            
            return {
                'success': True
            }
            
        except Exception as e:
            logger.error(f"Failed to stop agent {agent_id}: {e}")
            return {
                'success': False,
                'error': str(e)
            }

# Create a singleton instance
agent_manager = AgentManager()

# Make sure agent_manager is available for import
__all__ = ['agent_manager']
