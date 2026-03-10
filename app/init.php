<?php

declare(strict_types=1);

/**
 * App initialization: bootstrap + database.
 * Include this from public entry points (e.g. index.php, api.php).
 */
require_once __DIR__ . '/bootstrap.php';

// Database connection (available as $pdo when this file is required)
$pdo = require __DIR__ . '/config/database.php';

// Make PDO available to all models
\App\Models\Model::setPdo($pdo);

// Session is started on first use in AuthService::ensureSession() so that
// register (and health) work even when session directory is not writable.
