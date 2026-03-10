<?php

/**
 * Project root entry: forward to public/index.php.
 * Run the server with document root = public for clean URLs:
 *   php -S localhost:8000 -t public
 * Or from project root without -t: this file is used for requests to /
 */
require __DIR__ . '/public/index.php';
