from flask import Flask, request, jsonify, render_template, send_file
import undetected_chromedriver as uc
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, WebDriverException
import html2text
import time # Keep for download_markdown_zip timestamp, not for sleeps
import re
import logging
import os
import sys
from urllib.parse import urlparse
import io
import zipfile
from datetime import datetime

app = Flask(__name__)
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/scrape-and-convert', methods=['POST'])
def scrape_and_convert():
    data = request.get_json()
    url = data.get('url')
    
    if not url:
        return jsonify({'error': 'URL is required'}), 400
    
    # Validate URL
    try:
        result = urlparse(url)
        if not all([result.scheme, result.netloc]):
            return jsonify({'error': 'Invalid URL format. Ensure it includes a scheme (e.g., http, https) and domain.'}), 400
        if result.scheme not in ['http', 'https']: # Restrict schemes
            return jsonify({'error': 'Invalid URL scheme. Only HTTP and HTTPS URLs are allowed.'}), 400
    except Exception as e: # Catch potential errors from urlparse itself on very malformed input
        logging.error(f"URL parsing error for '{url}': {str(e)}")
        return jsonify({'error': f'Invalid URL provided: {str(e)}'}), 400
    
    try:
        markdown_content = scrape_url_to_markdown(url)
        return jsonify({'markdown': markdown_content})
    except TimeoutException as te:
        logging.error(f"Timeout error processing URL {url}: {str(te)}", exc_info=True)
        return jsonify({'error': f'The request timed out while trying to load or process the URL: {url}. The page might be too slow or inaccessible.'}), 500
    except WebDriverException as wde:
        logging.error(f"WebDriver error processing URL {url}: {str(wde)}", exc_info=True)
        return jsonify({'error': 'A browser automation error occurred. The website might be incompatible or blocking automation.'}), 500
    except RuntimeError as rne: # Catch custom RuntimeError from scrape_url_to_markdown
        logging.error(f"Runtime error processing URL {url}: {str(rne)}", exc_info=True)
        return jsonify({'error': str(rne)}), 500
    except Exception as e:
        logging.error(f"Unexpected error processing URL {url}: {str(e)}", exc_info=True)
        return jsonify({'error': 'An unexpected error occurred. Please try again later or contact support if the issue persists.'}), 500

def get_undetected_chrome_driver():
    """Initialize and return an undetected Chrome WebDriver with appropriate options."""
    options = uc.ChromeOptions()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--window-size=1920,1080')

    driver = None
    chrome_binary_path_used = "PATH (default)"

    # 1. Try without explicit binary_location (relies on PATH)
    try:
        logging.info("Attempting to initialize Chrome driver using PATH...")
        driver = uc.Chrome(options=options)
        logging.info("Chrome driver initialized using PATH.")
        return driver
    except Exception as e: # More specific: WebDriverException or similar if possible
        logging.warning(f"Could not initialize Chrome driver using PATH: {str(e)}")

    # 2. Try environment variable
    env_chrome_path = os.environ.get("CHROME_BINARY_PATH")
    if env_chrome_path:
        logging.info(f"Attempting to initialize Chrome driver using CHROME_BINARY_PATH: {env_chrome_path}...")
        options_env = uc.ChromeOptions() # Create new options object to avoid modifying the original
        options_env.add_argument('--headless=new')
        options_env.add_argument('--no-sandbox')
        options_env.add_argument('--disable-dev-shm-usage')
        options_env.add_argument('--disable-gpu')
        options_env.add_argument('--window-size=1920,1080')
        options_env.binary_location = env_chrome_path
        try:
            driver = uc.Chrome(options=options_env)
            logging.info(f"Chrome driver initialized using CHROME_BINARY_PATH: {env_chrome_path}.")
            chrome_binary_path_used = env_chrome_path
            return driver
        except Exception as e:
            logging.warning(f"Could not initialize Chrome driver using CHROME_BINARY_PATH '{env_chrome_path}': {str(e)}")

    # 3. Fallback to common macOS paths (if on macOS)
    if sys.platform == "darwin": # Check if on macOS
        logging.info("Attempting to initialize Chrome driver using common macOS paths...")
        mac_chrome_paths = [
            "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
            "/Applications/Google Chrome Canary.app/Contents/MacOS/Google Chrome Canary",
            "/Applications/Chromium.app/Contents/MacOS/Chromium"
        ]
        for path in mac_chrome_paths:
            if os.path.exists(path):
                options_mac = uc.ChromeOptions() # Create new options object
                options_mac.add_argument('--headless=new')
                options_mac.add_argument('--no-sandbox')
                options_mac.add_argument('--disable-dev-shm-usage')
                options_mac.add_argument('--disable-gpu')
                options_mac.add_argument('--window-size=1920,1080')
                options_mac.binary_location = path
                try:
                    driver = uc.Chrome(options=options_mac)
                    logging.info(f"Chrome driver initialized using macOS path: {path}.")
                    chrome_binary_path_used = path
                    return driver
                except Exception as e:
                    logging.warning(f"Could not initialize Chrome driver using macOS path '{path}': {str(e)}")

    # If all methods fail
    error_message = (
        "Google Chrome/Chromium not found or driver initialization failed. "
        "Please ensure Chrome/Chromium is installed and accessible via system PATH, "
        "CHROME_BINARY_PATH environment variable, or for macOS, in one of the standard application paths. "
        f"Last attempted source: {chrome_binary_path_used}."
    )
    logging.error(error_message)
    raise RuntimeError(error_message)


