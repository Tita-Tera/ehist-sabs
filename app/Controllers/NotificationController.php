<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\NotificationService;

/**
 * Handles notification list and mark-read (API).
 */
class NotificationController extends BaseController
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    public function index(): void
    {
        $unreadOnly = isset($_GET['unread_only']) && ($_GET['unread_only'] === '1' || strtolower($_GET['unread_only']) === 'true');
        $notifications = $this->notificationService->listForCurrentUser($unreadOnly);
        $this->json(['notifications' => $notifications]);
    }

    public function markRead(int $id): void
    {
        $result = $this->notificationService->markRead($id);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true]);
    }

    public function markReadMany(): void
    {
        if ($this->getRequestMethod() !== 'POST' && $this->getRequestMethod() !== 'PATCH') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input = $this->getJsonInput();
        $ids = $input['ids'] ?? (isset($input['id']) ? [$input['id']] : []);
        if (!is_array($ids)) {
            $ids = [];
        }
        $result = $this->notificationService->markReadMany($ids);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true]);
    }
}
