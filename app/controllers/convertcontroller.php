<?php

namespace Controllers;

use Models\UrlConverter;

/**
 * Convert Controller
 * Handles URL conversion requests and progress tracking
 */
class ConvertController
{
    private UrlConverter $converter;
    
    public function __construct()
    {
        $this->converter = new UrlConverter();
    }
    
    public function convert(): void
    {
        header('Content-Type: application/json');
        
        try {
            $url = $_POST['url'] ?? '';
            
            if (empty($url)) {
                throw new \InvalidArgumentException('URL is required');
            }
            
            // Initialize progress tracking
            $_SESSION['conversion_progress'] = [
                'current_step' => 'validate_url',
                'progress' => 0,
                'steps' => [],
                'error' => null,
                'file_id' => null
            ];
            
            // Start conversion process
            $result = $this->converter->convertUrl($url);
            
            echo json_encode([
                'success' => true,
                'file_id' => $result['file_id'],
                'filename' => $result['filename'],
                'markdown' => $result['markdown'],
                'html' => $result['html']
            ]);
            
        } catch (\Exception $e) {
            // Log error
            error_log("Conversion error: " . $e->getMessage());
            
            // Update progress with error
            if (isset($_SESSION['conversion_progress'])) {
                $_SESSION['conversion_progress']['error'] = $e->getMessage();
            }
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getProgress(): void
    {
        header('Content-Type: application/json');
        
        $progress = $_SESSION['conversion_progress'] ?? [
            'current_step' => null,
            'progress' => 0,
            'steps' => [],
            'error' => null,
            'file_id' => null
        ];
        
        echo json_encode($progress);
    }
    
    public function download(string $fileId): void
    {
        try {
            $filePath = DOWNLOADS_PATH . '/' . $fileId . '.md';
            
            if (!file_exists($filePath)) {
                throw new \RuntimeException('File not found');
            }
            
            $filename = 'converted_' . date('Y-m-d_H-i-s') . '.md';
            
            header('Content-Type: text/markdown');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filePath));
            
            readfile($filePath);
            
        } catch (\Exception $e) {
            http_response_code(404);
            echo 'File not found';
        }
    }
}
