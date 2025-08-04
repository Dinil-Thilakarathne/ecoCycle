<?php

namespace HotReloader;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Improved DiffChecker with better error handling and reliability
 */
class ImprovedDiffChecker
{

    private $ROOT;
    private $WATCH;
    private $IGNORE;

    /**
     * Constructor
     */
    function __construct($options = [])
    {
        $this->ROOT = $this->addSlash($options["ROOT"] ?? '');
        $this->WATCH = $options["WATCH"] ?? [];
        $this->IGNORE = $options["IGNORE"] ?? [];
    }

    /**
     * Fixed addSlash method
     */
    private function addSlash($str)
    {
        if (empty($str))
            return './';
        return rtrim($str, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Improved file hashing with error handling
     */
    private function hashAppFiles()
    {
        $hashes = [];

        if (empty($this->WATCH)) {
            return $hashes;
        }

        $git_mode = (is_string($this->WATCH) && strpos($this->WATCH, 'git:') === 0);

        if ($git_mode) {
            $this->ROOT = substr($this->WATCH, 4);
            $git_files = $this->getGitFiles($this->ROOT);
            if ($git_files === false) {
                error_log("HotReloader: Git command failed, falling back to regular watch");
                return $hashes;
            }
            $this->WATCH = $git_files;
        }

        foreach ($this->WATCH as $watch_path) {
            $full_path = $this->ROOT . $watch_path;

            try {
                if (is_dir($full_path)) {
                    if (!$this->willBeIgnored($full_path)) {
                        $dir_hash = $this->hashDir($full_path);
                        if ($dir_hash !== false) {
                            $hashes[] = $dir_hash;
                        }
                    }
                } else {
                    if (file_exists($full_path) && !$this->willBeIgnored($full_path)) {
                        $file_hash = $this->safeHashFile($full_path);
                        if ($file_hash !== false) {
                            $hashes[] = $file_hash;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("HotReloader: Error processing {$full_path}: " . $e->getMessage());
                continue;
            }
        }

        return $hashes;
    }

    /**
     * Safe file hashing with error handling
     */
    private function safeHashFile($file_path)
    {
        try {
            if (!is_readable($file_path)) {
                return false;
            }

            $hash = md5_file($file_path);
            return $hash !== false ? $hash : false;
        } catch (Exception $e) {
            error_log("HotReloader: Cannot hash file {$file_path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Improved git files detection
     */
    private function getGitFiles($repo_path)
    {
        try {
            if (!is_dir($repo_path . '/.git')) {
                return false; // Not a git repository
            }

            $command = sprintf(
                'cd %s && git ls-files -m -o --exclude-standard 2>/dev/null',
                escapeshellarg($repo_path)
            );

            $output = shell_exec($command);

            if ($output === null || $output === false) {
                return false;
            }

            $files = array_filter(explode(PHP_EOL, trim($output)), function ($file) {
                return !empty(trim($file));
            });

            return $files;
        } catch (Exception $e) {
            error_log("HotReloader: Git command failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Improved directory hashing
     */
    private function hashDir($directory)
    {
        if (!is_dir($directory) || !is_readable($directory)) {
            return false;
        }

        try {
            $files = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && !$this->willBeIgnored($file->getPathname())) {
                    $hash = $this->safeHashFile($file->getPathname());
                    if ($hash !== false) {
                        $files[] = $hash;
                    }
                }
            }

            return empty($files) ? false : md5(implode('', $files));
        } catch (Exception $e) {
            error_log("HotReloader: Error hashing directory {$directory}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Improved ignore checking
     */
    private function willBeIgnored($file_path)
    {
        if (empty($this->IGNORE)) {
            return false;
        }

        $relative_path = str_replace($this->ROOT, '', $file_path);

        foreach ($this->IGNORE as $ignore_pattern) {
            $ignore_path = $this->ROOT . $ignore_pattern;

            // Direct match
            if ($file_path === $ignore_path) {
                return true;
            }

            // Directory match
            if (strpos($file_path, $ignore_path) === 0) {
                return true;
            }

            // Pattern matching (simple wildcard support)
            if (strpos($ignore_pattern, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($ignore_pattern, '/'));
                if (preg_match('/^' . $pattern . '/', $relative_path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Improved app state hash generation
     */
    private function getAppStateHash()
    {
        $hashes = $this->hashAppFiles();

        // Remove false values but keep valid hashes
        $valid_hashes = array_filter($hashes, function ($hash) {
            return $hash !== false && !empty($hash);
        });

        if (empty($valid_hashes)) {
            // Return a default hash if no files are being watched
            return md5('no-files-watched-' . time());
        }

        // Sort for consistent hashing
        sort($valid_hashes);

        return md5(implode('|', $valid_hashes));
    }

    /**
     * Public API
     */
    public function hash()
    {
        return $this->getAppStateHash();
    }

    /**
     * Debug method to see what's being watched
     */
    public function getWatchedFiles()
    {
        return [
            'root' => $this->ROOT,
            'watch' => $this->WATCH,
            'ignore' => $this->IGNORE,
            'hash_count' => count($this->hashAppFiles())
        ];
    }
}
?>