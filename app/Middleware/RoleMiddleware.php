<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

/**
 * Restricts access by role (e.g. admin-only or provider-only routes).
 */
class RoleMiddleware
{
    /** @var int[] Allowed role IDs (User::ROLE_*) */
    private array $allowedRoles;

    public function __construct(
        private AuthService $authService,
        int ...$allowedRoles
    ) {
        $this->allowedRoles = $allowedRoles;
    }

    public function __invoke(callable $next): mixed
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentication required']);
            return null;
        }
        $roleId = (int) $user['role_id'];
        if (!in_array($roleId, $this->allowedRoles, true)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Insufficient permissions']);
            return null;
        }
        return $next();
    }
}
