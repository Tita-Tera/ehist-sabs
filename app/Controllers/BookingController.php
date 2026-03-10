<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BookingService;

/**
 * Handles booking creation, list, cancel, approve/reject (AJAX/API).
 */
class BookingController extends BaseController
{
    public function __construct(
        private BookingService $bookingService
    ) {
    }

    public function index(): void
    {
        [$limit, $offset] = $this->getLimitOffset();
        $bookings = $this->bookingService->getBookingsForCurrentUser($limit, $offset);
        $this->json(['bookings' => is_array($bookings) ? $bookings : []]);
    }

    public function create(): void
    {
        if ($this->getRequestMethod() !== 'POST') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input = $this->getJsonInput();
        $result = $this->bookingService->create($input);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json([
            'success'    => true,
            'booking_id' => $result['booking_id'],
            'status'     => $result['status'] ?? 'pending',
        ]);
    }

    public function updateStatus(int $id): void
    {
        if ($this->getRequestMethod() !== 'POST' && $this->getRequestMethod() !== 'PATCH') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input = $this->getJsonInput();
        $status = trim($input['status'] ?? '');
        $result = $this->bookingService->updateStatus($id, $status);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true]);
    }

    public function cancel(int $id): void
    {
        $result = $this->bookingService->cancel($id);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true]);
    }

    public function reschedule(int $id): void
    {
        if ($this->getRequestMethod() !== 'POST' && $this->getRequestMethod() !== 'PATCH') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input = $this->getJsonInput();
        $slotDate = trim((string) ($input['slot_date'] ?? ''));
        $startTime = trim((string) ($input['start_time'] ?? ''));
        $endTime = trim((string) ($input['end_time'] ?? ''));
        $result = $this->bookingService->reschedule($id, $slotDate, $startTime, $endTime);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true]);
    }
}
