<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Password reset tokens (password_reset_tokens table).
 */
class PasswordResetToken extends Model
{
    protected string $table = 'password_reset_tokens';

    public function create(string $email, string $token, string $expiresAt): int
    {
        $stmt = self::pdo()->prepare(
            "INSERT INTO `{$this->table}` (email, token, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->execute([$email, $token, $expiresAt]);
        return (int) self::pdo()->lastInsertId();
    }

    public function findValidToken(string $token): ?array
    {
        $stmt = self::pdo()->prepare(
            "SELECT * FROM `{$this->table}` WHERE token = ? AND expires_at > NOW() LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function deleteByEmail(string $email): void
    {
        $stmt = self::pdo()->prepare("DELETE FROM `{$this->table}` WHERE email = ?");
        $stmt->execute([$email]);
    }
}
