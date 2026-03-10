<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Service model (services table).
 */
class Service extends Model
{
    protected string $table = 'services';

    public function getByProvider(int $providerId): array
    {
        return $this->where('provider_id', $providerId);
    }

    /** Find a service by id only if it belongs to the given provider. */
    public function findByProviderAndId(int $providerId, int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            "SELECT * FROM `{$this->table}` WHERE `id` = ? AND `provider_id` = ? LIMIT 1"
        );
        $stmt->execute([$id, $providerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = self::pdo()->prepare(
            "INSERT INTO `{$this->table}` (provider_id, name, description, duration_min) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['provider_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['duration_min'] ?? 60,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    public function update(int $id, int $providerId, array $data): bool
    {
        $stmt = self::pdo()->prepare(
            "UPDATE `{$this->table}` SET name = ?, description = ?, duration_min = ? WHERE id = ? AND provider_id = ?"
        );
        $stmt->execute([
            $data['name'] ?? '',
            $data['description'] ?? null,
            (int) ($data['duration_min'] ?? 60),
            $id,
            $providerId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $providerId): bool
    {
        $stmt = self::pdo()->prepare(
            "DELETE FROM `{$this->table}` WHERE id = ? AND provider_id = ?"
        );
        $stmt->execute([$id, $providerId]);
        return $stmt->rowCount() > 0;
    }
}
