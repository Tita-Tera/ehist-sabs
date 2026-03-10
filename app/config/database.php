<?php

declare(strict_types=1);

/**
 * Database connection (PDO singleton).
 */
$config = require __DIR__ . '/config.php';
$db     = $config['db'];

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $db['host'],
    $db['port'],
    $db['database'],
    $db['charset']
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db['username'], $db['password'], $options);
} catch (PDOException $e) {
    if ($config['debug'] ?? true) {
        throw $e;
    }
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

return $pdo;
