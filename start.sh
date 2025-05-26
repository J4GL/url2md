#!/bin/bash

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

while [ $PORT -le $MAX_PORT ]; do
    if ! lsof -i:$PORT > /dev/null 2>&1; then
        break
    fi
    echo -e "${YELLOW}Port $PORT is in use, trying next port...${NC}"
    PORT=$((PORT+1))
done

if [ $PORT -gt $MAX_PORT ]; then
    echo -e "${RED}No available ports found in range 5000-$MAX_PORT.${NC}"
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
export FLASK_ENV=development

# Start the server
echo -e "${YELLOW}Starting server on port $PORT...${NC}"
echo -e "${GREEN}Server will be available at: ${BLUE}http://localhost:$PORT${NC}"
echo -e "${YELLOW}Press Ctrl+C to stop the server${NC}"
echo ""

python3 -m flask run --port=$PORT

# This will only execute when the server is stopped
echo ""
echo -e "${RED}Server stopped${NC}"

# Deactivate virtual environment
deactivate
