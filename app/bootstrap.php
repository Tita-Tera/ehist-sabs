<?php

declare(strict_types=1);

/**
 * Bootstrap: load env, config, and autoload app classes.
 */
$appRoot = dirname(__DIR__);

// Load .env if file exists (simple parse, no dependency)
$envFile = $appRoot . '/.env';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($name !== '') {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

// Timezone
$config = require __DIR__ . '/config/config.php';
date_default_timezone_set($config['timezone']);

// PSR-4 style autoload for App namespace (app/ folder)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $base   = dirname(__DIR__) . '/app/';
    $len    = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $file     = $base . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
