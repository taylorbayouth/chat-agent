from agent_service.web.api import app

if __name__ == "__main__":
    app.run(host='0.0.0.0', port=5001, debug=True)  # Changed port from 5000 to 5001
