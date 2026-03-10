<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Booking;
use App\Models\User;

/**
 * Admin-only: list and manage users (role, name, soft delete), system overview.
 */
class AdminController extends BaseController
{
    public function __construct(
        private User $userModel,
        private Booking $bookingModel
    ) {
    }

    /**
     * GET /admin/overview — counts for dashboard (users, bookings by status).
     */
    public function overview(): void
    {
        $userCount = $this->userModel->getCount();
        $bookingCounts = $this->bookingModel->getCountByStatus();
        $this->json([
            'users_total' => $userCount,
            'bookings'    => $bookingCounts,
        ]);
    }

    /**
     * GET /admin/users — list all users (optional ?deleted=1 to include soft-deleted).
     */
    public function index(): void
    {
        $includeDeleted = isset($_GET['deleted']) && (string) $_GET['deleted'] === '1';
        $users = $this->userModel->getAllForAdmin($includeDeleted);
        $this->json(['users' => array_map([$this, 'formatUser'], $users)]);
    }

    /**
     * PATCH /admin/users/:id — update user (name, role_id, optional soft delete).
     * Body: { "name"?: string, "role_id"?: 1|2|3, "deleted_at"?: null|true }
     * - deleted_at: null or omit to restore; true to soft-delete.
     */
    public function update(int $id): void
    {
        $user = $this->userModel->getByIdForAdmin($id);
        if ($user === null) {
            $this->jsonError('User not found', 404);
            return;
        }

        $input = $this->getJsonInput();
        $data = [];

        if (array_key_exists('name', $input)) {
            $data['name'] = is_string($input['name']) ? trim($input['name']) : $user['name'];
        }
        if (array_key_exists('role_id', $input)) {
            $data['role_id'] = $input['role_id'];
        }
        if (array_key_exists('deleted_at', $input)) {
            $data['deleted_at'] = $input['deleted_at'];
        }

        if ($data === []) {
            $this->json(['user' => $this->formatUser($user)]);
            return;
        }

        if (!$this->userModel->updateByAdmin($id, $data)) {
            $this->jsonError('No valid fields to update', 400);
            return;
        }

        $updated = $this->userModel->getByIdForAdmin($id);
        $this->json(['user' => $this->formatUser($updated ?: $user)]);
    }

    private function formatUser(array $row): array
    {
        return [
            'id'         => (int) $row['id'],
            'role_id'    => (int) $row['role_id'],
            'role_name'  => $row['role_name'] ?? null,
            'email'      => $row['email'],
            'name'       => $row['name'],
            'deleted_at' => isset($row['deleted_at']) && $row['deleted_at'] !== null ? $row['deleted_at'] : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
