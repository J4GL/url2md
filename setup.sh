#!/bin/bash

# Colors for better output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== URL to Markdown Converter - Setup ===${NC}"
echo -e "${BLUE}This script will check and install required dependencies for macOS${NC}"
echo ""

# Check if Python 3 is installed
echo -e "${YELLOW}Checking for Python 3...${NC}"
if command -v python3 &>/dev/null; then
    PYTHON_VERSION=$(python3 --version)
    echo -e "${GREEN}✓ Python 3 is installed: ${PYTHON_VERSION}${NC}"
else
    echo -e "${RED}✗ Python 3 is not installed${NC}"
    echo -e "${YELLOW}Installing Python 3 using Homebrew...${NC}"
    
    # Check if Homebrew is installed
    if ! command -v brew &>/dev/null; then
        echo -e "${YELLOW}Homebrew not found. Installing Homebrew...${NC}"
        /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    fi
    
    brew install python
    
    if command -v python3 &>/dev/null; then
        PYTHON_VERSION=$(python3 --version)
        echo -e "${GREEN}✓ Python 3 installed successfully: ${PYTHON_VERSION}${NC}"
    else
        echo -e "${RED}Failed to install Python 3. Please install it manually from https://www.python.org/downloads/${NC}"
        exit 1
    fi
fi

# Check if pip3 is installed
echo -e "${YELLOW}Checking for pip3...${NC}"
if command -v pip3 &>/dev/null; then
    PIP_VERSION=$(pip3 --version)
    echo -e "${GREEN}✓ pip3 is installed: ${PIP_VERSION}${NC}"
else
    echo -e "${RED}✗ pip3 is not installed${NC}"
    echo -e "${YELLOW}Installing pip3...${NC}"
    curl https://bootstrap.pypa.io/get-pip.py -o get-pip.py
    python3 get-pip.py
    rm get-pip.py
    
    if command -v pip3 &>/dev/null; then
        PIP_VERSION=$(pip3 --version)
        echo -e "${GREEN}✓ pip3 installed successfully: ${PIP_VERSION}${NC}"
    else
        echo -e "${RED}Failed to install pip3. Please install it manually.${NC}"
        exit 1
    fi
fi

# Check if Google Chrome is installed
echo -e "${YELLOW}Checking for Google Chrome...${NC}"
if [ -d "/Applications/Google Chrome.app" ]; then
    CHROME_VERSION=$(/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --version 2>/dev/null)
    echo -e "${GREEN}✓ Google Chrome is installed: ${CHROME_VERSION}${NC}"
else
    echo -e "${RED}✗ Google Chrome is not installed${NC}"
    echo -e "${YELLOW}Installing Google Chrome...${NC}"
    
    # Check if Homebrew is installed
    if ! command -v brew &>/dev/null; then
        echo -e "${YELLOW}Homebrew not found. Installing Homebrew...${NC}"
        /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    fi
    
    brew install --cask google-chrome
    
    if [ -d "/Applications/Google Chrome.app" ]; then
        CHROME_VERSION=$(/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --version 2>/dev/null)
        echo -e "${GREEN}✓ Google Chrome installed successfully: ${CHROME_VERSION}${NC}"
    else
        echo -e "${RED}Failed to install Google Chrome. Please install it manually from https://www.google.com/chrome/${NC}"
        echo -e "${RED}Chrome is required for the undetected_chromedriver to work properly.${NC}"
    fi
fi

# Create virtual environment if it doesn't exist
echo -e "${YELLOW}Checking for virtual environment...${NC}"
if [ ! -d "venv" ]; then
    echo -e "${YELLOW}Creating virtual environment...${NC}"
    python3 -m venv venv
    echo -e "${GREEN}✓ Virtual environment created${NC}"
else
    echo -e "${GREEN}✓ Virtual environment already exists${NC}"
fi

# Activate virtual environment
echo -e "${YELLOW}Activating virtual environment...${NC}"
source venv/bin/activate
echo -e "${GREEN}✓ Virtual environment activated${NC}"

# Install setuptools explicitly for Python 3.12+ (distutils compatibility)
echo -e "${YELLOW}Ensuring setuptools is installed for Python 3.12+ compatibility...${NC}"
pip3 install --upgrade setuptools

# Install Python dependencies
echo -e "${YELLOW}Installing Python dependencies...${NC}"
pip3 install -r requirements.txt

# Check if all required packages are installed
echo -e "${YELLOW}Verifying Python packages...${NC}"
REQUIRED_PACKAGES=("flask" "undetected-chromedriver" "html2text" "selenium")
ALL_INSTALLED=true

for package in "${REQUIRED_PACKAGES[@]}"; do
    if pip3 show $package &>/dev/null; then
        PKG_VERSION=$(pip3 show $package | grep Version | awk '{print $2}')
        echo -e "${GREEN}✓ $package is installed: v${PKG_VERSION}${NC}"
    else
        echo -e "${RED}✗ $package is not installed${NC}"
        ALL_INSTALLED=false
    fi
done

if [ "$ALL_INSTALLED" = false ]; then
    echo -e "${RED}Some packages are missing. Trying to install them again...${NC}"
    pip3 install -r requirements.txt
    
    # Check again
    MISSING=false
    for package in "${REQUIRED_PACKAGES[@]}"; do
        if ! pip3 show $package &>/dev/null; then
            echo -e "${RED}✗ Failed to install $package${NC}"
            MISSING=true
        fi
    done
    
    if [ "$MISSING" = true ]; then
        echo -e "${RED}Failed to install all required packages. Please check your internet connection and try again.${NC}"
        exit 1
    fi
fi

echo ""
echo -e "${GREEN}=== Setup completed successfully! ===${NC}"
echo -e "${BLUE}You can now run the server with: ./start.sh${NC}"

# Make the start script executable
chmod +x start.sh

# Deactivate virtual environment
deactivate
