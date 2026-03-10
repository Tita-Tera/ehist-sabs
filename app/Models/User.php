<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User model (users table).
 */
class User extends Model
{
    protected string $table = 'users';

    public const ROLE_ADMIN = 1;
    public const ROLE_PROVIDER = 2;
    public const ROLE_CUSTOMER = 3;

    public function findByEmail(string $email): ?array
    {
        $stmt = self::pdo()->prepare(
            "SELECT * FROM `{$this->table}` WHERE `email` = ? AND (`deleted_at` IS NULL) LIMIT 1"
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = self::pdo()->prepare(
            "INSERT INTO `{$this->table}` (`role_id`, `email`, `password`, `name`) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['role_id'] ?? self::ROLE_CUSTOMER,
            $data['email'],
            $data['password'],
            $data['name'],
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /** Count users (non-deleted by default). */
    public function getCount(bool $includeDeleted = false): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if (!$includeDeleted) {
            $sql .= " WHERE (deleted_at IS NULL)";
        }
        return (int) self::pdo()->query($sql)->fetchColumn();
    }

    public function getProviders(): array
    {
        $stmt = self::pdo()->prepare(
            "SELECT u.* FROM `{$this->table}` u WHERE u.role_id = ? AND (u.deleted_at IS NULL) ORDER BY u.name"
        );
        $stmt->execute([self::ROLE_PROVIDER]);
        return $stmt->fetchAll();
    }

    /**
     * Get one user by id for admin (no password). Returns null if not found.
     */
    public function getByIdForAdmin(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            "SELECT u.id, u.role_id, u.email, u.name, u.deleted_at, u.created_at, u.updated_at, r.name AS role_name
             FROM `{$this->table}` u
             JOIN `roles` r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * List all users for admin (with role name). Optionally include soft-deleted.
     */
    public function getAllForAdmin(bool $includeDeleted = false): array
    {
        $sql = "SELECT u.id, u.role_id, u.email, u.name, u.deleted_at, u.created_at, u.updated_at, r.name AS role_name
                FROM `{$this->table}` u
                JOIN `roles` r ON r.id = u.role_id";
        if (!$includeDeleted) {
            $sql .= " WHERE (u.deleted_at IS NULL)";
        }
        $sql .= " ORDER BY u.created_at DESC";
        return self::pdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update user fields allowed by admin: name, role_id, and optional soft delete (deleted_at).
     * For restore, pass deleted_at: null. For soft delete, pass deleted_at: true (sets NOW()).
     */
    public function updateByAdmin(int $id, array $data): bool
    {
        $allowed = [];
        $params = [];
        if (array_key_exists('name', $data) && is_string($data['name'])) {
            $allowed[] = '`name` = ?';
            $params[] = trim($data['name']);
        }
        if (array_key_exists('role_id', $data) && is_numeric($data['role_id'])) {
            $roleId = (int) $data['role_id'];
            if (in_array($roleId, [self::ROLE_ADMIN, self::ROLE_PROVIDER, self::ROLE_CUSTOMER], true)) {
                $allowed[] = '`role_id` = ?';
                $params[] = $roleId;
            }
        }
        if (array_key_exists('deleted_at', $data)) {
            $allowed[] = '`deleted_at` = ?';
            $params[] = ($data['deleted_at'] === null || $data['deleted_at'] === false || $data['deleted_at'] === '') ? null : date('Y-m-d H:i:s');
        }
        if ($allowed === []) {
            return false;
        }
        $params[] = $id;
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $allowed) . " WHERE `id` = ?";
        $stmt = self::pdo()->prepare($sql);
        return $stmt->execute($params);
    }
}
