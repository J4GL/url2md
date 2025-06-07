#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

# Colors for better output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== URL to Markdown Converter - macOS Setup ===${NC}"
echo -e "${RED}IMPORTANT: This script is designed primarily for macOS and uses Homebrew.${NC}"
echo -e "${RED}For Linux or Windows, please follow the manual installation instructions in README.md.${NC}"
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
    # `set -e` will cause exit if brew install fails
    PYTHON_VERSION=$(python3 --version)
    echo -e "${GREEN}✓ Python 3 installed successfully: ${PYTHON_VERSION}${NC}"
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
    # `set -e` will cause exit if pip3 install fails
    PIP_VERSION=$(pip3 --version)
    echo -e "${GREEN}✓ pip3 installed successfully: ${PIP_VERSION}${NC}"
fi

# Check if Google Chrome is installed
echo -e "${YELLOW}Checking for Google Chrome...${NC}"
CHROME_STANDARD_PATH="/Applications/Google Chrome.app"
CHROME_VERSION_CHECK_EXECUTABLE=""

if [ -d "$CHROME_STANDARD_PATH" ]; then
    CHROME_VERSION_CHECK_EXECUTABLE="$CHROME_STANDARD_PATH/Contents/MacOS/Google Chrome"
    CHROME_VERSION=$("$CHROME_VERSION_CHECK_EXECUTABLE" --version 2>/dev/null)
    echo -e "${GREEN}✓ Google Chrome is installed: ${CHROME_VERSION}${NC}"
else
    echo -e "${RED}✗ Google Chrome not found at standard location ($CHROME_STANDARD_PATH).${NC}"
    # Attempt to locate Chrome using mdfind as a fallback for non-standard installations
    echo -e "${YELLOW}Attempting to find Chrome in other locations (this might take a moment)...${NC}"
    ALT_CHROME_APP_PATH=$(mdfind 'kMDItemCFBundleIdentifier == "com.google.Chrome"' | head -n 1)

    if [[ -n "$ALT_CHROME_APP_PATH" && -d "$ALT_CHROME_APP_PATH" ]]; then
        CHROME_VERSION_CHECK_EXECUTABLE="$ALT_CHROME_APP_PATH/Contents/MacOS/Google Chrome"
    elif [[ -n "$ALT_CHROME_APP_PATH" && -x "$ALT_CHROME_APP_PATH" ]]; then # If mdfind returns the executable itself
        CHROME_VERSION_CHECK_EXECUTABLE="$ALT_CHROME_APP_PATH"
    fi
    
    if [[ -n "$CHROME_VERSION_CHECK_EXECUTABLE" && -x "$CHROME_VERSION_CHECK_EXECUTABLE" ]]; then
         CHROME_VERSION=$("$CHROME_VERSION_CHECK_EXECUTABLE" --version 2>/dev/null)
         echo -e "${GREEN}✓ Google Chrome found at: $ALT_CHROME_APP_PATH (${CHROME_VERSION})${NC}"
    else
        echo -e "${RED}✗ Google Chrome still not found in common alternative locations.${NC}"
        echo -e "${YELLOW}Attempting to install Google Chrome using Homebrew...${NC}"

        if ! command -v brew &>/dev/null; then
            echo -e "${YELLOW}Homebrew not found. Installing Homebrew...${NC}"
            /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
        fi

        brew install --cask google-chrome

        # Verify installation after attempting brew install
        if [ -d "$CHROME_STANDARD_PATH" ]; then
            CHROME_VERSION_CHECK_EXECUTABLE="$CHROME_STANDARD_PATH/Contents/MacOS/Google Chrome"
            CHROME_VERSION=$("$CHROME_VERSION_CHECK_EXECUTABLE" --version 2>/dev/null)
            echo -e "${GREEN}✓ Google Chrome installed successfully via Homebrew: ${CHROME_VERSION}${NC}"
        else
            # Check mdfind again in case it was installed to a non-standard path by brew (less likely for cask)
            ALT_CHROME_APP_PATH_AFTER_INSTALL=$(mdfind 'kMDItemCFBundleIdentifier == "com.google.Chrome"' | head -n 1)
            if [[ -n "$ALT_CHROME_APP_PATH_AFTER_INSTALL" && -d "$ALT_CHROME_APP_PATH_AFTER_INSTALL" ]]; then
                 CHROME_VERSION_CHECK_EXECUTABLE="$ALT_CHROME_APP_PATH_AFTER_INSTALL/Contents/MacOS/Google Chrome"
            elif [[ -n "$ALT_CHROME_APP_PATH_AFTER_INSTALL" && -x "$ALT_CHROME_APP_PATH_AFTER_INSTALL" ]]; then
                 CHROME_VERSION_CHECK_EXECUTABLE="$ALT_CHROME_APP_PATH_AFTER_INSTALL"
            fi

            if [[ -n "$CHROME_VERSION_CHECK_EXECUTABLE" && -x "$CHROME_VERSION_CHECK_EXECUTABLE" ]]; then
                CHROME_VERSION=$("$CHROME_VERSION_CHECK_EXECUTABLE" --version 2>/dev/null)
                echo -e "${GREEN}✓ Google Chrome found after install: ${CHROME_VERSION}${NC} (may be in a non-standard location)"
            else
                echo -e "${RED}Failed to install or find Google Chrome after Homebrew installation attempt.${NC}"
                echo -e "${RED}Google Chrome is required. Please install it manually from https://www.google.com/chrome/ and ensure it's in a standard location or accessible via mdfind.${NC}"
                exit 1
            fi
        fi
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
echo -e "${YELLOW}Ensuring setuptools is installed/updated for Python 3.12+ compatibility...${NC}"
pip3 install --upgrade setuptools

# Install Python dependencies
echo -e "${YELLOW}Installing Python dependencies from requirements.txt...${NC}"
pip3 install -r requirements.txt
# `set -e` will cause exit if pip3 install fails

# Verification of packages is largely handled by `set -e` ensuring pip install succeeds.
# However, a specific check can provide clearer messages if a package from requirements.txt was missing or malformed.
echo -e "${YELLOW}Verifying Python packages installation...${NC}"
REQUIRED_PACKAGES=("flask" "undetected-chromedriver" "html2text" "selenium")
PACKAGES_OK=true
for package in "${REQUIRED_PACKAGES[@]}"; do
    if ! pip3 show "$package" &>/dev/null; then
        echo -e "${RED}✗ Package $package is NOT installed after pip install -r requirements.txt.${NC}"
        PACKAGES_OK=false
    else
        PKG_VERSION=$(pip3 show "$package" | grep Version | awk '{print $2}')
        echo -e "${GREEN}✓ $package is installed (Version: ${PKG_VERSION})${NC}"
    fi
done

if [ "$PACKAGES_OK" = false ]; then
    echo -e "${RED}One or more required packages were not successfully installed from requirements.txt.${NC}"
    echo -e "${RED}Please check requirements.txt and ensure all packages can be installed.${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}=== Setup completed successfully! ===${NC}"
echo -e "${BLUE}You can now run the server with: ./start.sh${NC}"

# Make the start script executable
chmod +x start.sh

# Deactivate virtual environment (optional as script exits, but good practice)
deactivate
echo -e "${YELLOW}Virtual environment deactivated.${NC}"

exit 0
