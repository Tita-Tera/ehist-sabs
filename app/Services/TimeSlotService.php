<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\TimeSlot;
use App\Models\User;

/**
 * Time slot business logic: available slots for customers/bookings, provider CRUD.
 */
class TimeSlotService
{
    public function __construct(
        private TimeSlot $timeSlotModel,
        private Booking $bookingModel,
        private AuthService $authService
    ) {
    }

    /**
     * Get available slots for a provider on a date (default today).
     * Public/customer-facing: returns slots that are is_available and not taken by a booking.
     */
    public function getAvailableSlots(int $providerId, ?string $date = null): array
    {
        $date  = $date ?? date('Y-m-d');
        $slots = $this->timeSlotModel->getAvailableByProviderAndDate($providerId, $date);
        $available = [];
        foreach ($slots as $slot) {
            $slotDate   = $slot['slot_date'];
            $startTime  = $slot['start_time'];
            $endTime    = $slot['end_time'];
            $hasBooking = $this->bookingModel->hasOverlap($providerId, $slotDate, $startTime, $endTime, null);
            if (!$hasBooking) {
                $available[] = $slot;
            }
        }
        return $available;
    }

    /**
     * List time slots for the current provider (own availability). Optional date range.
     */
    public function getSlotsForCurrentProvider(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        if ((int) $user['role_id'] !== User::ROLE_PROVIDER && (int) $user['role_id'] !== User::ROLE_ADMIN) {
            return ['error' => 'Only service providers can manage time slots', 'code' => 403];
        }
        $providerId = (int) $user['id'];
        if ((int) $user['role_id'] === User::ROLE_ADMIN && $dateFrom === null && $dateTo === null) {
            // Admin could later support ?provider_id=X to view any provider's slots
        }
        $slots = $this->timeSlotModel->getByProvider($providerId, $dateFrom, $dateTo);
        return ['slots' => $slots];
    }

    public function create(array $input): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        if ((int) $user['role_id'] !== User::ROLE_PROVIDER && (int) $user['role_id'] !== User::ROLE_ADMIN) {
            return ['error' => 'Only service providers can create time slots', 'code' => 403];
        }
        $providerId = (int) $user['id'];
        $slotDate   = $input['slot_date'] ?? '';
        $startTime  = $input['start_time'] ?? '';
        $endTime    = $input['end_time'] ?? '';
        if ($slotDate === '' || $startTime === '' || $endTime === '') {
            return ['error' => 'Missing required fields: slot_date, start_time, end_time'];
        }
        if ($this->timeSlotModel->hasOverlap($providerId, $slotDate, $startTime, $endTime, null)) {
            return ['error' => 'Time slot overlaps with an existing slot'];
        }
        $id = $this->timeSlotModel->create([
            'provider_id'   => $providerId,
            'slot_date'     => $slotDate,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'is_available'  => (int) ($input['is_available'] ?? 1),
        ]);
        return ['slot_id' => $id];
    }

    public function update(int $id, array $input): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        $slot = $this->timeSlotModel->find($id);
        if ($slot === null) {
            return ['error' => 'Time slot not found', 'code' => 404];
        }
        $providerId = (int) $slot['provider_id'];
        $canEdit    = (int) $user['id'] === $providerId || (int) $user['role_id'] === User::ROLE_ADMIN;
        if (!$canEdit) {
            return ['error' => 'Not allowed to update this time slot', 'code' => 403];
        }
        $data = [];
        foreach (['slot_date', 'start_time', 'end_time', 'is_available'] as $key) {
            if (array_key_exists($key, $input)) {
                $data[$key] = $input[$key];
            }
        }
        if ($data !== []) {
            $slotDate   = $data['slot_date'] ?? $slot['slot_date'];
            $startTime  = $data['start_time'] ?? $slot['start_time'];
            $endTime    = $data['end_time'] ?? $slot['end_time'];
            if ($this->timeSlotModel->hasOverlap($providerId, $slotDate, $startTime, $endTime, $id)) {
                return ['error' => 'Updated time would overlap with an existing slot'];
            }
            $this->timeSlotModel->update($id, $data);
        }
        return [];
    }

    public function delete(int $id): array
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return ['error' => 'Not authenticated', 'code' => 401];
        }
        $slot = $this->timeSlotModel->find($id);
        if ($slot === null) {
            return ['error' => 'Time slot not found', 'code' => 404];
        }
        $providerId = (int) $slot['provider_id'];
        $canDelete  = (int) $user['id'] === $providerId || (int) $user['role_id'] === User::ROLE_ADMIN;
        if (!$canDelete) {
            return ['error' => 'Not allowed to delete this time slot', 'code' => 403];
        }
        $this->timeSlotModel->delete($id);
        return [];
    }
}
