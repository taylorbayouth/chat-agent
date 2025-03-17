#!/bin/bash

# Start Laravel
php artisan serve &
LARAVEL_PID=$!
echo "Laravel started with PID: $LARAVEL_PID"

# Start Python service
cd python
source venv/bin/activate
python run.py &
PYTHON_PID=$!
echo "Python service started with PID: $PYTHON_PID"
cd ..

# Start Node.js service
cd node
npm start &
NODE_PID=$!
echo "Node.js service started with PID: $NODE_PID"
cd ..

echo "All services started. Press Ctrl+C to stop all."

# Function to kill all started processes
function cleanup {
  echo "Stopping all services..."
  kill $LARAVEL_PID
  kill $PYTHON_PID
  kill $NODE_PID
  wait
  echo "All services stopped."
  exit
}

# Trap Ctrl+C
trap cleanup SIGINT

# Wait
wait
