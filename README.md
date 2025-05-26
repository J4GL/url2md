./# URL to Markdown Converter

A web application that converts web pages into clean, readable Markdown format. Built with Python Flask and Selenium, this tool can handle JavaScript-rendered content and provides a simple web interface for easy conversion.

## Features

- Converts any public webpage to Markdown format
- Handles JavaScript-rendered content using headless Chrome
- Extracts and focuses on the main content of the page
- Provides both raw markdown and rendered preview
- Simple and intuitive web interface
- Lightweight and easy to set up
- Cross-platform compatibility (macOS, Linux, Windows)
- API endpoint for programmatic access

## Prerequisites

- Python 3.7 or higher
- Google Chrome or Chromium browser
- pip (Python package manager)
- setuptools (explicitly installed for Python 3.12+ compatibility)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/url2md.git
   cd url2md
   ```

2. Make the setup script executable and run it:
   ```bash
   chmod +x setup.sh
   ./setup.sh
   ```

   This will automatically:
   - Check for and install required system dependencies
   - Create a Python virtual environment
   - Ensure `setuptools` is installed (required for Python 3.12+ and for distutils compatibility)
   - Install Python dependencies
   - Download and configure ChromeDriver

## Usage

1. Start the application:
   ```bash
   ./start.sh
   ```

2. Open your web browser and navigate to:
   ```
   http://localhost:5000
   ```
   (The script will automatically find an available port between 5000-5010 if the default is in use)

3. Enter the URL of the webpage you want to convert and click "Convert to Markdown"

4. The converted Markdown will be displayed in the text area, and you can copy it or download it as a .md file

## API Endpoint

You can also use the conversion service programmatically by sending a POST request to:

```
POST /scrape-and-convert
Content-Type: application/json

{
    "url": "https://example.com"
}
```

### Response

```json
{
    "markdown": "# Example Domain\n\nThis is the converted markdown content..."
}
```

## Project Structure

- `app.py` - Main Flask application
- `templates/` - HTML templates
  - `index.html` - Web interface
- `requirements.txt` - Python dependencies
- `setup.sh` - Setup script for dependencies
- `start.sh` - Script to start the application

## Dependencies

- Flask - Web framework
- Selenium - Web browser automation
- undetected-chromedriver - ChromeDriver that avoids detection
- html2text - HTML to Markdown conversion

## Troubleshooting

- If you encounter ChromeDriver version issues, try updating Chrome to the latest version
- Make sure you have enough disk space for Chrome to run in headless mode
- Check the terminal output for any error messages

## License

This project is open source and available under the [MIT License](LICENSE).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

2. Install the required dependencies:

```bash
pip install -r requirements.txt
```

3. Make sure you have Chrome browser installed on your system

## Usage

1. Start the Flask application:

```bash
python app.py
```

2. Open your web browser and navigate to:

```
http://localhost:5000
```

3. Enter a URL in the input field and click "Convert to Markdown"

4. The application will scrape the content, convert it to markdown, and display both the raw markdown and a rendered preview

5. You can copy the markdown to your clipboard by clicking the "Copy Markdown" button

## How It Works

1. The frontend sends the URL to the backend endpoint `/scrape-and-convert`
2. The backend uses `undetected_chromedriver` to open the URL in a headless Chrome browser
3. It attempts to find the main content of the page using common CSS selectors
4. The HTML content is converted to markdown using the `html2text` library
5. The markdown is returned to the frontend, which displays both the raw markdown and a rendered preview

## Troubleshooting

- If you encounter issues with Chrome not starting, make sure you have the latest version of Chrome installed
- For some websites, the content extraction might not be perfect due to their complex structure
- Some websites might block automated access; in such cases, the application might not be able to scrape the content

## License

This project is open source and available under the MIT License.
