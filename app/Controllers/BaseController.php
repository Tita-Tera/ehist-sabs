<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Base controller: JSON response helpers and common setup.
 */
abstract class BaseController
{
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }

    protected function jsonError(string $message, int $status = 400): void
    {
        $this->json(['error' => $message], $status);
    }

    /** Get request body as array (JSON or form-urlencoded). */
    protected function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== '' && $raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return is_array($_POST) ? $_POST : [];
    }

    protected function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /** Parse limit/offset from query string (default limit 50, max 100). */
    protected function getLimitOffset(): array
    {
        $limit  = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $limit  = min(max(1, $limit), 100);
        $offset = max(0, $offset);
        return [$limit, $offset];
    }

    /** Simple validation: email format. */
    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Simple validation: date Y-m-d. */
    protected function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /** Simple validation: time H:i or H:i:s. */
    protected function isValidTime(string $time): bool
    {
        return (bool) preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $time);
    }
}
