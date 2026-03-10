<?php

declare(strict_types=1);

/**
 * Application configuration.
 * Load environment from .env when available.
 */
return [
    'env'         => $_ENV['APP_ENV'] ?? 'development',
    'debug'       => filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    'timezone'    => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'base_path'   => dirname(__DIR__, 2),
    'app_path'    => dirname(__DIR__),
    'public_path' => dirname(__DIR__, 2) . '/public',

    'db' => [
        'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_DATABASE'] ?? 'ehist_sabs',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    ],

    'session' => [
        'name'   => $_ENV['SESSION_NAME'] ?? 'ehist_sabs_session',
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    ],
];
