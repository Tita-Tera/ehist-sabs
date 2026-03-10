<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PasswordResetToken;
use App\Models\User;

/**
 * Authentication: login, register, logout, password hash/verify, password reset.
 */
class AuthService
{
    private const SESSION_USER_KEY = 'user_id';
    private const RESET_TOKEN_EXPIRY_HOURS = 1;

    public function __construct(
        private User $userModel,
        private ?PasswordResetToken $tokenModel = null
    ) {
    }

    public function attempt(string $email, string $password): ?array
    {
        $user = $this->userModel->findByEmail($email);
        if ($user === null) {
            return null;
        }
        if (!password_verify($password, $user['password'])) {
            return null;
        }
        return $user;
    }

    public function login(array $user): void
    {
        $this->ensureSession();
        $_SESSION[self::SESSION_USER_KEY] = (int) $user['id'];
    }

    public function logout(): void
    {
        $this->ensureSession();
        unset($_SESSION[self::SESSION_USER_KEY]);
    }

    public function currentUser(): ?array
    {
        $this->ensureSession();
        $id = $_SESSION[self::SESSION_USER_KEY] ?? null;
        if ($id === null) {
            return null;
        }
        return $this->userModel->find((int) $id);
    }

    /**
     * Register a new user. Role must be ROLE_CUSTOMER or ROLE_PROVIDER (admin cannot self-register).
     */
    public function register(string $email, string $password, string $name, int $roleId = User::ROLE_CUSTOMER): array
    {
        $allowedRoles = [User::ROLE_CUSTOMER, User::ROLE_PROVIDER];
        if (!in_array($roleId, $allowedRoles, true)) {
            return ['error' => 'Invalid role. Choose Customer or Provider.'];
        }
        if ($this->userModel->findByEmail($email) !== null) {
            return ['error' => 'Email already registered'];
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            return ['error' => 'Password hashing failed'];
        }
        try {
            $userId = $this->userModel->create([
                'email'    => $email,
                'password' => $hash,
                'name'     => $name,
                'role_id'  => $roleId,
            ]);
        } catch (\PDOException $e) {
            $code = $e->getCode();
            if ($code === '23000' || strpos((string) $e->getMessage(), 'Duplicate') !== false) {
                return ['error' => 'Email already registered'];
            }
            if ($code === '23000' && strpos((string) $e->getMessage(), 'foreign key') !== false) {
                return ['error' => 'Registration failed. Please ensure the database is set up (run schema and seed).'];
            }
            throw $e;
        }
        return ['user_id' => $userId];
    }

    /** Request password reset: creates token, returns token (for testing; in production send by email). */
    public function requestPasswordReset(string $email): array
    {
        if ($this->tokenModel === null) {
            return ['error' => 'Password reset not configured'];
        }
        $user = $this->userModel->findByEmail($email);
        if ($user === null) {
            return ['error' => 'No account with that email'];
        }
        $this->tokenModel->deleteByEmail($email);
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::RESET_TOKEN_EXPIRY_HOURS * 3600);
        $this->tokenModel->create($email, $token, $expiresAt);
        return ['success' => true, 'token' => $token];
    }

    /** Reset password using token. */
    public function resetPassword(string $token, string $newPassword): array
    {
        if ($this->tokenModel === null) {
            return ['error' => 'Password reset not configured'];
        }
        $row = $this->tokenModel->findValidToken($token);
        if ($row === null) {
            return ['error' => 'Invalid or expired token'];
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hash === false) {
            return ['error' => 'Password hashing failed'];
        }
        $stmt = \App\Models\Model::pdo()->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hash, $row['email']]);
        $stmt = \App\Models\Model::pdo()->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
        $stmt->execute([$token]);
        return ['success' => true];
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require dirname(__DIR__) . '/config/config.php';
            session_name($config['session']['name'] ?? 'ehist_sabs_session');
            session_start();
        }
    }
}
