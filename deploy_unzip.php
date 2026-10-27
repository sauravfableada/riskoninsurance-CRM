<?php
/**
 * Automated Deployment Extractor
 */

// Secret key to prevent unauthorized access
$secret_key = 'riskon_deploy_secret_2026';

if (!isset($_GET['token']) || $_GET['token'] !== $secret_key) {
    http_response_code(403);
    die('Unauthorized');
}

$zip_file = __DIR__ . '/deploy.zip';

if (!file_exists($zip_file)) {
    http_response_code(404);
    die('deploy.zip not found');
}

$zip = new ZipArchive();
if ($zip->open($zip_file) === true) {
    $zip->extractTo(__DIR__);
    $zip->close();
    
    // Remove the zip file after successful extraction
    @unlink($zip_file);
    
    // Remove this extraction script
    @unlink(__FILE__);
    
    echo "SUCCESS: Extracted and cleaned up.";
} else {
    http_response_code(500);
    echo "ERROR: Failed to open zip file.";
}
