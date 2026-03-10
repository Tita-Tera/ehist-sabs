<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Service;
use App\Services\AuthService;

/**
 * CRUD for the logged-in provider's own services.
 * Requires authentication and role service_provider.
 */
class ProviderServiceController extends BaseController
{
    public function __construct(
        private AuthService $authService,
        private Service $serviceModel
    ) {
    }

    /** GET provider/services — list current provider's services. */
    public function index(): void
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            $this->jsonError('Authentication required', 401);
            return;
        }
        $providerId = (int) $user['id'];
        $services = $this->serviceModel->getByProvider($providerId);
        $this->json(['services' => $services]);
    }

    /** POST provider/services — create a service for current provider. */
    public function create(): void
    {
        if ($this->getRequestMethod() !== 'POST') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $user = $this->authService->currentUser();
        if ($user === null) {
            $this->jsonError('Authentication required', 401);
            return;
        }
        $providerId = (int) $user['id'];
        $input = $this->getJsonInput();
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $this->jsonError('Name is required');
            return;
        }
        $durationMin = (int) ($input['duration_min'] ?? 60);
        if ($durationMin < 1 || $durationMin > 1440) {
            $this->jsonError('duration_min must be between 1 and 1440');
            return;
        }
        $id = $this->serviceModel->create([
            'provider_id'   => $providerId,
            'name'          => $name,
            'description'   => isset($input['description']) ? trim((string) $input['description']) : null,
            'duration_min'  => $durationMin,
        ]);
        $this->json(['success' => true, 'service_id' => $id], 201);
    }

    /** PATCH provider/services/:id — update a service (only if owned by current provider). */
    public function update(int $id): void
    {
        if ($this->getRequestMethod() !== 'PATCH' && $this->getRequestMethod() !== 'PUT') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $user = $this->authService->currentUser();
        if ($user === null) {
            $this->jsonError('Authentication required', 401);
            return;
        }
        $providerId = (int) $user['id'];
        $service = $this->serviceModel->findByProviderAndId($providerId, $id);
        if ($service === null) {
            $this->jsonError('Not found', 404);
            return;
        }
        $input = $this->getJsonInput();
        $name = isset($input['name']) ? trim((string) $input['name']) : $service['name'];
        if ($name === '') {
            $this->jsonError('Name cannot be empty');
            return;
        }
        $description = array_key_exists('description', $input)
            ? (trim((string) $input['description']) ?: null)
            : $service['description'];
        $durationMin = array_key_exists('duration_min', $input)
            ? (int) $input['duration_min']
            : (int) $service['duration_min'];
        if ($durationMin < 1 || $durationMin > 1440) {
            $this->jsonError('duration_min must be between 1 and 1440');
            return;
        }
        $ok = $this->serviceModel->update($id, $providerId, [
            'name'         => $name,
            'description'  => $description,
            'duration_min' => $durationMin,
        ]);
        if (!$ok) {
            $this->jsonError('Update failed', 500);
            return;
        }
        $this->json(['success' => true]);
    }

    /** DELETE provider/services/:id — delete a service (only if owned by current provider). */
    public function delete(int $id): void
    {
        if ($this->getRequestMethod() !== 'DELETE') {
            $this->jsonError('Method not allowed', 405);
            return;
        }
        $user = $this->authService->currentUser();
        if ($user === null) {
            $this->jsonError('Authentication required', 401);
            return;
        }
        $providerId = (int) $user['id'];
        $ok = $this->serviceModel->delete($id, $providerId);
        if (!$ok) {
            $this->jsonError('Not found', 404);
            return;
        }
        $this->json(['success' => true]);
    }
}
