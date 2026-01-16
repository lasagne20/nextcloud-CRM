<?php

declare(strict_types=1);

// PHPUnit bootstrap file

// Set up test environment
$_ENV['PHPUNIT_CONFIG'] = '1';

// Determine Nextcloud root
$ncRoot = __DIR__ . '/../../../..';
if (!is_dir($ncRoot . '/lib')) {
    $ncRoot = '/var/www/html';
}

if (!defined('OC_ROOT')) {
    define('OC_ROOT', $ncRoot);
}

// Bootstrap Nextcloud if available
if (file_exists($ncRoot . '/lib/base.php')) {
    // Running in actual Nextcloud environment
    require_once $ncRoot . '/lib/base.php';
    
    // Initialize Nextcloud
    if (class_exists('OC')) {
        try {
            \OC::$loader->addValidRoot($ncRoot);
        } catch (\Throwable $e) {
            // Loader might already be initialized
        }
    }
}

// Autoload our app's classes
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
