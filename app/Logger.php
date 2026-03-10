<?php

declare(strict_types=1);

namespace App;

/**
 * Simple file logger.
 */
class Logger
{
    private static ?string $path = null;

    public static function setPath(string $path): void
    {
        self::$path = $path;
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $path = self::$path ?? dirname(__DIR__) . '/storage/logs/app.log';
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = date('Y-m-d H:i:s') . " [{$level}] " . $message;
        if ($context !== []) {
            $line .= ' ' . json_encode($context);
        }
        @file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
