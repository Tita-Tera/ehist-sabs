<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Booking model (bookings table).
 */
class Booking extends Model
{
    protected string $table = 'bookings';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public function getByCustomer(int $customerId, int $limit = 100, int $offset = 0): array
    {
        $stmt = self::pdo()->prepare(
            "SELECT * FROM `{$this->table}` WHERE customer_id = ? ORDER BY slot_date DESC, start_time DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function getByProvider(int $providerId, int $limit = 100, int $offset = 0): array
    {
        $stmt = self::pdo()->prepare(
            "SELECT * FROM `{$this->table}` WHERE provider_id = ? ORDER BY slot_date DESC, start_time DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset
        );
        $stmt->execute([$providerId]);
        return $stmt->fetchAll();
    }

    public function getAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = self::pdo()->prepare(
            "SELECT * FROM `{$this->table}` ORDER BY slot_date DESC, start_time DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Count bookings grouped by status (for admin overview). */
    public function getCountByStatus(): array
    {
        $stmt = self::pdo()->query(
            "SELECT status, COUNT(*) AS count FROM `{$this->table}` GROUP BY status"
        );
        $out = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $out[$row['status']] = (int) $row['count'];
        }
        return $out;
    }

    public function create(array $data): int
    {
        $stmt = self::pdo()->prepare(
            "INSERT INTO `{$this->table}` (customer_id, provider_id, service_id, slot_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['customer_id'],
            $data['provider_id'],
            $data['service_id'],
            $data['slot_date'],
            $data['start_time'],
            $data['end_time'],
            $data['status'] ?? self::STATUS_PENDING,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = self::pdo()->prepare("UPDATE `{$this->table}` SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function updateTime(int $id, string $slotDate, string $startTime, string $endTime): bool
    {
        $stmt = self::pdo()->prepare("UPDATE `{$this->table}` SET slot_date = ?, start_time = ?, end_time = ? WHERE id = ?");
        return $stmt->execute([$slotDate, $startTime, $endTime, $id]);
    }

    /** Check for overlapping booking (same provider, same date, overlapping time). */
    public function hasOverlap(int $providerId, string $slotDate, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $sql = "SELECT 1 FROM `{$this->table}` WHERE provider_id = ? AND slot_date = ? AND status NOT IN ('cancelled', 'rejected')
                AND ( (start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?) )";
        $params = [$providerId, $slotDate, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $sql .= " LIMIT 1";
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }
}
