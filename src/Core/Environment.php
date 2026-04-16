<?php

namespace Core;

/**
 * Environment Configuration Loader
 * 
 * Handles loading and parsing of .env files for environment-specific configuration.
 * Similar to Laravel's DotEnv functionality.
 * 
 * @package Core
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class Environment
{
    /**
     * Loaded environment variables
     * 
     * @var array
     */
    private static array $variables = [];

    /**
     * Load environment variables from .env file
     * 
     * @param string $path Path to the .env file
     * @return void
     * @throws \Exception If .env file cannot be loaded
     */
    public static function load(string $path): void
    {
        // Load base .env first
        self::loadFile($path . '/.env');

        // Load .env.local if it exists — values here OVERRIDE .env
        // This is the standard convention for local developer overrides
        self::loadFile($path . '/.env.local');
    }

    /**
     * Load environment variables from a specific file path.
     *
     * @param string $envFile Absolute path to the .env file
     * @return void
     */
    private static function loadFile(string $envFile): void
    {
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = self::parseValue(trim($value));

                // Set in $_ENV and $_SERVER
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");

                self::$variables[$key] = $value;
            }
        }
    }


    /**
     * Get environment variable value
     * 
     * @param string $key The environment variable key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The environment variable value or default
     */
    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? self::$variables[$key] ?? $default;
    }

    /**
     * Parse environment variable value
     * 
     * @param string $value Raw value from .env file
     * @return mixed Parsed value (string, bool, null)
     */
    private static function parseValue(string $value)
    {
        // Remove quotes if present
        if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
            $value = $matches[2];
        }

        // Parse boolean and null values
        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
            case '':
                return '';
            default:
                return $value;
        }
    }

    /**
     * Check if environment variable exists
     * 
     * @param string $key The environment variable key
     * @return bool True if exists, false otherwise
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || isset($_SERVER[$key]) || isset(self::$variables[$key]);
    }

    /**
     * Get all environment variables
     * 
     * @return array All loaded environment variables
     */
    public static function all(): array
    {
        return array_merge($_ENV, self::$variables);
    }
}
