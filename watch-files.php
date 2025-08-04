#!/usr/bin/env php
<?php
/**
 * File Watcher for Live Reload
 * Run this script in the terminal: php watch-files.php
 */

echo "🔄 EcoCycle File Watcher Started\n";
echo "Watching for changes in CSS and JS files...\n";
echo "Press Ctrl+C to stop\n\n";

// Directories to watch
$watchDirs = [
    __DIR__ . '/public/css',
    __DIR__ . '/public/js',
    __DIR__ . '/src/Views'
];

// File extensions to watch
$watchExtensions = ['css', 'js', 'php'];

// Store file modification times
$fileStates = [];

/**
 * Get all files in directories with watched extensions
 */
function getWatchedFiles($dirs, $extensions)
{
    $files = [];

    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $ext = strtolower($file->getExtension());
                    if (in_array($ext, $extensions)) {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }
    }

    return $files;
}

/**
 * Check for file changes
 */
function checkForChanges($files, &$fileStates)
{
    $changes = [];

    foreach ($files as $file) {
        $currentMtime = filemtime($file);

        if (isset($fileStates[$file])) {
            if ($fileStates[$file] !== $currentMtime) {
                $changes[] = [
                    'file' => $file,
                    'type' => pathinfo($file, PATHINFO_EXTENSION),
                    'time' => date('H:i:s')
                ];
            }
        }

        $fileStates[$file] = $currentMtime;
    }

    return $changes;
}

/**
 * Write reload signal file
 */
function writeReloadSignal($changes)
{
    $signalFile = __DIR__ . '/public/.reload-signal';
    $data = [
        'timestamp' => time(),
        'changes' => $changes
    ];

    file_put_contents($signalFile, json_encode($data));
}

// Initialize file states
$watchedFiles = getWatchedFiles($watchDirs, $watchExtensions);
foreach ($watchedFiles as $file) {
    $fileStates[$file] = filemtime($file);
}

echo "Watching " . count($watchedFiles) . " files...\n\n";

// Main watch loop
while (true) {
    $watchedFiles = getWatchedFiles($watchDirs, $watchExtensions);
    $changes = checkForChanges($watchedFiles, $fileStates);

    if (!empty($changes)) {
        foreach ($changes as $change) {
            $fileName = basename($change['file']);
            $fileType = strtoupper($change['type']);
            echo "[{$change['time']}] 📝 {$fileType} changed: {$fileName}\n";
        }

        writeReloadSignal($changes);
        echo "🔄 Reload signal sent\n\n";
    }

    sleep(1); // Check every second
}
?>