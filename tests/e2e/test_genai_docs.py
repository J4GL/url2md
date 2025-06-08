import re
from playwright.sync_api import Page, expect

# Define the target URL for the local application
LOCAL_APP_URL = "http://localhost:5000/"
URL_TO_SCRAPE = "https://googleapis.github.io/python-genai/"
SUCCESS_MESSAGE = "Conversion completed successfully!"

def test_scrape_and_convert_success(page: Page):
    # Navigate to the local application
    page.goto(LOCAL_APP_URL)

    # Find the URL input field and fill it
    url_input = page.locator("#urlInput")
    expect(url_input).to_be_visible()
    url_input.fill(URL_TO_SCRAPE)

    # Find the submit button and click it
    submit_button = page.locator("#submitBtn")
    expect(submit_button).to_be_visible()
    expect(submit_button).to_be_enabled()
    submit_button.click()

    # Wait for the success message to appear in the status div
    status_message = page.locator("#status")

    # Increased timeout for the message to appear, as scraping can take time
    expect(status_message).to_have_text(SUCCESS_MESSAGE, timeout=30000) # 30 seconds timeout
    expect(status_message).to_have_class(re.compile(r'success'))

    # Also check that the output section is visible
    output_section = page.locator("#outputSection")
    expect(output_section).to_be_visible(timeout=10000)

    # Check if markdown output has some content
    markdown_output = page.locator("#markdownOutput")
    expect(markdown_output).not_to_be_empty(timeout=5000)
