<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Time slot model (provider availability).
 */
class TimeSlot extends Model
{
    protected string $table = 'time_slots';

    public function getAvailableByProviderAndDate(int $providerId, string $date): array
    {
        $stmt = self::pdo()->prepare(
            "SELECT * FROM `{$this->table}` WHERE provider_id = ? AND slot_date = ? AND is_available = 1 ORDER BY start_time"
        );
        $stmt->execute([$providerId, $date]);
        return $stmt->fetchAll();
    }

    /** True if provider has an available slot matching this exact date and time range. */
    public function existsAvailableSlot(int $providerId, string $slotDate, string $startTime, string $endTime): bool
    {
        $startTime = $this->normalizeTime($startTime);
        $endTime   = $this->normalizeTime($endTime);
        $stmt = self::pdo()->prepare(
            "SELECT 1 FROM `{$this->table}` WHERE provider_id = ? AND slot_date = ? AND is_available = 1 AND start_time = ? AND end_time = ? LIMIT 1"
        );
        $stmt->execute([$providerId, $slotDate, $startTime, $endTime]);
        return (bool) $stmt->fetch();
    }

    private function normalizeTime(string $time): string
    {
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        return $time;
    }

    /** All slots for a provider, optionally filtered by date range. */
    public function getByProvider(int $providerId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE provider_id = ?";
        $params = [$providerId];
        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= " AND slot_date >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= " AND slot_date <= ?";
            $params[] = $dateTo;
        }
        $sql .= " ORDER BY slot_date, start_time";
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = self::pdo()->prepare(
            "INSERT INTO `{$this->table}` (provider_id, slot_date, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['provider_id'],
            $data['slot_date'],
            $data['start_time'],
            $data['end_time'],
            $data['is_available'] ?? 1,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $updates = [];
        $params = [];
        $allowed = ['slot_date', 'start_time', 'end_time', 'is_available'];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $updates[] = "`$col` = ?";
                $params[] = $data[$col];
            }
        }
        if ($updates === []) {
            return true;
        }
        $params[] = $id;
        $stmt = self::pdo()->prepare(
            "UPDATE `{$this->table}` SET " . implode(', ', $updates) . " WHERE id = ?"
        );
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = self::pdo()->prepare("DELETE FROM `{$this->table}` WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Check for overlapping time slots (same provider, same date). */
    public function hasOverlap(int $providerId, string $slotDate, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $sql = "SELECT 1 FROM `{$this->table}` WHERE provider_id = ? AND slot_date = ?
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