def scrape_url_to_markdown(url):
    """Scrape the given URL and convert the main content to markdown."""
    driver = None
    try:
        driver = get_undetected_chrome_driver()
        logging.info(f"Navigating to URL: {url}")
        driver.get(url)
        
        try:
            WebDriverWait(driver, 15).until( # Increased timeout to 15 seconds
                EC.presence_of_element_located((By.TAG_NAME, "body"))
            )
            logging.info(f"Page body loaded for URL: {url}")
        except TimeoutException:
            logging.error(f"Timeout waiting for page body to load for URL: {url}")
            raise RuntimeError(f"Page {url} timed out while loading. Content might be incomplete or unavailable.")
        
        main_content_selectors = [
            "main", "article", "[role='main']", "#content", ".content",
            "#main", ".main", ".post-content", ".article-content",
            ".entry-content", "#article-body", ".article-body", "body"
        ] # Added more selectors like [role='main'] and article-body
        
        content_html = ""
        selected_selector = "body (fallback)" # Default if nothing specific is found
        for selector in main_content_selectors:
            try:
                elements = driver.find_elements(By.CSS_SELECTOR, selector)
                if elements:
                    logging.info(f"Found content for URL {url} with selector: '{selector}'")
                    content_html = elements[0].get_attribute('outerHTML')
                    selected_selector = selector
                    break
            except Exception as e: # Catching generic exception here as find_elements can throw various things
                logging.warning(f"Error finding elements with selector '{selector}' for URL {url}: {str(e)}")
        
        if not content_html:
            logging.warning(f"No specific main content selectors matched for {url}. Using entire body.")
            # Fallback to body if no specific content container found, which is already tried as the last selector
            # This explicit check might be redundant if "body" is always last and works.
            # However, to be safe, ensure body's HTML is fetched if loop finishes without break.
            if selected_selector == "body (fallback)": # if loop completed without break
                 content_html = driver.find_element(By.TAG_NAME, "body").get_attribute('outerHTML')


        logging.info(f"Converting HTML to Markdown for URL {url} (using content from selector: '{selected_selector}')")
        h = html2text.HTML2Text()
        h.ignore_links = False
        h.ignore_images = False
        h.ignore_tables = False
        h.body_width = 0
        
        markdown = h.handle(content_html)
        markdown = re.sub(r'\n{3,}', '\n\n', markdown).strip()
        logging.info(f"Successfully converted URL {url} to Markdown.")
        return markdown
    
    finally:
        if driver:
            logging.info(f"Quitting WebDriver for URL: {url}")
            driver.quit()

@app.route('/download-markdown-zip', methods=['POST'])
def download_markdown_zip():
    data = request.get_json()
    markdown = data.get('markdown')
    url = data.get('url', 'output') # Default URL if not provided
    if not markdown:
        return jsonify({'error': 'Markdown content required'}), 400

    safe_url = re.sub(r'[^a-zA-Z0-9_-]', '_', urlparse(url).netloc + urlparse(url).path)[:60] or 'output'
    filename_md = f"{safe_url}.md"
    zip_filename = f"{safe_url}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.zip"

    mem_zip = io.BytesIO()
    with zipfile.ZipFile(mem_zip, mode='w', compression=zipfile.ZIP_DEFLATED) as zf:
        zf.writestr(filename_md, markdown)
    mem_zip.seek(0)

    logging.info(f"Preparing ZIP file for download: {zip_filename} (Markdown: {filename_md})")
    return send_file(
        mem_zip,
        mimetype='application/zip',
        as_attachment=True,
        download_name=zip_filename
    )

if __name__ == '__main__':
    # Port will be set by start.sh or Flask default if run directly
    app.run(debug=False, host='0.0.0.0', port=int(os.environ.get('PORT', 5002)))
