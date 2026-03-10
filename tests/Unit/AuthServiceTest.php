<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    public function test_attempt_returns_null_when_user_not_found(): void
    {
        $userModel = $this->createMock(User::class);
        $userModel->method('findByEmail')->willReturn(null);

        $auth = new AuthService($userModel);
        $result = $auth->attempt('unknown@example.com', 'any');

        $this->assertNull($result);
    }

    public function test_attempt_returns_null_when_password_wrong(): void
    {
        $userModel = $this->createMock(User::class);
        $userModel->method('findByEmail')->willReturn([
            'id'       => 1,
            'email'    => 'u@example.com',
            'password' => password_hash('correct', PASSWORD_DEFAULT),
            'name'     => 'User',
            'role_id'  => User::ROLE_CUSTOMER,
        ]);

        $auth = new AuthService($userModel);
        $result = $auth->attempt('u@example.com', 'wrong');

        $this->assertNull($result);
    }

    public function test_attempt_returns_user_when_password_correct(): void
    {
        $user = [
            'id'       => 1,
            'email'    => 'u@example.com',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'name'     => 'User',
            'role_id'  => User::ROLE_CUSTOMER,
        ];
        $userModel = $this->createMock(User::class);
        $userModel->method('findByEmail')->willReturn($user);

        $auth = new AuthService($userModel);
        $result = $auth->attempt('u@example.com', 'secret123');

        $this->assertNotNull($result);
        $this->assertSame(1, (int) $result['id']);
        $this->assertSame('u@example.com', $result['email']);
    }

    public function test_register_returns_error_when_email_already_registered(): void
    {
        $userModel = $this->createMock(User::class);
        $userModel->method('findByEmail')->willReturn(['id' => 1, 'email' => 'x@example.com']);

        $auth = new AuthService($userModel);
        $result = $auth->register('x@example.com', 'pass123', 'Name');

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Email already registered', $result['error']);
    }

    public function test_register_returns_user_id_on_success(): void
    {
        $userModel = $this->createMock(User::class);
        $userModel->method('findByEmail')->willReturn(null);
        $userModel->method('create')->willReturn(42);

        $auth = new AuthService($userModel);
        $result = $auth->register('new@example.com', 'password123', 'New User');

        $this->assertArrayHasKey('user_id', $result);
        $this->assertSame(42, $result['user_id']);
        $this->assertArrayNotHasKey('error', $result);
    }
}
