<?php

declare(strict_types=1);

/**
 * API entry point: routes AJAX/API requests to controllers.
 * Usage: POST /api.php/auth/login, GET /api.php/bookings, etc.
 */
header('Content-Type: application/json; charset=utf-8');

// Parse path first (no DB/session needed). Support subdirs: .../api.php/bookings -> bookings
$rawPath = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '';
$path = trim(parse_url($rawPath, PHP_URL_PATH) ?: $rawPath, '/');
if (preg_match('#api\.php/?(.*)$#', $path, $m)) {
    $path = trim($m[1] ?? '', '/');
} else {
    $path = preg_replace('#^api\.php/?#', '', $path);
}
$path = trim((string) $path, '/');
$segments = $path !== '' ? explode('/', $path) : [];

// Health check before loading app — always returns 200 so you can verify the API is reachable
if ($segments === [] || ($segments[0] ?? '') === 'health') {
    http_response_code(200);
    echo json_encode([
        'api'    => 'ehist-sabs',
        'status' => 'ok',
        'docs'   => 'See docs/API.md for endpoints. Base path: /api.php',
    ]);
    exit;
}

// Load app (DB + session)
try {
    require_once dirname(__DIR__) . '/app/init.php';
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable', 'detail' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Setup failed', 'detail' => $e->getMessage()]);
    exit;
}

$config = require dirname(__DIR__) . '/app/config/config.php';

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\ProviderServiceController;
use App\Controllers\ServiceController;
use App\Controllers\TimeSlotController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Controllers\NotificationController;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\PasswordResetToken;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\AuthService;
use App\Services\BookingService;
use App\Services\NotificationService;
use App\Services\TimeSlotService;

// CORS headers for frontend (adjust origin in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Wire services and controllers
$userModel         = new User();
$bookingModel      = new Booking();
$timeSlotModel     = new TimeSlot();
$notificationModel = new Notification();
$passwordResetModel = new PasswordResetToken();
$authService       = new AuthService($userModel, $passwordResetModel);
$bookingService    = new BookingService($bookingModel, $authService, $notificationModel, $timeSlotModel);
$timeSlotService   = new TimeSlotService($timeSlotModel, $bookingModel, $authService);
$authController    = new AuthController($authService);
$bookingController = new BookingController($bookingService);
$notificationService = new NotificationService($notificationModel, $authService);
$notificationController = new NotificationController($notificationService);
$serviceController = new ServiceController();
$timeSlotController = new TimeSlotController($timeSlotService);
$providerServiceController = new ProviderServiceController($authService, new Service());

$authMiddleware = new AuthMiddleware($authService);
$providerMiddleware = new RoleMiddleware($authService, User::ROLE_PROVIDER);
$roleMiddlewareAdmin = new RoleMiddleware($authService, User::ROLE_ADMIN);
$adminController = new AdminController($userModel, $bookingModel);

