<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;

/**
 * Handles login, register, logout (AJAX/API).
 */
class AuthController extends BaseController
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function login(): void
    {
        if ($this->getRequestMethod() !== 'POST') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input = $this->getJsonInput();
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        if ($email === '' || $password === '') {
            $this->jsonError(
                'Email and password required. Send JSON body: {"email":"...","password":"..."} with header Content-Type: application/json'
            );
            return;
        }
        if (!$this->isValidEmail($email)) {
            $this->jsonError('Invalid email format');
            return;
        }
        $user = $this->authService->attempt($email, $password);
        if ($user === null) {
            $this->jsonError('Invalid credentials', 401);
            return;
        }
        $this->authService->login($user);
        $this->json([
            'success' => true,
            'user'    => [
                'id'    => (int) $user['id'],
                'email' => $user['email'],
                'name'  => $user['name'],
                'role_id' => (int) $user['role_id'],
            ],
        ]);
    }

    public function register(): void
    {
        if ($this->getRequestMethod() !== 'POST') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input = $this->getJsonInput();
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $name = trim((string) ($input['name'] ?? ''));
        if ($email === '' || $password === '' || $name === '') {
            $this->jsonError(
                'Email, password and name required. Send JSON body: {"email":"...","password":"...","name":"..."} with header Content-Type: application/json'
            );
            return;
        }
        if (!$this->isValidEmail($email)) {
            $this->jsonError('Invalid email format');
            return;
        }
        if (strlen($password) < 6) {
            $this->jsonError('Password must be at least 6 characters');
            return;
        }
        $result = $this->authService->register($email, $password, $name);
        if (isset($result['error'])) {
            $this->jsonError($result['error']);
            return;
        }
        $this->json(['success' => true, 'user_id' => $result['user_id']]);
    }

    public function logout(): void
    {
        $this->authService->logout();
        $this->json(['success' => true]);
    }

    public function me(): void
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            $this->jsonError('Not authenticated', 401);
            return;
        }
        $this->json([
            'id'      => (int) $user['id'],
            'email'   => $user['email'],
            'name'    => $user['name'],
            'role_id' => (int) $user['role_id'],
        ]);
    }

    public function forgotPassword(): void
    {
        if ($this->getRequestMethod() !== 'POST') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input = $this->getJsonInput();
        $email = trim((string) ($input['email'] ?? ''));
        if ($email === '' || !$this->isValidEmail($email)) {
            $this->jsonError('Valid email required');
            return;
        }
        $result = $this->authService->requestPasswordReset($email);
        if (isset($result['error'])) {
            $this->jsonError($result['error']);
            return;
        }
        $this->json(['success' => true, 'token' => $result['token'] ?? null]);
    }

    public function resetPassword(): void
    {
        if ($this->getRequestMethod() !== 'POST') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input = $this->getJsonInput();
        $token = trim((string) ($input['token'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        if ($token === '' || $password === '') {
            $this->jsonError('Token and password required');
            return;
        }
        if (strlen($password) < 6) {
            $this->jsonError('Password must be at least 6 characters');
            return;
        }
        $result = $this->authService->resetPassword($token, $password);
        if (isset($result['error'])) {
            $this->jsonError($result['error']);
            return;
        }
        $this->json(['success' => true]);
    }
}
