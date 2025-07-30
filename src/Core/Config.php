<?php

namespace Core;

/**
 * Configuration Manager
 * 
 * Handles loading and accessing configuration values from files.
 * Similar to Laravel's config functionality.
 * 
 * @package Core
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class Config
{
    /**
     * Configuration settings
     * 
     * @var array
     */
    private static array $settings = [];

    /**
     * Loaded configuration files
     * 
     * @var array
     */
    private static array $loadedFiles = [];

    /**
     * Load configuration from file
     * 
     * @param string $file Path to configuration file
     * @param string|null $key Optional key to store config under
     * @return void
     * @throws \Exception If file doesn't exist or returns invalid data
     */
    public static function load(string $file, ?string $key = null): void
    {
        if (!file_exists($file)) {
            throw new \Exception("Configuration file not found: {$file}");
        }

        if (in_array($file, self::$loadedFiles)) {
            return; // Already loaded
        }

        $config = require $file;

        if (!is_array($config)) {
            throw new \Exception("Configuration file must return an array: {$file}");
        }

        if ($key) {
            self::$settings[$key] = $config;
        } else {
            // Use filename as key
            $filename = pathinfo($file, PATHINFO_FILENAME);
            self::$settings[$filename] = $config;
        }

        self::$loadedFiles[] = $file;
    }

    /**
     * Load all configuration files from directory
     * 
     * @param string $directory Path to configuration directory
     * @return void
     */
    public static function loadDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/*.php');

        foreach ($files as $file) {
            self::load($file);
        }
    }

    /**
     * Get configuration value using dot notation
     * 
     * @param string $key Configuration key (e.g., 'database.host')
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Configuration value or default
     */
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$settings;

        foreach ($keys as $segment) {
            if (!is_array($value) || !isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set configuration value using dot notation
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$settings;

        foreach ($keys as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }

        $config = $value;
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if key exists, false otherwise
     */
    public static function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = self::$settings;

        foreach ($keys as $segment) {
            if (!is_array($value) || !isset($value[$segment])) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Remove configuration key
     * 
     * @param string $key Configuration key
     * @return void
     */
    public static function forget(string $key): void
    {
        $keys = explode('.', $key);
        $config = &self::$settings;

        $lastKey = array_pop($keys);

        foreach ($keys as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                return;
            }
            $config = &$config[$segment];
        }

        unset($config[$lastKey]);
    }

    /**
     * Get all configuration settings
     * 
     * @return array All configuration settings
     */
    public static function all(): array
    {
        return self::$settings;
    }

    /**
     * Clear all configuration settings
     * 
     * @return void
     */
    public static function clear(): void
    {
        self::$settings = [];
        self::$loadedFiles = [];
    }

    /**
     * Merge configuration array
     * 
     * @param array $config Configuration array to merge
     * @param string|null $key Optional key to merge under
     * @return void
     */
    public static function merge(array $config, ?string $key = null): void
    {
        if ($key) {
            if (!isset(self::$settings[$key])) {
                self::$settings[$key] = [];
            }
            self::$settings[$key] = array_merge(self::$settings[$key], $config);
        } else {
            self::$settings = array_merge(self::$settings, $config);
        }
    }

    /**
     * Get environment-specific configuration
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Configuration value with environment override
     */
    public static function env(string $key, $default = null)
    {
        $envKey = strtoupper(str_replace('.', '_', $key));
        $envValue = Environment::get($envKey);

        if ($envValue !== null) {
            return $envValue;
        }

        return self::get($key, $default);
    }
}