<?php

namespace Models;

/**
 * URL Converter Model
 * Handles the conversion of URLs to Markdown
 */
class UrlConverter
{
    private function updateProgress(string $step, int $progress): void
    {
        $_SESSION['conversion_progress']['current_step'] = $step;
        $_SESSION['conversion_progress']['progress'] = $progress;
        $_SESSION['conversion_progress']['steps'][] = [
            'step' => $step,
            'message' => PROGRESS_STEPS[$step],
            'timestamp' => date('H:i:s'),
            'completed' => true
        ];
    }
    
    public function convertUrl(string $url): array
    {
        // Step 1: Validate URL
        $this->updateProgress('validate_url', 16);
        $this->validateUrl($url);
        
        // Step 2: Fetch content
        $this->updateProgress('fetch_content', 33);
        $html = $this->fetchContent($url);
        
        // Step 3: Parse HTML
        $this->updateProgress('parse_html', 50);
        $cleanHtml = $this->parseHtml($html);
        
        // Step 4: Convert to Markdown
        $this->updateProgress('convert_markdown', 66);
        $markdown = $this->convertToMarkdown($cleanHtml, $url);
        
        // Step 5: Save file
        $this->updateProgress('save_file', 83);
        $fileId = $this->saveMarkdownFile($markdown);
        
        // Step 6: Complete
        $this->updateProgress('complete', 100);
        
        // Generate HTML preview
        $html = $this->markdownToHtml($markdown);
        
        $_SESSION['conversion_progress']['file_id'] = $fileId;
        
        return [
            'file_id' => $fileId,
            'filename' => $fileId . '.md',
            'markdown' => $markdown,
            'html' => $html
        ];
    }
    
