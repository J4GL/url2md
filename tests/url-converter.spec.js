const { test, expect } = require('@playwright/test');

test.describe('URL to Markdown Converter', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('should display the main page correctly', async ({ page }) => {
    // User goal: Verify the main page loads with all required elements
    // Success indicator: Page title and main form are visible
    
    await expect(page).toHaveTitle(/URL to Markdown Converter/);
    await expect(page.locator('h1')).toContainText('URL to Markdown Converter');
    await expect(page.locator('#url')).toBeVisible();
    await expect(page.locator('#convertBtn')).toBeVisible();
    await expect(page.locator('#convertBtn')).toContainText('Convert to Markdown');
  });

  test('should show validation error for empty URL', async ({ page }) => {
    // User goal: Test form validation for empty input
    // Success indicator: Error message appears when submitting empty form
    
    await page.click('#convertBtn');
    
    // Check for HTML5 validation or custom error
    const urlInput = page.locator('#url');
    await expect(urlInput).toHaveAttribute('required');
  });

  test('should show validation error for invalid URL', async ({ page }) => {
    // User goal: Test form validation for invalid URL format
    // Success indicator: Browser validation prevents submission of invalid URL
    
    await page.fill('#url', 'not-a-valid-url');
    await page.click('#convertBtn');
    
    // HTML5 validation should prevent submission
    const urlInput = page.locator('#url');
    const validationMessage = await urlInput.evaluate(el => el.validationMessage);
    expect(validationMessage).toBeTruthy();
  });

  test('should handle conversion process with progress tracking', async ({ page }) => {
    // User goal: Convert a URL and see progress tracking
    // Success indicator: Progress bar appears and shows conversion steps

    // Use a simple, reliable test URL
    await page.fill('#url', 'https://example.com');

    // Start conversion
    await page.click('#convertBtn');

    // Check that button shows loading state
    await expect(page.locator('.btn-spinner')).toBeVisible();
    await expect(page.locator('#convertBtn')).toBeDisabled();

    // Check that progress section appears
    await expect(page.locator('#progressSection')).toBeVisible({ timeout: 15000 });

    // Check progress bar
    await expect(page.locator('#progressBar')).toBeVisible();
    await expect(page.locator('#progressText')).toBeVisible();

    // Wait for conversion to complete or show results/error
    await page.waitForFunction(() => {
      const resultsSection = document.querySelector('#resultsSection');
      const errorSection = document.querySelector('#errorSection');
      return (resultsSection && resultsSection.style.display !== 'none') ||
             (errorSection && errorSection.style.display !== 'none');
    }, { timeout: 120000 });

    // Check if we got results or an error
    const resultsVisible = await page.locator('#resultsSection').isVisible();
    const errorVisible = await page.locator('#errorSection').isVisible();

    expect(resultsVisible || errorVisible).toBeTruthy();

    if (resultsVisible) {
      // Test successful conversion
      await expect(page.locator('#markdownContent')).toBeVisible();
      await expect(page.locator('#htmlContent')).toBeVisible();
      await expect(page.locator('#downloadBtn')).toBeVisible();
      await expect(page.locator('#newConversionBtn')).toBeVisible();

      // Check that markdown content is not empty
      const markdownContent = await page.locator('#markdownContent').textContent();
      expect(markdownContent.trim()).toBeTruthy();

      // Check that HTML preview is not empty
      const htmlContent = await page.locator('#htmlContent').innerHTML();
      expect(htmlContent.trim()).toBeTruthy();
    }
  });

  test('should allow starting a new conversion', async ({ page }) => {
    // User goal: Start a new conversion after completing one
    // Success indicator: Form resets and is ready for new input

    // Fill and submit a URL first
    await page.fill('#url', 'https://example.com');
    await page.click('#convertBtn');

    // Wait for some progress or completion
    await page.waitForSelector('#progressSection', { state: 'visible', timeout: 15000 });

    // Wait for either completion or error
    await page.waitForFunction(() => {
      const resultsSection = document.querySelector('#resultsSection');
      const errorSection = document.querySelector('#errorSection');
      return (resultsSection && resultsSection.style.display !== 'none') ||
             (errorSection && errorSection.style.display !== 'none');
    }, { timeout: 120000 });

    // Click new conversion button if results are shown, or retry if error
    const newConversionBtn = page.locator('#newConversionBtn');
    const retryBtn = page.locator('#retryBtn');

    if (await newConversionBtn.isVisible()) {
      await newConversionBtn.click();
    } else if (await retryBtn.isVisible()) {
      await retryBtn.click();
    }

    // Check that form is reset
    await expect(page.locator('#url')).toHaveValue('');
    await expect(page.locator('#progressSection')).toBeHidden();
    await expect(page.locator('#resultsSection')).toBeHidden();
    await expect(page.locator('#errorSection')).toBeHidden();
  });

  test('should have responsive design elements', async ({ page }) => {
    // User goal: Verify the interface works on different screen sizes
    // Success indicator: Layout adapts properly to mobile viewport
    
    // Test mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await expect(page.locator('.container')).toBeVisible();
    await expect(page.locator('#url')).toBeVisible();
    await expect(page.locator('#convertBtn')).toBeVisible();
    
    // Check that elements are still accessible
    const urlInput = page.locator('#url');
    await expect(urlInput).toBeVisible();
    
    // Test desktop viewport
    await page.setViewportSize({ width: 1200, height: 800 });
    
    await expect(page.locator('.container')).toBeVisible();
    await expect(page.locator('#url')).toBeVisible();
    await expect(page.locator('#convertBtn')).toBeVisible();
  });

  test('should display proper error handling', async ({ page }) => {
    // User goal: See appropriate error messages for failed conversions
    // Success indicator: Error section shows with retry option

    // Use an invalid URL that should cause an error
    await page.fill('#url', 'https://this-domain-does-not-exist-12345.com');
    await page.click('#convertBtn');

    // For invalid domains, the error might happen so quickly that progress section doesn't appear
    // So we wait for either progress section OR error section
    await page.waitForFunction(() => {
      const progressSection = document.querySelector('#progressSection');
      const errorSection = document.querySelector('#errorSection');
      return (progressSection && progressSection.style.display !== 'none') ||
             (errorSection && errorSection.style.display !== 'none');
    }, { timeout: 15000 });

    // Wait for either results or error (should be error for invalid domain)
    await page.waitForFunction(() => {
      const resultsSection = document.querySelector('#resultsSection');
      const errorSection = document.querySelector('#errorSection');
      return (resultsSection && resultsSection.style.display !== 'none') ||
             (errorSection && errorSection.style.display !== 'none');
    }, { timeout: 120000 });

    // Should show error section
    const errorVisible = await page.locator('#errorSection').isVisible();
    expect(errorVisible).toBeTruthy();

    await expect(page.locator('#errorMessage')).toBeVisible();
    await expect(page.locator('#retryBtn')).toBeVisible();

    // Check that error message is not empty
    const errorText = await page.locator('#errorMessage').textContent();
    expect(errorText.trim()).toBeTruthy();
  });
});