// Route dispatch
try {
    // Auth (public)
    if ($segments[0] === 'auth') {
        $action = $segments[1] ?? 'login';
        if ($action === 'login') {
            $authController->login();
        } elseif ($action === 'register') {
            $authController->register();
        } elseif ($action === 'logout') {
            $authController->logout();
        } elseif ($action === 'me') {
            $authController->me();
        } elseif ($action === 'forgot-password') {
            $authController->forgotPassword();
        } elseif ($action === 'reset-password') {
            $authController->resetPassword();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        exit;
    }

    // Services (public read)
    if ($segments[0] === 'services') {
        if (($segments[1] ?? '') === 'providers') {
            $serviceController->providers();
        } elseif (isset($segments[1]) && is_numeric($segments[1])) {
            $serviceController->byProvider((int) $segments[1]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        exit;
    }

    // Provider: CRUD for own services (auth + role service_provider)
    if ($segments[0] === 'provider' && ($segments[1] ?? '') === 'services') {
        $providerMiddleware(function () use ($providerServiceController, $segments) {
            $id = isset($segments[2]) && is_numeric($segments[2]) ? (int) $segments[2] : null;
            if ($id === null && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $providerServiceController->index();
                return;
            }
            if ($id === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $providerServiceController->create();
                return;
            }
            if ($id !== null && ($_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'PUT')) {
                $providerServiceController->update($id);
                return;
            }
            if ($id !== null && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $providerServiceController->delete($id);
                return;
            }
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        });
        exit;
    }

    // Notifications (authenticated)
    if ($segments[0] === 'notifications') {
        $authMiddleware(function () use ($notificationController, $segments) {
            if (!isset($segments[1]) || $segments[1] === '') {
                $notificationController->index();
                return;
            }
            if ($segments[1] === 'read' && ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PATCH')) {
                $notificationController->markReadMany();
                return;
            }
            if (isset($segments[1]) && is_numeric($segments[1]) && ($segments[2] ?? '') === 'read') {
                $notificationController->markRead((int) $segments[1]);
                return;
            }
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        });
        exit;
    }

    // Time slots: GET available (public), provider CRUD (authenticated)
    if ($segments[0] === 'time-slots') {
        $sub = $segments[1] ?? '';
        $id  = isset($segments[1]) && is_numeric($segments[1]) ? (int) $segments[1] : null;
        if ($sub === 'available') {
            $timeSlotController->available();
            exit;
        }
        $authMiddleware(function () use ($timeSlotController, $segments) {
            $id  = isset($segments[1]) && is_numeric($segments[1]) ? (int) $segments[1] : null;
            if ($id === null && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $timeSlotController->index();
                return;
            }
            if ($id === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $timeSlotController->create();
                return;
            }
            if ($id !== null && ($_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'POST') && ($segments[2] ?? '') === '') {
                $timeSlotController->update($id);
                return;
            }
            if ($id !== null && $_SERVER['REQUEST_METHOD'] === 'DELETE' && ($segments[2] ?? '') === '') {
                $timeSlotController->delete($id);
                return;
            }
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        });
        exit;
    }

    // Admin (authenticated, admin role only)
    if ($segments[0] === 'admin') {
        $authMiddleware(function () use ($roleMiddlewareAdmin, $adminController, $segments) {
            $roleMiddlewareAdmin(function () use ($adminController, $segments) {
                if (($segments[1] ?? '') === 'overview') {
                    $adminController->overview();
                    return;
                }
                if (($segments[1] ?? '') === 'users') {
                    $id = isset($segments[2]) && is_numeric($segments[2]) ? (int) $segments[2] : null;
                    if ($id === null && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
                        $adminController->index();
                        return;
                    }
                    if ($id !== null && ($_SERVER['REQUEST_METHOD'] ?? '') === 'PATCH') {
                        $adminController->update($id);
                        return;
                    }
                }
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            });
        });
        exit;
    }

    // Bookings (authenticated)
    if ($segments[0] === 'bookings') {
        $authMiddleware(function () use ($bookingController, $segments) {
            $id = isset($segments[1]) && is_numeric($segments[1]) ? (int) $segments[1] : null;
            $sub = $segments[2] ?? '';
            if ($id === null && $sub === '') {
                $bookingController->index();
                return;
            }
            if ($id !== null && $sub === 'cancel') {
                $bookingController->cancel($id);
                return;
            }
            if ($id !== null && $sub === 'status') {
                $bookingController->updateStatus($id);
                return;
            }
            if ($id !== null && $sub === 'reschedule') {
                $bookingController->reschedule($id);
                return;
            }
            if ($id === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $bookingController->create();
                return;
            }
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        });
        exit;
    }

    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found']);
} catch (Throwable $e) {
    \App\Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
    http_response_code(500);
    header('Content-Type: application/json');
    $debug = isset($config) && ($config['debug'] ?? false);
    echo json_encode([
        'error'  => 'Internal server error',
        'detail' => $debug ? $e->getMessage() : null,
    ]);
}
