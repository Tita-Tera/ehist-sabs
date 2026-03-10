# Potential Q&A — Backend & Frontend (ehist-sabs)

Questions a supervisor or audience might ask, with concise answers based on the actual implementation.

---

## Architecture & design

**Q: Why did you use a single API entry point (`api.php`) instead of separate files per resource?**  
**A:** One entry point gives a single place for CORS, error handling, and routing. We parse the path and dispatch to the right controller. It’s easier to secure, log, and maintain than many small PHP files. The frontend only needs one base URL.

**Q: How is the frontend separated from the backend?**  
**A:** The frontend lives entirely under `public/` (HTML, CSS, JS). It never executes business logic in PHP; it only calls `/api.php` with JSON and uses the session cookie. We documented this in `docs/FRONTEND-CONTRACT.md` so the frontend team can work against the API without touching `app/`.

**Q: Why put business logic in Services instead of Controllers?**  
**A:** Controllers stay thin: they read the request, call a service, and return JSON. Services hold validation, overlap checks, and notifications. That makes the logic testable with unit tests (we mock the service dependencies) and reusable if we add another entry point (e.g. CLI or a second API).

**Q: What is the role of Middleware?**  
**A:** We have two: **AuthMiddleware** checks that a user is logged in (session); if not, we return 401. **RoleMiddleware** checks that the current user has the required role (e.g. provider or admin); if not, we return 403. They wrap protected routes in `api.php` so we don’t duplicate auth checks in every controller.

---

## Security

**Q: How are passwords stored?**  
**A:** We use PHP’s `password_hash()` with `PASSWORD_DEFAULT` (bcrypt) and verify with `password_verify()`. Plain passwords are never stored.

**Q: How do you prevent double-booking or overlapping appointments?**  
**A:** In the **Booking** model we have `hasOverlap()`: it checks the `bookings` table for the same provider and date, excluding cancelled and rejected bookings, and detects overlapping time ranges. **BookingService** calls this before creating a booking and before rescheduling. If there’s an overlap we return an error and don’t insert/update.

**Q: Who can cancel a booking?**  
**A:** The **customer** who made it, the **provider** of the service, or an **administrator**. We check `customer_id`, `provider_id`, and role in **BookingService::cancel()** and return 403 if the current user isn’t allowed.

**Q: Who can approve or reject a booking?**  
**A:** Only the **provider** of that booking or an **administrator**. We check in **BookingService::updateStatus()** and return 403 otherwise.

**Q: How is the API authenticated?**  
**A:** Session-based. After login we store the user id in `$_SESSION` and send a session cookie. The frontend sends `credentials: 'include'` so the cookie is sent with every request. We don’t use JWT in this version.

**Q: What about SQL injection?**  
**A:** We use **prepared statements** everywhere (PDO `prepare` + `execute` with parameters). User input is never concatenated into SQL strings.

**Q: Why is CORS set to `*`?**  
**A:** For development it’s convenient when the frontend runs on a different port or domain. In production we would restrict `Access-Control-Allow-Origin` to the actual frontend origin for better security.

---

## API & backend behaviour

**Q: What HTTP methods do you use and why?**  
**A:** **GET** for reading (list bookings, get services, etc.). **POST** for actions that change state (login, create booking, cancel, approve/reject, reschedule). **PATCH** for partial updates (e.g. provider services, admin user update). **DELETE** for deleting (e.g. provider service or time slot). We use standard semantics so the API is predictable.

**Q: How do you handle errors?**  
**A:** We return appropriate status codes: 400 for validation errors, 401 for not logged in, 403 for forbidden, 404 for not found, 500 for server errors. The body is always JSON with at least an `error` key; in debug mode we may add a `detail` key with the exception message. We also log errors with **Logger** to `storage/logs/app.log`.

**Q: What validation do you do on booking creation?**  
**A:** We check: required fields (provider_id, service_id, slot_date, start_time, end_time), slot_date in Y-m-d format, start/end time in H:i or H:i:s, user is a customer, and no overlap for that provider/date/time. If any check fails we return 400 with an error message.

