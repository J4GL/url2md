# URL to Markdown Converter

A web application that converts web pages into clean, readable Markdown format. Built with Python Flask and Selenium, this tool can handle JavaScript-rendered content and provides a simple web interface for easy conversion.

## Features

- Converts any public webpage to Markdown format
- Handles JavaScript-rendered content using headless Chrome
- Extracts and focuses on the main content of the page
- Provides both raw markdown and a rendered preview
- Simple and intuitive web interface
- API endpoint for programmatic access
- Designed with cross-platform use in mind (macOS, Linux, Windows) with appropriate setup.

## Prerequisites

Before you begin, ensure you have the following installed:

- Python 3.7 or higher
- `pip` (Python package manager, usually comes with Python)
- Google Chrome or Chromium browser
- `setuptools` (Python package, especially for Python 3.12+ compatibility)

## Installation

You can set up the project using the provided script for macOS or by following manual steps for other operating systems.

### Using `setup.sh` (macOS only)

This script automates the setup process for macOS users.

1.  Clone the repository:
    ```bash
    git clone https://github.com/J4GL/url2md.git
    cd url2md
    ```

2.  Make the setup script executable and run it:
    ```bash
    chmod +x setup.sh
    ./setup.sh
    ```

    This script will automatically:
    - Check for and attempt to install required system dependencies (Python 3, pip, Google Chrome via Homebrew).
    - Create a Python virtual environment named `venv`.
    - Ensure `setuptools` is installed/updated within the virtual environment.
    - Install Python dependencies from `requirements.txt`.
    - Installs `undetected-chromedriver` (via pip), which automatically manages ChromeDriver versions compatible with your installed Chrome browser.

### Manual Installation (Linux, Windows, or other systems)

Follow these steps to set up the project manually:

1.  **Install Python 3 & pip:**
    *   Download Python from [python.org](https://www.python.org/downloads/) or use your system's package manager (e.g., `sudo apt install python3 python3-pip` on Debian/Ubuntu). Ensure pip is available.

2.  **Install Google Chrome:**
    *   Download and install Google Chrome from [google.com/chrome](https://www.google.com/chrome/).

3.  **Clone the Repository:**
    ```bash
    git clone https://github.com/J4GL/url2md.git
    cd url2md
    ```

4.  **Create a Python Virtual Environment:**
    It's highly recommended to use a virtual environment.
    ```bash
    python3 -m venv venv
    ```

5.  **Activate the Virtual Environment:**
    *   On macOS and Linux:
        ```bash
        source venv/bin/activate
        ```
    *   On Windows (Command Prompt/PowerShell):
        ```bash
        venv\Scripts\activate
        ```

6.  **Install Dependencies:**
    Ensure `setuptools` is up-to-date and then install packages from `requirements.txt`:
    ```bash
    pip install --upgrade setuptools
    pip install -r requirements.txt
    ```

## Usage

1.  Ensure your virtual environment is activated if you set it up manually.
2.  Start the application using the `start.sh` script:
    ```bash
    ./start.sh
    ```
    *(Note: For Windows users, or if `start.sh` is not suitable for your Linux distribution due to its use of `lsof`, you can run the Flask app directly after activating the venv: `export FLASK_APP=app.py; export FLASK_ENV=development; python3 -m flask run --port 5000` or the equivalent `set` commands for Windows CMD.)*

3.  Open your web browser and navigate to the URL provided in the terminal. `start.sh` will attempt to use port 5000 first, then try ports up to 5010 if the default is busy (e.g., `http://localhost:5000`).

4.  Enter the URL of the webpage you want to convert and click "Convert to Markdown".

5.  The converted Markdown will be displayed, and you can copy it or download it as a ZIP file containing a `.md` file.

## API Endpoint

You can also use the conversion service programmatically by sending a POST request:

-   **URL:** `/scrape-and-convert`
-   **Method:** `POST`
-   **Headers:** `Content-Type: application/json`
-   **Body:**
    ```json
    {
        "url": "https://example.com"
    }
    ```

### Example Response

```json
{
    "markdown": "# Example Domain\n\nThis is the converted markdown content..."
}
```

## How It Works

1.  The frontend (from `templates/index.html`) sends the target URL to the backend API endpoint (`/scrape-and-convert`) in `app.py`.
2.  The backend uses `undetected-chromedriver` to launch a headless instance of Google Chrome, navigating to the provided URL. This allows processing of JavaScript-rendered content.
3.  Selenium attempts to identify the main content of the page by looking for common HTML5 structural elements (like `<main>`, `<article>`) or common CSS selectors (like `#content`, `.main`). If specific selectors fail, it falls back to using the entire `<body>`.
4.  The extracted HTML content is then converted to Markdown format using the `html2text` Python library.
5.  The resulting Markdown text is returned to the frontend, which displays it and also renders an HTML preview using `marked.js`.

## Project Structure

```
url2md/
├── app.py                # Main Flask application logic
├── requirements.txt      # Python dependencies for pip
├── setup.sh              # Setup script (primarily for macOS)
├── start.sh              # Script to start the Flask development server
├── templates/
│   └── index.html        # HTML template for the web interface
├── venv/                 # Python virtual environment (created after setup)
└── README.md             # This file
```

## Dependencies

-   **Flask:** A micro web framework for Python, used for the backend server and API.
-   **Selenium:** A browser automation tool, used to control Chrome and scrape web content.
-   **undetected-chromedriver:** A specialized version of ChromeDriver that helps avoid bot detection measures.
-   **html2text:** A Python library used to convert HTML content into Markdown.

## Troubleshooting

-   **ChromeDriver/Chrome Version Mismatch:** `undetected-chromedriver` aims to automatically download a compatible ChromeDriver. If you see errors related to this (e.g., "session not created"), ensure your Google Chrome browser is updated to the latest version. You might also try updating `undetected-chromedriver` itself: `pip install --upgrade undetected-chromedriver`.
-   **Content Extraction Issues:** For some websites with very complex or unusual structures, the automatic main content extraction might not be perfect. The tool tries common selectors but may sometimes grab too little or too much content.
-   **Blocked Access:** Some websites employ robust measures to block automated scraping. If the application consistently fails for a specific site, it might be due to such blocks.
-   **`lsof` not found (for `start.sh` on some Linux systems):** The `start.sh` script uses `lsof` to find an available port. If `lsof` is not installed on your Linux system, you may need to install it (e.g., `sudo apt install lsof`) or run the Flask application manually as described in the "Usage" section.
-   **General Errors:** Always check the terminal output where you ran `./start.sh` or `python3 app.py` for specific error messages from Flask or Selenium. These messages often provide clues about what went wrong.
-   **Python 3.12+ `setuptools` issue:** If you encounter errors related to `distutils` especially on Python 3.12 or newer, ensure `setuptools` is installed and up-to-date in your virtual environment (`pip install --upgrade setuptools`). The `setup.sh` script and manual installation instructions include this step.

## License

This project is open source and available under the MIT License. You should include a `LICENSE` file in the root of your repository containing the full text of the MIT License.
*(Note: A `LICENSE` file was not found in the repository at the time of this writing. Please add one to comply with open-source best practices if distributing under MIT License.)*

## Contributing

Contributions are welcome! If you have improvements, bug fixes, or new features, please feel free to:

1.  Fork the repository.
2.  Create a new branch for your changes.
3.  Make your changes and commit them with clear messages.
4.  Submit a Pull Request for review.
```
