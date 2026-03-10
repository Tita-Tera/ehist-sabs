<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Service;
use App\Models\User;

/**
 * Handles listing services and providers (AJAX/API).
 */
class ServiceController extends BaseController
{
    public function providers(): void
    {
        $userModel = new User();
        $providers = $userModel->getProviders();
        $this->json(['providers' => $providers]);
    }

    public function byProvider(int $providerId): void
    {
        $service = new Service();
        $services = $service->getByProvider($providerId);
        $this->json(['services' => $services]);
    }
}