**Q: What is the “reschedule” feature?**  
**A:** It lets the **provider** (or admin) change the date/time of a **pending** booking. We validate the new slot format and run the same overlap check (excluding the current booking id). Then we update the booking and create a notification for the customer (e.g. “Booking #X has been rescheduled to …”).

**Q: How do notifications work?**  
**A:** When a booking is approved, rejected, cancelled, or rescheduled, **BookingService** calls **Notification** model to insert a row (user_id, type, title, body, reference to the booking). The frontend fetches notifications via `GET /api.php/notifications` and can mark them read. We don’t use real-time push; the user sees updates when they open the dropdown or refresh.

---

## Database & models

**Q: Why soft delete for users?**  
**A:** So admins can “delete” a user without losing history. We set `deleted_at`; queries can filter these out by default. Admin can list users “including deleted” and we can restore by clearing `deleted_at` if needed.

**Q: How does the overlap check work in SQL?**  
**A:** We look for rows where provider and date match, status is not cancelled/rejected, and the time ranges overlap. Two ranges overlap if one starts before the other ends and ends after the other starts. We express that with three OR conditions and use an optional `excludeId` so when rescheduling we don’t count the booking we’re updating.

**Q: Why are bookings listed differently for customer vs provider vs admin?**  
**A:** **BookingService::getBookingsForCurrentUser()** checks the current user’s role: **customer** → `getByCustomer(id)`, **provider** → `getByProvider(id)`, **admin** → `getAll()`. So one endpoint returns different data depending on who is logged in, which keeps the API simple.

---

## Frontend & SPA

**Q: Why a single-page app (SPA) instead of multiple HTML pages?**  
**A:** One HTML file with all views lets us switch screens without full reloads. State (user, providers, selected slot) stays in memory. We only need one place to handle auth (check on load, then show the right view by role). It fits the “frontend calls API only” contract.

**Q: How do you know which view to show when the user logs in?**  
**A:** On load we call `GET /api.php/auth/me`. If we get a user we set `state.user` and choose the default view from `role_id`: customer → “book”, provider → “provider”, admin → “admin”. If we don’t get a user we show the login view.

**Q: What is the difference between “predefined slot” and “custom time” when booking?**  
**A:** **Predefined:** We fetch available slots from `GET /api.php/time-slots/available?provider_id=&date=`. The user picks one and we send that exact start/end. **Custom:** The user types start and end time; we send that to the API. The slot might not exist in the provider’s time_slots; the provider can then approve, reject, or reschedule to a valid slot. So custom is a “request” that the provider handles manually.

**Q: Where is the API base URL defined?**  
**A:** In `api.js` the base is `/api.php` (same origin). So it works whether we run on `localhost:8000` or a deployed domain. For a different origin we’d only change that constant and ensure CORS and credentials are correct.

**Q: How do you show API errors to the user?**  
**A:** The API client in `api.js` throws on non-2xx with `err.status` and `err.data` (parsed JSON). `app.js` catches that and shows `data.error` (and optionally `data.detail`) in a global message bar that auto-hides after a few seconds.

---

## Roles & permissions

**Q: What can each role do?**  
**A:** **Customer:** register, login, view providers/services, see available slots, create booking, view and cancel own bookings. **Provider:** everything a customer can, plus manage own services (CRUD), manage own time slots (CRUD), and for bookings where they are the provider: approve, reject, reschedule. **Administrator:** all of the above plus admin overview (counts), list/update/soft-delete any user, list all bookings and approve/reject any booking.

**Q: How do you enforce “provider can only edit their own services”?**  
**A:** Provider endpoints are wrapped in **RoleMiddleware** (provider role). The **ProviderServiceController** and **TimeSlotController** (for create/update/delete) work with the current user from **AuthService::currentUser()**; the models and services filter or check by `provider_id` so a provider can only see and change their own data.

---

## Testing & documentation

**Q: How do you test the backend without a database?**  
**A:** We use **PHPUnit** and **mock** the dependencies. For example, AuthService is tested with a fake User model that returns predefined data; BookingService is tested with a mock Booking model and AuthService. So we test validation and business rules without connecting to MySQL. We run tests with `composer test` or `./vendor/bin/phpunit`.

**Q: What is documented for the API?**  
**A:** **docs/API.md** lists every endpoint: method, URL, body/query, and who can call it. We also have **docs/FRONTEND-CONTRACT.md** for the frontend/backend boundary and **docs/postman-collection.json** for manual testing.

---

## Scalability & future work

**Q: What would you change for a larger scale?**  
**A:** Examples: (1) Restrict CORS to the real frontend origin. (2) Add rate limiting on login and booking creation. (3) Use a proper session store (e.g. Redis) if we run multiple app servers. (4) Add indexes if we have many bookings (e.g. on provider_id + slot_date + status). (5) Consider JWT or tokens if we need mobile or cross-domain auth.

**Q: Could the frontend be replaced with a mobile app?**  
**A:** Yes. The API is JSON and session-cookie based; for mobile we could add token-based auth (e.g. return a token on login and send it in a header). The same endpoints (bookings, services, time-slots, notifications, etc.) would still apply.

**Q: How would you add email for “forgot password”?**  
**A:** We already have the flow: **AuthController::forgotPassword()** creates a token and returns it (for testing). In production we’d inject a mailer service and send an email with a link containing the token; **reset-password** would stay as is, consuming the token from the request body.

---

## Quick reference

| Topic        | Where to look                          |
|-------------|-----------------------------------------|
| Overlap check | `app/Models/Booking.php` → `hasOverlap()` |
| Who can cancel | `app/Services/BookingService.php` → `cancel()` |
| Who can approve/reject | `app/Services/BookingService.php` → `updateStatus()` |
| Auth/session | `app/Services/AuthService.php`, `app/Middleware/AuthMiddleware.php` |
| API routes   | `public/api.php`                        |
| Frontend API client | `public/api.js`                     |
| SPA state & views | `public/app.js`                    |
