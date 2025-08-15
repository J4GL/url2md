<?php

namespace Controllers;

/**
 * Home Controller
 * Handles the main page display
 */
class HomeController
{
    public function index(): void
    {
        $pageTitle = APP_NAME;
        $pageDescription = 'Convert any URL to a clean Markdown file with live preview';
        
        // Clear any existing progress
        unset($_SESSION['conversion_progress']);
        
        include APP_PATH . '/views/home.php';
    }
}
