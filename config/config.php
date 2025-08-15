<?php
/**
 * Application Configuration
 */

declare(strict_types=1);

// Application settings
define('APP_NAME', 'URL to Markdown Converter');
define('APP_VERSION', '1.0.0');

// File storage settings
define('STORAGE_PATH', APP_ROOT . '/storage');
define('TEMP_PATH', STORAGE_PATH . '/temp');
define('DOWNLOADS_PATH', STORAGE_PATH . '/downloads');

// Create storage directories if they don't exist
if (!is_dir(STORAGE_PATH)) {
    mkdir(STORAGE_PATH, 0755, true);
}
if (!is_dir(TEMP_PATH)) {
    mkdir(TEMP_PATH, 0755, true);
}
if (!is_dir(DOWNLOADS_PATH)) {
    mkdir(DOWNLOADS_PATH, 0755, true);
}

// HTTP settings
define('USER_AGENT', 'URL2MD Converter/1.0 (PHP)');
define('REQUEST_TIMEOUT', 30);

// Increase execution time for URL fetching
set_time_limit(120);

// Progress tracking
define('PROGRESS_STEPS', [
    'validate_url' => 'Validating URL',
    'fetch_content' => 'Fetching webpage content',
    'parse_html' => 'Parsing HTML structure',
    'convert_markdown' => 'Converting to Markdown',
    'save_file' => 'Saving Markdown file',
    'complete' => 'Conversion complete'
]);
