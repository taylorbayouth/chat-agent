from flask import Flask, request, jsonify
from flask_cors import CORS
import asyncio
import os
import logging
from dotenv import load_dotenv

# Import agent_manager correctly
from ..agents.main_agent import agent_manager

# Load environment variables
load_dotenv()

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)

logger = logging.getLogger(__name__)


# Check for required environment variables
required_env_vars = ['OPENAI_API_KEY', 'WEBSOCKET_SERVER']
for var in required_env_vars:
    if not os.environ.get(var):
        logger.warning(f"Missing environment variable: {var}")

# Create Flask app
app = Flask(__name__)
CORS(app)

# Helper for running async functions in Flask
def run_async(func):
    """Run an async function from a synchronous context."""
    loop = asyncio.new_event_loop()
    asyncio.set_event_loop(loop)
    result = loop.run_until_complete(func)
    loop.close()
    return result

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint."""
    return jsonify({
        'status': 'ok',
        'service': 'agent-service',
        'version': '0.1.0'
    })


# Update the create_agent route to return more details:
@app.route('/agents', methods=['POST'])
def create_agent():
    """Create a new agent."""
    data = request.json
    agent_id = data.get('agent_id')
    
    if not agent_id:
        return jsonify({
            'success': False,
            'error': 'Missing agent_id parameter'
        }), 400
    
    # Check OpenAI API key
    if not os.environ.get('OPENAI_API_KEY'):
        return jsonify({
            'success': False,
            'error': 'OPENAI_API_KEY environment variable not set'
        }), 403
        
    result = run_async(agent_manager.create_agent(agent_id, data))
    
    if result.get('success', False):
        return jsonify(result)
    else:
        # Return detailed error with appropriate status code
        status_code = 500
        if 'unauthorized' in str(result.get('error', '')).lower() or 'forbidden' in str(result.get('error', '')).lower():
            status_code = 403
            
        return jsonify(result), status_code


@app.route('/agents/<agent_id>/run', methods=['POST'])
def run_agent(agent_id):
    """Run an agent with the specified input."""
    data = request.json
    input_text = data.get('input')
    
    if not input_text:
        return jsonify({
            'success': False,
            'error': 'Missing input parameter'
        }), 400
        
    result = run_async(agent_manager.run_agent(agent_id, input_text))
    
    if result.get('success', False):
        return jsonify(result)
    else:
        return jsonify(result), 500

@app.route('/agents/<agent_id>', methods=['GET'])
def get_agent_status(agent_id):
    """Get the status of an agent."""
    result = agent_manager.get_agent_status(agent_id)
    
    if result.get('exists', False):
        return jsonify(result)
    else:
        return jsonify({
            'success': False,
            'error': f"Agent {agent_id} not found"
        }), 404

@app.route('/agents/<agent_id>', methods=['DELETE'])
def stop_agent(agent_id):
    """Stop and remove an agent."""
    result = run_async(agent_manager.stop_agent(agent_id))
    
    if result.get('success', False):
        return jsonify(result)
    else:
        return jsonify(result), 500

@app.route('/test', methods=['GET'])
def test_api():
    """Test endpoint that doesn't require OpenAI API calls."""
    return jsonify({
        'success': True,
        'message': 'Python service is up and running!',
        'version': '0.1.0',
        'openai_api_key_set': bool(os.environ.get('OPENAI_API_KEY')),
        'websocket_server': os.environ.get('WEBSOCKET_SERVER', 'ws://localhost:3001')
    })
