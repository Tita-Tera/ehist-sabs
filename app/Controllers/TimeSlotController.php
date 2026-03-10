<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TimeSlotService;

/**
 * Handles time slot availability (GET for customers) and provider CRUD (POST/PATCH/DELETE).
 */
class TimeSlotController extends BaseController
{
    public function __construct(
        private TimeSlotService $timeSlotService
    ) {
    }

    /**
     * GET available slots for a provider (query: provider_id, optional date).
     * Public – no auth required so customers can see availability.
     */
    public function available(): void
    {
        $providerId = isset($_GET['provider_id']) ? (int) $_GET['provider_id'] : 0;
        $date       = isset($_GET['date']) ? trim((string) $_GET['date']) : null;
        if ($providerId <= 0) {
            $this->jsonError('provider_id is required', 400);
            return;
        }
        $slots = $this->timeSlotService->getAvailableSlots($providerId, $date ?: null);
        $this->json(['slots' => $slots]);
    }

    /**
     * GET current provider's time slots (optional date_from, date_to).
     * Auth required – provider only.
     */
    public function index(): void
    {
        $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : null;
        $dateTo   = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : null;
        $result   = $this->timeSlotService->getSlotsForCurrentProvider($dateFrom ?: null, $dateTo ?: null);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json($result);
    }

    public function create(): void
    {
        if ($this->getRequestMethod() !== 'POST') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input  = $this->getJsonInput();
        $result = $this->timeSlotService->create($input);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true, 'slot_id' => $result['slot_id']]);
    }

    public function update(int $id): void
    {
        if ($this->getRequestMethod() !== 'PATCH' && $this->getRequestMethod() !== 'POST') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $input  = $this->getJsonInput();
        $result = $this->timeSlotService->update($id, $input);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true]);
    }

    public function delete(int $id): void
    {
        $result = $this->timeSlotService->delete($id);
        if (isset($result['error'])) {
            $this->jsonError($result['error'], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true]);
    }
}
