<?php
/**
 * URL to Markdown Converter
 * Main entry point for the application
 */

declare(strict_types=1);

// Start session for progress tracking
session_start();

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define application constants
define('APP_ROOT', __DIR__);
define('APP_PATH', APP_ROOT . '/app');
define('PUBLIC_PATH', APP_ROOT . '/public');
define('CONFIG_PATH', APP_ROOT . '/config');

// Include configuration
require_once CONFIG_PATH . '/config.php';

// Simple autoloader
spl_autoload_register(function ($class) {
    $file = APP_PATH . '/' . strtolower(str_replace('\\', '/', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Simple router
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$uri = parse_url($request_uri, PHP_URL_PATH);

// Route handling
switch ($uri) {
    case '/':
    case '/index.php':
        $controller = new Controllers\HomeController();
        $controller->index();
        break;
        
    case '/convert':
        if ($request_method === 'POST') {
            $controller = new Controllers\ConvertController();
            $controller->convert();
        } else {
            header('Location: /');
            exit;
        }
        break;
        
    case '/progress':
        $controller = new Controllers\ConvertController();
        $controller->getProgress();
        break;
        
    case '/download':
        if (isset($_GET['file'])) {
            $controller = new Controllers\ConvertController();
            $controller->download($_GET['file']);
        } else {
            header('Location: /');
            exit;
        }
        break;
        
    default:
        // Serve static files
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico)$/', $uri)) {
            $file_path = PUBLIC_PATH . $uri;
            if (file_exists($file_path)) {
                $mime_types = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'ico' => 'image/x-icon'
                ];
                
                $extension = pathinfo($file_path, PATHINFO_EXTENSION);
                $mime_type = $mime_types[$extension] ?? 'application/octet-stream';
                
                header('Content-Type: ' . $mime_type);
                readfile($file_path);
                exit;
            }
        }
        
        // 404 Not Found
        http_response_code(404);
        echo '404 - Page Not Found';
        break;
}
