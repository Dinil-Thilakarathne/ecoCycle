<?php
$url = 'https://getcomposer.org/download/latest-stable/composer.phar';
$output = __DIR__ . '/composer.phar';

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

echo "Downloading Composer from: $url\n";

$phar = @file_get_contents($url, false, $context);

if ($phar === false) {
    $errors = error_get_last();
    echo "Failed to download composer.phar\n";
    echo "Error: " . print_r($errors, true) . "\n";
    exit(1);
}

$bytes = file_put_contents($output, $phar);
if ($bytes === false) {
    echo "Failed to write composer.phar to disk\n";
    exit(1);
}

echo "Successfully downloaded Composer PHAR file\n";
echo "File size: " . strlen($phar) . " bytes\n";
echo "Saved to: $output\n";

// Test if it's a valid PHAR file
if (file_exists($output)) {
    $size = filesize($output);
    echo "File verified on disk: $size bytes\n";
    
    // Check if executable
    $result = exec("php \"$output\" --version", $output_arr, $code);
    if ($code === 0) {
        echo "Composer version check: " . implode("\n", $output_arr) . "\n";
    } else {
        echo "Composer verification returned code: $code\n";
    }
}
