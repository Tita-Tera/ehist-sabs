<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Booking;
use App\Services\AuthService;
use App\Services\BookingService;
use PHPUnit\Framework\TestCase;

class BookingServiceTest extends TestCase
{
    public function test_getBookingsForCurrentUser_returns_empty_when_not_authenticated(): void
    {
        $auth = $this->createMock(AuthService::class);
        $auth->method('currentUser')->willReturn(null);

        $bookingModel = $this->createMock(Booking::class);
        $service = new BookingService($bookingModel, $auth, null);

        $result = $service->getBookingsForCurrentUser(10, 0);

        $this->assertSame([], $result);
    }

    public function test_create_returns_error_when_not_authenticated(): void
    {
        $auth = $this->createMock(AuthService::class);
        $auth->method('currentUser')->willReturn(null);

        $bookingModel = $this->createMock(Booking::class);
        $service = new BookingService($bookingModel, $auth, null);

        $result = $service->create([
            'provider_id' => 1,
            'service_id'  => 1,
            'slot_date'   => '2026-03-15',
            'start_time'  => '09:00:00',
            'end_time'    => '10:00:00',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Not authenticated', $result['error']);
        $this->assertSame(401, $result['code'] ?? 0);
    }

    public function test_create_returns_error_when_not_customer(): void
    {
        $auth = $this->createMock(AuthService::class);
        $auth->method('currentUser')->willReturn([
            'id' => 1, 'email' => 'p@example.com', 'name' => 'P', 'role_id' => 2, // provider
        ]);

        $bookingModel = $this->createMock(Booking::class);
        $service = new BookingService($bookingModel, $auth, null);

        $result = $service->create([
            'provider_id' => 2,
            'service_id'  => 1,
            'slot_date'   => '2026-03-15',
            'start_time'  => '09:00:00',
            'end_time'    => '10:00:00',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Only customers can create bookings', $result['error']);
        $this->assertSame(403, $result['code'] ?? 0);
    }

    public function test_create_returns_error_when_missing_required_fields(): void
    {
        $auth = $this->createMock(AuthService::class);
        $auth->method('currentUser')->willReturn([
            'id' => 1, 'email' => 'c@example.com', 'name' => 'C', 'role_id' => 3,
        ]);

        $bookingModel = $this->createMock(Booking::class);
        $service = new BookingService($bookingModel, $auth, null);

        $result = $service->create([
            'provider_id' => 1,
            'service_id'  => 1,
            // missing slot_date, start_time, end_time
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Missing required fields', $result['error']);
    }

    public function test_create_returns_error_when_slot_date_invalid(): void
    {
        $auth = $this->createMock(AuthService::class);
        $auth->method('currentUser')->willReturn([
            'id' => 1, 'email' => 'c@example.com', 'name' => 'C', 'role_id' => 3,
        ]);

        $bookingModel = $this->createMock(Booking::class);
        $bookingModel->method('hasOverlap')->willReturn(false);
        $service = new BookingService($bookingModel, $auth, null);

        $result = $service->create([
            'provider_id' => 1,
            'service_id'  => 1,
            'slot_date'   => 'not-a-date',
            'start_time'  => '09:00:00',
            'end_time'    => '10:00:00',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('slot_date', $result['error']);
    }

    public function test_create_returns_error_when_time_format_invalid(): void
    {
        $auth = $this->createMock(AuthService::class);
        $auth->method('currentUser')->willReturn([
            'id' => 1, 'email' => 'c@example.com', 'name' => 'C', 'role_id' => 3,
        ]);

        $bookingModel = $this->createMock(Booking::class);
        $bookingModel->method('hasOverlap')->willReturn(false);
        $service = new BookingService($bookingModel, $auth, null);

        // Use a string that fails the H:i / H:i:s regex (e.g. single digit minutes)
        $result = $service->create([
            'provider_id' => 1,
            'service_id'  => 1,
            'slot_date'   => '2026-03-15',
            'start_time'  => '09:0',
            'end_time'    => '10:00:00',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('start_time', $result['error']);
    }

    public function test_create_returns_error_when_overlap(): void
    {
        $auth = $this->createMock(AuthService::class);
        $auth->method('currentUser')->willReturn([
            'id' => 1, 'email' => 'c@example.com', 'name' => 'C', 'role_id' => 3,
        ]);

        $bookingModel = $this->createMock(Booking::class);
        $bookingModel->method('hasOverlap')->willReturn(true);

        $service = new BookingService($bookingModel, $auth, null);

        $result = $service->create([
            'provider_id' => 1,
            'service_id'  => 1,
            'slot_date'   => '2026-03-15',
            'start_time'  => '09:00:00',
            'end_time'    => '10:00:00',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('overlap', $result['error']);
    }

    public function test_create_returns_booking_id_on_success(): void
    {
        $auth = $this->createMock(AuthService::class);
        $auth->method('currentUser')->willReturn([
            'id' => 1, 'email' => 'c@example.com', 'name' => 'C', 'role_id' => 3,
        ]);

        $bookingModel = $this->createMock(Booking::class);
        $bookingModel->method('hasOverlap')->willReturn(false);
        $bookingModel->method('create')->willReturn(99);

        $service = new BookingService($bookingModel, $auth, null);

        $result = $service->create([
            'provider_id' => 1,
            'service_id'  => 1,
            'slot_date'   => '2026-03-15',
            'start_time'  => '09:00:00',
            'end_time'    => '10:00:00',
        ]);

        $this->assertArrayHasKey('booking_id', $result);
        $this->assertSame(99, $result['booking_id']);
        $this->assertArrayNotHasKey('error', $result);
    }
}
