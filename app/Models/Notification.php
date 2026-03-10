<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Notification model (notifications table).
 */
class Notification extends Model
{
    protected string $table = 'notifications';

    public function getByUser(int $userId, bool $unreadOnly = false): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE user_id = ?";
        $params = [$userId];
        if ($unreadOnly) {
            $sql .= " AND read_at IS NULL";
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function markRead(int $id, int $userId): bool
    {
        $stmt = self::pdo()->prepare(
            "UPDATE `{$this->table}` SET read_at = NOW() WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$id, $userId]);
    }

    /** @param int[] $ids */
    public function markReadMany(array $ids, int $userId): int
    {
        if ($ids === []) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$userId]);
        $stmt = self::pdo()->prepare(
            "UPDATE `{$this->table}` SET read_at = NOW() WHERE id IN ($placeholders) AND user_id = ?"
        );
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function create(array $data): int
    {
        $stmt = self::pdo()->prepare(
            "INSERT INTO `{$this->table}` (user_id, type, title, body, reference_type, reference_id) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['user_id'],
            $data['type'] ?? 'info',
            $data['title'],
            $data['body'] ?? null,
            $data['reference_type'] ?? null,
            $data['reference_id'] ?? null,
        ]);
        return (int) self::pdo()->lastInsertId();
    }
}
