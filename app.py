from flask import Flask, request, jsonify, render_template, send_file
import undetected_chromedriver as uc
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import html2text
import time
import re
import logging
import os
import sys
from urllib.parse import urlparse

app = Flask(__name__)
logging.basicConfig(level=logging.INFO)

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
            return jsonify({'error': 'Invalid URL format'}), 400
    except Exception as e:
        return jsonify({'error': f'Invalid URL: {str(e)}'}), 400
    
    try:
        markdown_content = scrape_url_to_markdown(url)
        return jsonify({'markdown': markdown_content})
    except Exception as e:
        logging.error(f"Error processing URL {url}: {str(e)}")
        return jsonify({'error': f'Failed to process URL: {str(e)}'}), 500

def get_undetected_chrome_driver():
    """Initialize and return an undetected Chrome WebDriver with appropriate options."""
    options = uc.ChromeOptions()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--window-size=1920,1080')

    # Try to find Chrome browser in common locations
    chrome_paths = [
        "/usr/bin/google-chrome",  # Added this path
        "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
        "/Applications/Google Chrome Canary.app/Contents/MacOS/Google Chrome Canary",
        "/Applications/Chromium.app/Contents/MacOS/Chromium"
    ]
    chrome_binary = None
    for path in chrome_paths:
        if os.path.exists(path):
            chrome_binary = path
            break
    if chrome_binary:
        options.binary_location = chrome_binary
    # Only set binary_location if a valid path is found
    elif hasattr(options, 'binary_location') and options.binary_location:
        del options.binary_location
    # If not found, fail with a clear error
    if not chrome_binary:
        raise RuntimeError(
            "Google Chrome not found in standard locations. "
            "Please install Google Chrome or specify its path in the code."
        )
    driver = uc.Chrome(options=options)
    return driver


def scrape_url_to_markdown(url):
    """Scrape the given URL and convert the main content to markdown."""
    driver = None
    try:
        # Initialize the undetected Chrome driver
        driver = get_undetected_chrome_driver()
        driver.get(url)
        
        # Wait for the page to load
        time.sleep(2)
        
        # Extract the main content
        # This is a simple approach that works for many sites
        # For more complex sites, you might need to adjust the selectors
        main_content_selectors = [
            "main", "article", "#content", ".content", 
            "#main", ".main", ".post-content", ".article-content",
            ".entry-content", "#article-content", "body"
        ]
        
        content_html = ""
        for selector in main_content_selectors:
            try:
                elements = driver.find_elements(By.CSS_SELECTOR, selector)
                if elements:
                    # Use the first matching element
                    content_html = elements[0].get_attribute('outerHTML')
                    break
            except Exception as e:
                logging.warning(f"Error with selector {selector}: {str(e)}")
        
        if not content_html:
            # If no specific content container found, use the body
            content_html = driver.find_element(By.TAG_NAME, "body").get_attribute('outerHTML')
        
        # Convert HTML to markdown
        h = html2text.HTML2Text()
        h.ignore_links = False
        h.ignore_images = False
        h.ignore_tables = False
        h.body_width = 0  # No wrapping
        
        markdown = h.handle(content_html)
        
        # Clean up the markdown (remove excessive newlines, etc.)
        markdown = re.sub(r'\n{3,}', '\n\n', markdown)
        
        return markdown
    
    finally:
        if driver:
            driver.quit()

@app.route('/download-markdown-zip', methods=['POST'])
def download_markdown_zip():
    data = request.get_json()
    markdown = data.get('markdown')
    url = data.get('url', 'output')
    if not markdown:
        return jsonify({'error': 'Markdown content required'}), 400

    import io
    import zipfile
    from datetime import datetime
    # Use a safe filename
    import re
    safe_url = re.sub(r'[^a-zA-Z0-9_-]', '_', url)[:40] or 'output'
    filename = f"{safe_url}.md"
    zip_filename = f"{safe_url}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.zip"

    mem_zip = io.BytesIO()
    with zipfile.ZipFile(mem_zip, mode='w', compression=zipfile.ZIP_DEFLATED) as zf:
        zf.writestr(filename, markdown)
    mem_zip.seek(0)
    return send_file(
        mem_zip,
        mimetype='application/zip',
        as_attachment=True,
        download_name=zip_filename
    )

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5002)
