<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\TimeSlot;
use App\Models\User;

/**
 * Booking business logic: create, list, cancel, approve/reject with conflict checks.
 * Bookings on predefined time slots are auto-approved; custom times stay pending for provider approval.
 */
class BookingService
{
    public function __construct(
        private Booking $bookingModel,
        private AuthService $authService,
        private ?Notification $notificationModel = null,
        private ?TimeSlot $timeSlotModel = null
    ) {
    }

    private function notifyBookingStatus(int $userId, int $bookingId, string $status, string $title): void
    {
        if ($this->notificationModel === null) {
            return;
        }
        $this->notificationModel->create([
            'user_id'        => $userId,
            'type'           => 'booking',
            'title'          => $title,
            'body'           => "Booking #{$bookingId} has been {$status}.",
            'reference_type' => 'booking',
            'reference_id'   => $bookingId,
        ]);
    }

    public function getBookingsForCurrentUser(int $limit = 50, int $offset = 0): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return [];
        }
        $roleId = (int) $user['role_id'];
        $id     = (int) $user['id'];
        if ($roleId === User::ROLE_CUSTOMER) {
            return $this->bookingModel->getByCustomer($id, $limit, $offset);
        }
        if ($roleId === User::ROLE_PROVIDER) {
            return $this->bookingModel->getByProvider($id, $limit, $offset);
        }
        return $this->bookingModel->getAll($limit, $offset);
    }

    public function create(array $input): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        if ((int) $user['role_id'] !== User::ROLE_CUSTOMER) {
            return ['error' => 'Only customers can create bookings', 'code' => 403];
        }
        $providerId = (int) ($input['provider_id'] ?? 0);
        $serviceId  = (int) ($input['service_id'] ?? 0);
        $slotDate   = $input['slot_date'] ?? '';
        $startTime  = $input['start_time'] ?? '';
        $endTime    = $input['end_time'] ?? '';
        if ($providerId <= 0 || $serviceId <= 0 || $slotDate === '' || $startTime === '' || $endTime === '') {
            return ['error' => 'Missing required fields: provider_id, service_id, slot_date, start_time, end_time'];
        }
        $d = \DateTime::createFromFormat('Y-m-d', $slotDate);
        if (!$d || $d->format('Y-m-d') !== $slotDate) {
            return ['error' => 'Invalid slot_date format. Use Y-m-d'];
        }
        if (!preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $startTime) || !preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $endTime)) {
            return ['error' => 'Invalid start_time or end_time. Use H:i or H:i:s'];
        }
        if ($this->bookingModel->hasOverlap($providerId, $slotDate, $startTime, $endTime, null)) {
            return ['error' => 'Time slot is no longer available (overlap)'];
        }
        $isPredefinedSlot = $this->timeSlotModel !== null
            && $this->timeSlotModel->existsAvailableSlot($providerId, $slotDate, $startTime, $endTime);
        $status = $isPredefinedSlot ? Booking::STATUS_APPROVED : Booking::STATUS_PENDING;
        $bookingId = $this->bookingModel->create([
            'customer_id' => (int) $user['id'],
            'provider_id' => $providerId,
            'service_id'  => $serviceId,
            'slot_date'   => $slotDate,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => $status,
        ]);
        return ['booking_id' => $bookingId, 'status' => $status];
    }

    public function updateStatus(int $bookingId, string $status): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        $allowed = [Booking::STATUS_APPROVED, Booking::STATUS_REJECTED];
        if (!in_array($status, $allowed, true)) {
            return ['error' => 'Invalid status'];
        }
        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return ['error' => 'Booking not found', 'code' => 404];
        }
        $roleId = (int) $user['role_id'];
        $uid = (int) $user['id'];
        $canAct = ($roleId === User::ROLE_ADMIN)
            || ($roleId === User::ROLE_PROVIDER && (int) $booking['provider_id'] === $uid);
        if (!$canAct) {
            return ['error' => 'Only the provider or an administrator can approve/reject this booking', 'code' => 403];
        }
        $this->bookingModel->updateStatus($bookingId, $status);
        $this->notifyBookingStatus(
            (int) $booking['customer_id'],
            $bookingId,
            $status,
            "Booking #{$bookingId} " . ($status === Booking::STATUS_APPROVED ? 'approved' : 'rejected')
        );
        return [];
    }

    /** Provider (or admin) can reschedule a pending booking to a new date/time. */
    public function reschedule(int $bookingId, string $slotDate, string $startTime, string $endTime): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return ['error' => 'Booking not found', 'code' => 404];
        }
        if ((string) $booking['status'] !== Booking::STATUS_PENDING) {
            return ['error' => 'Only pending bookings can be rescheduled', 'code' => 400];
        }
        $roleId = (int) $user['role_id'];
        $uid = (int) $user['id'];
        $canAct = ($roleId === User::ROLE_ADMIN)
            || ($roleId === User::ROLE_PROVIDER && (int) $booking['provider_id'] === $uid);
        if (!$canAct) {
            return ['error' => 'Only the provider or an administrator can reschedule this booking', 'code' => 403];
        }
        $d = \DateTime::createFromFormat('Y-m-d', $slotDate);
        if (!$d || $d->format('Y-m-d') !== $slotDate) {
            return ['error' => 'Invalid slot_date format. Use Y-m-d'];
        }
        if (!preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $startTime) || !preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $endTime)) {
            return ['error' => 'Invalid start_time or end_time. Use H:i or H:i:s'];
        }
        $providerId = (int) $booking['provider_id'];
        if ($this->bookingModel->hasOverlap($providerId, $slotDate, $startTime, $endTime, $bookingId)) {
            return ['error' => 'The new time slot overlaps with another booking'];
        }
        $this->bookingModel->updateTime($bookingId, $slotDate, $startTime, $endTime);
        $this->notifyBookingStatus(
            (int) $booking['customer_id'],
            $bookingId,
            'rescheduled',
            "Booking #{$bookingId} has been rescheduled to {$slotDate} {$startTime}–{$endTime}."
        );
        return [];
    }

    public function cancel(int $bookingId): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return ['error' => 'Booking not found', 'code' => 404];
        }
        $uid = (int) $user['id'];
        $roleId = (int) $user['role_id'];
        $canCancel = ($roleId === User::ROLE_ADMIN)
            || ($roleId === User::ROLE_CUSTOMER && (int) $booking['customer_id'] === $uid)
            || ($roleId === User::ROLE_PROVIDER && (int) $booking['provider_id'] === $uid);
        if (!$canCancel) {
            return ['error' => 'Not allowed to cancel this booking', 'code' => 403];
        }
        $this->bookingModel->updateStatus($bookingId, Booking::STATUS_CANCELLED);
        $this->notifyBookingStatus(
            (int) $booking['customer_id'],
            $bookingId,
            'cancelled',
            "Booking #{$bookingId} cancelled"
        );
        return [];
    }
}
