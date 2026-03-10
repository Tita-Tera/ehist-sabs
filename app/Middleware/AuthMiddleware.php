<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

/**
 * Ensures user is authenticated. Use before protected routes.
 */
class AuthMiddleware
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function __invoke(callable $next): mixed
    {
        if ($this->authService->currentUser() === null) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentication required']);
            return null;
        }
        return $next();
    }
}
