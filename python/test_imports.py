#!/usr/bin/env python

print("Testing imports...")

try:
    from agent_service.web.api import app
    print("✅ Successfully imported app from agent_service.web.api")
except ImportError as e:
    print(f"❌ Failed to import app: {e}")
    
try:
    from agent_service.agents.main_agent import agent_manager
    print("✅ Successfully imported agent_manager from agent_service.agents.main_agent")
except ImportError as e:
    print(f"❌ Failed to import agent_manager: {e}")

try:
    from agent_service.computer.async_computer import WebSocketComputer
    print("✅ Successfully imported WebSocketComputer from agent_service.computer.async_computer")
except ImportError as e:
    print(f"❌ Failed to import WebSocketComputer: {e}")

print("Import test complete.")
