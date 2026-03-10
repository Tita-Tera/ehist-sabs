# ehist-sabs — Backend & Frontend Work Summary  
## Presentation: Smart Appointment Booking System

---

## Slide 1: Project Overview

**ehist-sabs** = **Smart Appointment Booking System**

- **Purpose:** Web-based scheduling between customers and service providers
- **Stack:** PHP (OOP) backend, MySQL, HTML/CSS/JS frontend, AJAX API
- **Roles:** Administrator, Service Provider, Customer
- **Deliverables:** REST-style API, single-page app (SPA), role-based UI, conflict-free booking, notifications

---

## Slide 2: High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  FRONTEND (public/)                                          │
│  index.php  ·  style.css  ·  api.js  ·  app.js              │
│  Single-page app → all data via AJAX to /api.php            │
└───────────────────────────┬─────────────────────────────────┘
                            │ JSON, session cookie
┌───────────────────────────▼─────────────────────────────────┐
│  API ENTRY (public/api.php)                                  │
│  Routing · CORS · Health check · Error handling              │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│  BACKEND (app/)                                              │
│  Controllers → Services → Models  ·  Middleware (Auth, Role)  │
└───────────────────────────┬─────────────────────────────────┘
                            │ PDO
┌───────────────────────────▼─────────────────────────────────┐
│  DATABASE (MySQL)                                            │
│  users, roles, services, time_slots, bookings, notifications │
└─────────────────────────────────────────────────────────────┘
```

---

# BACKEND WORK

---

## Slide 3: Backend — Entry Point & Routing

**File:** `public/api.php`

- **Single API entry:** All AJAX requests go to `/api.php` (path-based routing).
- **Health check:** `GET /api.php` or `/api.php/health` → `{"api":"ehist-sabs","status":"ok"}` (no DB load).
- **CORS:** `Access-Control-Allow-Origin: *` (configurable for production).
- **Error handling:** 503 if DB unavailable, 500 with optional debug detail, JSON responses.
- **Routing:** Path segments mapped to controllers (auth, services, provider, time-slots, bookings, notifications, admin).

---

## Slide 4: Backend — API Endpoints Summary

| Area | Endpoints | Auth |
|------|-----------|------|
| **Auth** | register, login, logout, me, forgot-password, reset-password | Public / session |
| **Services** | GET providers, GET services/{id} | Public |
| **Provider** | GET/POST/PATCH/DELETE provider/services | Provider role |
| **Time slots** | GET available (public), GET/POST/PATCH/DELETE (provider) | Optional / Provider |
| **Bookings** | GET list, POST create, POST cancel, POST status, POST reschedule | Customer / Admin / Provider |
| **Notifications** | GET list, POST read, POST {id}/read | Authenticated |
| **Admin** | GET overview, GET/PATCH users | Admin role |

---

## Slide 5: Backend — Controllers

| Controller | Responsibility |
|------------|----------------|
| **AuthController** | login, register, logout, me, forgotPassword, resetPassword |
| **BookingController** | index (list), create, cancel, updateStatus, reschedule |
| **ServiceController** | providers(), byProvider(id) |
| **ProviderServiceController** | CRUD for provider’s own services |
| **TimeSlotController** | available(), index, create, update, delete |
| **NotificationController** | index, markRead, markReadMany |
| **AdminController** | overview, users list, update user |

All return **JSON**; use `BaseController` helpers (`getJsonInput()`, `getLimitOffset()`, `json()`, `jsonError()`).

---

## Slide 6: Backend — Services (Business Logic)

| Service | Role |
|---------|------|
| **AuthService** | Login/register, session, password hash/verify, forgot/reset password (tokens). |
| **BookingService** | Create booking (with overlap check), list by role (customer/provider/admin), cancel, updateStatus (approve/reject), reschedule; creates notifications. |
| **TimeSlotService** | Available slots for provider+date; CRUD for provider’s slots; overlap awareness. |
| **NotificationService** | List/mark read for current user. |

**Design:** Controllers are thin; validation and rules live in services. Notifications created on booking status changes (approved, rejected, cancelled, rescheduled).

---

## Slide 7: Backend — Models & Database

**Models:** `User`, `Booking`, `Service`, `TimeSlot`, `Notification`, `PasswordResetToken`; base `Model` with shared PDO.

**Schema highlights:**
- **roles** — administrator, service_provider, customer
- **users** — email, password (hashed), name, role_id, deleted_at (soft delete)
- **services** — provider_id, name, description, duration_min
- **time_slots** — provider_id, slot_date, start_time, end_time, is_available
- **bookings** — customer_id, provider_id, service_id, slot_date, start_time, end_time, status (pending/approved/rejected/cancelled)
- **notifications** — user_id, type, title, body, reference_type/id, read_at
- **password_reset_tokens** — token, user_id, expires_at

**Booking rules:** Overlap check in `Booking::hasOverlap()`; no double-booking.

---

## Slide 8: Backend — Middleware & Security

- **AuthMiddleware:** Ensures session user; returns 401 if not authenticated.
- **RoleMiddleware:** Restricts by role (e.g. provider-only, admin-only); returns 403 if insufficient permissions.
- **Session:** Started on first auth use; session cookie used for API auth (no JWT in this version).
- **Passwords:** `password_hash()` / `password_verify()` (PASSWORD_DEFAULT).
- **Input:** JSON body parsed in controllers; validation in services (required fields, formats, overlap).

---

## Slide 9: Backend — Init & Config

- **app/init.php:** Loads bootstrap, database config, sets PDO on `Model`.
- **app/config/database.php:** PDO connection (from .env or config).
- **app/config/config.php:** App config (e.g. debug for error detail).
- **app/Logger:** Error logging (e.g. to `storage/logs/app.log`).
- **Composer:** Autoload + PHPUnit for unit tests (`AuthService`, `BookingService`).

---

# FRONTEND WORK

---

## Slide 10: Frontend — Structure

**Owned by frontend:** Everything under `public/`

| File | Purpose |
|------|--------|
| **index.php** | Single HTML shell: header, main, all views (login, register, book, my-bookings, provider, admin). |
| **style.css** | Layout, header, cards, forms, tabs, lists, notifications, responsive rules. |
| **api.js** | API client: `api.auth.*`, `api.services.*`, `api.timeSlots.*`, `api.bookings.*`, `api.notifications.*`, `api.provider.*`, `api.admin.*`. Uses `fetch` with `credentials: 'include'`. |
| **app.js** | SPA logic: state, view switching, form handlers, load/render functions for each view. |

**Contract:** Frontend uses only the API (`/api.php`); no backend logic in pages. See `docs/API.md` and `docs/FRONTEND-CONTRACT.md`.

---

## Slide 11: Frontend — Single-Page App (app.js)

- **State:** `user`, `view`, `providers`, `servicesByProvider`, `selectedSlot`.
- **Views:** login, register, book, my-bookings, provider, admin. One visible at a time; header only when logged in.
- **Navigation:** `data-view` links and logo switch view; provider/admin nav items shown by role.
- **Auth flow:** On load, `api.auth.me()` → if valid, set user and go to default view (book for customer, admin for admin, provider for provider); else show login. Register/Login forms submit via API and then refresh state/view.
- **Global message:** Success/error banner (e.g. “Booking created”, API errors) with auto-hide.

---

## Slide 12: Frontend — Customer: Book & My Bookings

**Book view:**
- Provider dropdown (from `api.services.providers()`).
- Service dropdown (from `api.services.byProvider(providerId)`).
- Date picker (min = today).
- **Predefined slot:** Load `api.timeSlots.available(providerId, date)`; user picks one slot; submit with that start/end.
- **Custom time:** User enters start/end time; request sent for provider to accept/reschedule/reject.
- Submit → `api.bookings.create({ provider_id, service_id, slot_date, start_time, end_time })`; then refresh “My upcoming bookings” preview.

**My Bookings view:**
- `api.bookings.list()` → list with provider/service names, date, time, status.
- Cancel button → `api.bookings.cancel(id)`; list refreshed.

---

## Slide 13: Frontend — Provider View (Tabs)

- **My services:** List from `api.provider.services.list()`; add form → `api.provider.services.create()`; edit/delete per service.
- **My time slots:** List from `api.timeSlots` (provider); add form → `api.provider.timeSlots.create({ slot_date, start_time, end_time })`; update/delete.
- **Incoming bookings:** List bookings for provider; actions: Approve, Reject, Reschedule (suggest new date/time via `api.bookings.reschedule(id, body)`).

---

## Slide 14: Frontend — Admin View (Tabs)

- **Overview:** `api.admin.overview()` → counts (users, bookings by status: pending, approved, rejected, cancelled).
- **Users:** `api.admin.users(includeDeleted)` → table; `api.admin.updateUser(id, body)` for name, role_id, or soft-delete (deleted_at).
- **All bookings:** List all bookings (same bookings API, admin sees all); approve/reject via `api.bookings.updateStatus(id, status)`.

---

## Slide 15: Frontend — Notifications & UX

- **Header:** Notification icon; dropdown with list from `api.notifications.list()`; mark read via `api.notifications.markRead(id)` or `markReadMany(ids)`.
- **Loading:** Buttons show “Please wait…” and disable during submit.
- **Errors:** API errors (4xx/5xx) shown in global message with `error` (and optional `detail`) from JSON.
- **Responsive:** CSS for layout and readability (cards, grids, tabs, slot buttons).

---

## Slide 16: Frontend — API Client (api.js)

- **Base URL:** `/api.php` (same origin).
- **Methods:** `api.get(path, query)`, `api.post(path, body)`, `api.patch(path, body)`, `api.delete(path)`.
- **Options:** `credentials: 'include'` (session cookie); `Content-Type: application/json` for POST/PATCH body.
- **Error handling:** Non-2xx → throw with `err.status`, `err.data`; app.js shows `data.error` / `data.detail` in global message.
- **Convenience:** Namespaced methods for auth, services, timeSlots, bookings, notifications, provider, admin, health.

---

# INTEGRATION & QUALITY

---

## Slide 17: Integration & Testing

- **API docs:** `docs/API.md` — all endpoints, methods, body/query, auth.
- **Contract:** `docs/FRONTEND-CONTRACT.md` — API-only integration; frontend owns `public/`.
- **Postman:** `docs/postman-collection.json` for manual API testing.
- **Unit tests:** PHPUnit in `tests/Unit/` (e.g. AuthServiceTest, BookingServiceTest); mocks, no DB required; run with `composer test` or `./vendor/bin/phpunit`.
- **Seed data:** `database/seed.sql` for provider + service (e.g. provider@example.com) for quick testing.

---

## Slide 18: Summary — Backend Delivered

- REST-style JSON API with single entry point and path-based routing.
- Auth (register, login, logout, me, forgot/reset password) with session and hashed passwords.
- Services, time slots (public availability + provider CRUD), bookings (create, list, cancel, approve/reject, reschedule) with overlap prevention.
- Notifications for booking status changes.
- Admin overview, user list/update/soft-delete.
- Middleware for auth and role-based access; structured app (init, config, logger, PDO).
- Database schema, migrations, seed; API and contract documentation.

---

## Slide 19: Summary — Frontend Delivered

- Single-page app (one `index.php` + CSS + JS) with view-based navigation.
- Login/Register and role-based default view (customer → book, provider → provider, admin → admin).
- Book flow: provider → service → date → predefined slots or custom time; create booking; preview of upcoming bookings.
- My Bookings: full list and cancel.
- Provider: manage services, time slots, and incoming bookings (approve/reject/reschedule).
- Admin: overview, user management, all bookings and status updates.
- Notifications in header with mark-as-read.
- Centralized API client (`api.js`) and error/loading UX; responsive layout.

---

## Slide 20: Thank You / Q&A

**ehist-sabs** — Smart Appointment Booking System  

- **Backend:** PHP OOP, MySQL, JSON API, auth, roles, bookings, notifications, admin.  
- **Frontend:** SPA, AJAX, role-based UI, booking flow, provider and admin tools.  
- **Docs:** API.md, FRONTEND-CONTRACT.md, README, Postman collection.  
- **Run:** `php -S localhost:8000 -t public` → http://localhost:8000  

Questions?
