<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;

/**
 * Notification business logic: list for current user, mark read.
 */
class NotificationService
{
    public function __construct(
        private Notification $notificationModel,
        private AuthService $authService
    ) {
    }

    public function listForCurrentUser(bool $unreadOnly = false): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return [];
        }
        return $this->notificationModel->getByUser((int) $user['id'], $unreadOnly);
    }

    /** @return array{error?: string, code?: int} */
    public function markRead(int $id): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        $ok = $this->notificationModel->markRead($id, (int) $user['id']);
        if (!$ok) {
            return ['error' => 'Notification not found or already read', 'code' => 404];
        }
        return [];
    }

    /**
     * @param int[] $ids
     * @return array{error?: string, code?: int}
     */
    public function markReadMany(array $ids): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        $ids = array_map('intval', array_filter($ids, fn($v) => is_numeric($v) && (int) $v > 0));
        $this->notificationModel->markReadMany($ids, (int) $user['id']);
        return [];
    }
}