    private function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL format');
        }
        
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            throw new \InvalidArgumentException('Only HTTP and HTTPS URLs are supported');
        }
    }
    
    private function fetchContent(string $url): string
    {
        // Try cURL first, then fall back to file_get_contents
        if (function_exists('curl_init')) {
            return $this->fetchWithCurl($url);
        } else {
            return $this->fetchWithFileGetContents($url);
        }
    }

    private function fetchWithCurl(string $url): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => REQUEST_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => USER_AGENT,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive'
            ],
            CURLOPT_ENCODING => '', // Handle gzip automatically
            CURLOPT_SSL_VERIFYPEER => false, // For development - should be true in production
            CURLOPT_SSL_VERIFYHOST => false  // For development - should be true in production
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($html === false || !empty($error)) {
            throw new \RuntimeException('Failed to fetch content from URL: ' . $error);
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("HTTP Error $httpCode when fetching URL");
        }

        return $html;
    }

    private function fetchWithFileGetContents(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: ' . USER_AGENT,
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Accept-Encoding: gzip, deflate',
                    'Connection: keep-alive'
                ],
                'timeout' => REQUEST_TIMEOUT,
                'follow_location' => true,
                'max_redirects' => 5
            ]
        ]);

        $html = @file_get_contents($url, false, $context);

        if ($html === false) {
            throw new \RuntimeException('Failed to fetch content from URL');
        }

        return $html;
    }
    
    private function parseHtml(string $html): string
    {
        // Create DOMDocument
        $dom = new \DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        // Load HTML
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Clear libxml errors
        libxml_clear_errors();
        
        // Remove script and style elements
        $this->removeElements($dom, ['script', 'style', 'nav', 'footer', 'aside']);
        
        // Extract main content
        $mainContent = $this->extractMainContent($dom);
        
        return $dom->saveHTML($mainContent);
    }
    
    private function removeElements(\DOMDocument $dom, array $tagNames): void
    {
        foreach ($tagNames as $tagName) {
            $elements = $dom->getElementsByTagName($tagName);
            $elementsToRemove = [];
            
            foreach ($elements as $element) {
                $elementsToRemove[] = $element;
            }
            
            foreach ($elementsToRemove as $element) {
                $element->parentNode->removeChild($element);
            }
        }
    }
    
    private function extractMainContent(\DOMDocument $dom): \DOMNode
    {
        // Try to find main content area
        $selectors = ['main', 'article', '[role="main"]', '.content', '#content', '.post', '.entry'];

        foreach ($selectors as $selector) {
            $xpath = new \DOMXPath($dom);
            $nodes = $xpath->query("//*[@class='content' or @id='content' or local-name()='main' or local-name()='article']");

            if ($nodes->length > 0) {
                return $nodes->item(0);
            }
        }

        // Fallback to body
        $body = $dom->getElementsByTagName('body')->item(0);
        return $body ?: $dom->documentElement;
    }

    private function convertToMarkdown(string $html, string $originalUrl): string
    {
        // Simple HTML to Markdown conversion
        $markdown = $html;

        // Convert headers
        $markdown = preg_replace('/<h1[^>]*>(.*?)<\/h1>/is', '# $1', $markdown);
        $markdown = preg_replace('/<h2[^>]*>(.*?)<\/h2>/is', '## $1', $markdown);
        $markdown = preg_replace('/<h3[^>]*>(.*?)<\/h3>/is', '### $1', $markdown);
        $markdown = preg_replace('/<h4[^>]*>(.*?)<\/h4>/is', '#### $1', $markdown);
        $markdown = preg_replace('/<h5[^>]*>(.*?)<\/h5>/is', '##### $1', $markdown);
        $markdown = preg_replace('/<h6[^>]*>(.*?)<\/h6>/is', '###### $1', $markdown);

        // Convert paragraphs
        $markdown = preg_replace('/<p[^>]*>(.*?)<\/p>/is', '$1' . "\n\n", $markdown);

        // Convert line breaks
        $markdown = preg_replace('/<br\s*\/?>/i', "\n", $markdown);

        // Convert links
        $markdown = preg_replace('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', '[$2]($1)', $markdown);

        // Convert images
        $markdown = preg_replace('/<img[^>]*src=["\']([^"\']*)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*\/?>/is', '![$2]($1)', $markdown);
        $markdown = preg_replace('/<img[^>]*alt=["\']([^"\']*)["\'][^>]*src=["\']([^"\']*)["\'][^>]*\/?>/is', '![$1]($2)', $markdown);
        $markdown = preg_replace('/<img[^>]*src=["\']([^"\']*)["\'][^>]*\/?>/is', '![]($1)', $markdown);

        // Convert bold and italic
        $markdown = preg_replace('/<(strong|b)[^>]*>(.*?)<\/\1>/is', '**$2**', $markdown);
        $markdown = preg_replace('/<(em|i)[^>]*>(.*?)<\/\1>/is', '*$2*', $markdown);

        // Convert code
        $markdown = preg_replace('/<code[^>]*>(.*?)<\/code>/is', '`$1`', $markdown);
        $markdown = preg_replace('/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is', "```\n$1\n```", $markdown);
        $markdown = preg_replace('/<pre[^>]*>(.*?)<\/pre>/is', "```\n$1\n```", $markdown);

        // Convert lists
        $markdown = preg_replace('/<ul[^>]*>(.*?)<\/ul>/is', '$1', $markdown);
        $markdown = preg_replace('/<ol[^>]*>(.*?)<\/ol>/is', '$1', $markdown);
        $markdown = preg_replace('/<li[^>]*>(.*?)<\/li>/is', '- $1' . "\n", $markdown);

        // Convert blockquotes
        $markdown = preg_replace('/<blockquote[^>]*>(.*?)<\/blockquote>/is', '> $1', $markdown);

        // Remove remaining HTML tags
        $markdown = strip_tags($markdown);

        // Clean up whitespace
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);
        $markdown = trim($markdown);

        // Add header with source URL
        $header = "# Converted from: $originalUrl\n\n";
        $header .= "*Converted on: " . date('Y-m-d H:i:s') . "*\n\n---\n\n";

        return $header . $markdown;
    }

    private function saveMarkdownFile(string $markdown): string
    {
        $fileId = uniqid('md_', true);
        $filePath = DOWNLOADS_PATH . '/' . $fileId . '.md';

        if (file_put_contents($filePath, $markdown) === false) {
            throw new \RuntimeException('Failed to save markdown file');
        }

        return $fileId;
    }

    private function markdownToHtml(string $markdown): string
    {
        // Simple Markdown to HTML conversion for preview
        $html = htmlspecialchars($markdown);

        // Convert headers
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^#### (.*$)/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^##### (.*$)/m', '<h5>$1</h5>', $html);
        $html = preg_replace('/^###### (.*$)/m', '<h6>$1</h6>', $html);

        // Convert bold and italic
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);

        // Convert code
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);

        // Convert links
        $html = preg_replace('/\[([^\]]*)\]\(([^)]*)\)/', '<a href="$2" target="_blank">$1</a>', $html);

        // Convert line breaks to paragraphs
        $html = preg_replace('/\n\n/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';

        // Fix empty paragraphs
        $html = preg_replace('/<p><\/p>/', '', $html);

        return $html;
    }
}
