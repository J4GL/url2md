#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

# Colors for better output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== URL to Markdown Converter - Server Launcher ===${NC}"

# Find an available port (default: 5000, fallbacks: 5001-5010)
PORT=5000
MAX_PORT=5010
PORT_FOUND=false

echo -e "${YELLOW}Searching for an available port from $PORT to $MAX_PORT...${NC}"

while [ $PORT -le $MAX_PORT ]; do
    echo -e "${YELLOW}Checking port $PORT...${NC}"
    # Try to connect to the port using Python.
    # If connect_ex returns 0, the port is in use (connection successful or actively refused by a listening socket).
    # If connect_ex returns a non-zero errno (e.g., ECONNREFUSED for non-listening port), the port is likely available.
    if python3 -c "import socket; s = socket.socket(socket.AF_INET, socket.SOCK_STREAM); s.settimeout(0.1); exit(0) if s.connect_ex(('127.0.0.1', $PORT)) == 0 else exit(1)"; then
        echo -e "${RED}Port $PORT is currently in use.${NC}"
        PORT=$((PORT+1))
    else
        echo -e "${GREEN}Port $PORT is available.${NC}"
        PORT_FOUND=true
        break # Port is available
    fi
done

if [ "$PORT_FOUND" = false ]; then
    echo -e "${RED}No available ports found in the range 5000-$MAX_PORT.${NC}"
    echo -e "${RED}Please free up a port or modify this script to use a different port range.${NC}"
    exit 1
fi

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo -e "${RED}Virtual environment not found.${NC}"
    echo -e "${YELLOW}Please run ./setup.sh first to set up the environment.${NC}"
    exit 1
fi

# Activate virtual environment
echo -e "${YELLOW}Activating virtual environment...${NC}"
source venv/bin/activate
echo -e "${GREEN}✓ Virtual environment activated${NC}"

# Set environment variables
export FLASK_APP=app.py
export FLASK_ENV=development # Recommended for development for debugging and auto-reload

# Start the server
echo -e "${YELLOW}Starting server on port $PORT...${NC}"
echo -e "${GREEN}Server will be available at: ${BLUE}http://localhost:$PORT${NC} or ${BLUE}http://127.0.0.1:$PORT${NC}"
echo -e "${YELLOW}Press Ctrl+C to stop the server${NC}"
echo ""

# Using python3 -m flask run ensures it uses the Flask from the venv
python3 -m flask run --host=0.0.0.0 --port=$PORT

# This will only execute when the server is stopped (e.g., by Ctrl+C)
echo ""
echo -e "${RED}Server stopped on port $PORT${NC}"

# Deactivate virtual environment
deactivate
echo -e "${YELLOW}Virtual environment deactivated.${NC}"

exit 0
