<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?= htmlspecialchars(APP_NAME) ?></h1>
            <p class="subtitle"><?= htmlspecialchars($pageDescription) ?></p>
        </header>

        <main class="main">
            <!-- URL Input Form -->
            <section class="input-section">
                <form id="convertForm" class="convert-form">
                    <div class="form-group">
                        <label for="url">Enter URL to convert:</label>
                        <input 
                            type="url" 
                            id="url" 
                            name="url" 
                            placeholder="https://example.com/article" 
                            required
                            class="url-input"
                        >
                    </div>
                    <button type="submit" class="convert-btn" id="convertBtn">
                        <span class="btn-text">Convert to Markdown</span>
                        <span class="btn-spinner" style="display: none;">Converting...</span>
                    </button>
                </form>
            </section>

            <!-- Progress Section -->
            <section class="progress-section" id="progressSection" style="display: none;">
                <h2>Conversion Progress</h2>
                
                <!-- Progress Bar -->
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <span class="progress-text" id="progressText">0%</span>
                </div>

                <!-- Step-by-step Log -->
                <div class="progress-log">
                    <h3>Process Log:</h3>
                    <ul id="progressLog" class="log-list"></ul>
                </div>
            </section>

            <!-- Results Section -->
            <section class="results-section" id="resultsSection" style="display: none;">
                <h2>Conversion Results</h2>
                
                <div class="results-actions">
                    <button id="downloadBtn" class="download-btn">Download Markdown File</button>
                    <button id="newConversionBtn" class="new-conversion-btn">Convert Another URL</button>
                </div>

                <!-- Side-by-side Preview -->
                <div class="preview-container">
                    <div class="preview-panel">
                        <h3>Raw Markdown</h3>
                        <div class="markdown-content">
                            <pre id="markdownContent"></pre>
                        </div>
                    </div>
                    
                    <div class="preview-panel">
                        <h3>Rendered Preview</h3>
                        <div class="html-content" id="htmlContent"></div>
                    </div>
                </div>
            </section>

            <!-- Error Section -->
            <section class="error-section" id="errorSection" style="display: none;">
                <h2>Conversion Error</h2>
                <div class="error-message" id="errorMessage"></div>
                <button id="retryBtn" class="retry-btn">Try Again</button>
            </section>
        </main>

        <footer class="footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?> v<?= htmlspecialchars(APP_VERSION) ?></p>
        </footer>
    </div>

    <script src="/js/app.js"></script>
</body>
</html>
