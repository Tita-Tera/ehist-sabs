<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Base model with PDO and common CRUD helpers.
 */
abstract class Model
{
    protected static ?PDO $pdo = null;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    protected static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = require dirname(__DIR__) . '/config/database.php';
        }
        return self::$pdo;
    }

    public function find(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy !== '') {
            $sql .= " ORDER BY " . preg_replace('/[^a-z_,\s]/i', '', $orderBy);
        }
        return self::pdo()->query($sql)->fetchAll();
    }

    public function where(string $column, $value): array
    {
        $stmt = self::pdo()->prepare(
            "SELECT * FROM `{$this->table}` WHERE `" . preg_replace('/[^a-z_]/i', '', $column) . "` = ?"
        );
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public function findOneBy(string $column, $value): ?array
    {
        $rows = $this->where($column, $value);
        return $rows[0] ?? null;
    }
}
