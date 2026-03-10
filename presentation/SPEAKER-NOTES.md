# Speaker notes — ehist-sabs Backend & Frontend

Use these in **Google Slides** (View → Show speaker notes) or **PowerPoint** (Notes pane) under each slide.

**How to use:**  
1. Create one slide per “Slide N” section in `WORK-DONE-BACKEND-FRONTEND.md`.  
2. For each slide, copy the **“Say”** paragraph (and optionally **“Key points to stress”**) into the speaker notes for that slide.  
3. Present with the notes visible only to you (presenter view).

---

## Slide 1: Project Overview

**Say:**  
“This is ehist-sabs, our Smart Appointment Booking System. It’s a web app where customers book appointments with service providers, and admins oversee the system. We use PHP on the backend, MySQL for data, and a single-page frontend that talks to the API over AJAX. We have three roles—administrator, service provider, and customer—and we deliver a REST-style API, a full SPA, role-based UI, conflict-free booking, and notifications.”

**Key points to stress:**  
- One product: booking system for customers and providers.  
- Clear split: backend API vs frontend SPA.  
- Three roles drive what each user sees and can do.

---

## Slide 2: High-Level Architecture

**Say:**  
“Everything the user sees lives in the frontend under `public/`: one HTML shell, CSS, and two JS files. The frontend never runs PHP logic; it only calls our API at `/api.php` with JSON and uses the session cookie. The API file does routing, CORS, and error handling, then delegates to controllers. Controllers use services and models; middleware handles auth and roles. All persistence goes through PDO into MySQL.”

**Key points to stress:**  
- Single entry point: `/api.php`.  
- Frontend is 100% API-driven.  
- Clear layers: API → Controllers → Services → Models → DB.

---

## Slide 3: Backend — Entry Point & Routing

**Say:**  
“All backend requests hit `public/api.php`. We do path-based routing: the URL path decides which controller runs. Before touching the database we expose a health check so you can verify the API is up. We send CORS headers so the frontend can call from another port or domain during development. If the database is down we return 503; other errors are 500 with optional debug detail. Every response is JSON.”

**Key points to stress:**  
- One file, many routes.  
- Health check is cheap and doesn’t use the DB.  
- CORS and error handling are built in.

---

## Slide 4: Backend — API Endpoints Summary

**Say:**  
“Auth endpoints are mostly public for login and register; the rest use the session. Services and available time slots are public so customers can browse before logging in. Provider and admin areas are protected by role. Bookings require auth and differ by role: customers create and cancel, admins approve or reject, providers can also reschedule. Notifications are for the logged-in user only.”

**Key points to stress:**  
- Table is your “map” of the API.  
- Public vs authenticated vs role-based.  
- Bookings and notifications are central to the flow.

---

## Slide 5: Backend — Controllers

**Say:**  
“Controllers are thin: they read the request, call the right service method, and return JSON. They use a base controller for parsing JSON body, limit/offset, and consistent success/error responses. Each controller covers one area—auth, bookings, services, time slots, notifications, provider services, admin—so the API is easy to extend and test.”

**Key points to stress:**  
- No business logic in controllers; that’s in services.  
- Same JSON and error pattern everywhere.  
- One controller per domain (auth, bookings, etc.).

---

## Slide 6: Backend — Services (Business Logic)

**Say:**  
“All real logic lives in services. AuthService handles login, registration, session, and password reset. BookingService validates inputs, checks for overlapping times, creates or updates bookings, and triggers notifications when status changes. TimeSlotService figures out available slots and lets providers manage their schedule. NotificationService just lists and marks read. So when a booking is approved or cancelled, the service layer both updates the DB and creates the notification.”

**Key points to stress:**  
- Services own validation and business rules.  
- Overlap check prevents double-booking.  
- Notifications are created automatically from booking actions.

---

## Slide 7: Backend — Models & Database

**Say:**  
“We have one model per main table, plus a base model with shared PDO. The schema has roles, users with soft delete, services per provider, time slots for availability, and bookings with status. There are notifications and password-reset tokens. The important rule is in the Booking model: we check for overlapping times before creating or rescheduling so we never double-book a provider.”

**Key points to stress:**  
- Models map to tables and encapsulate queries.  
- Soft delete on users for admin safety.  
- Overlap check is in the model and used by BookingService.

---

## Slide 8: Backend — Middleware & Security

**Say:**  
“We use two middleware layers: one that just checks if the user is logged in, and one that checks their role. Unauthenticated requests get 401; wrong role gets 403. Auth is session-based with a cookie—no JWT in this version. Passwords are hashed with PHP’s default algorithm. We parse JSON in the controller and validate in the service so we never trust input blindly.”

**Key points to stress:**  
- AuthMiddleware = “is there a user?”  
- RoleMiddleware = “does this user have the right role?”  
- Session + hashed passwords, validation in services.

---

## Slide 9: Backend — Init & Config

**Say:**  
“Init loads the bootstrap and database config and attaches PDO to the base model so every model can run queries. Config holds things like debug mode for error messages. We have a logger that writes to `storage/logs`. We use Composer for autoloading and PHPUnit; our unit tests mock dependencies so we don’t need a real database when running tests.”

**Key points to stress:**  
- One init path for the app and DB.  
- Config and logging are centralized.  
- Tests run without a live DB.

---

## Slide 10: Frontend — Structure

**Say:**  
“The frontend owns everything in `public/`. We have one HTML file, `index.php`, that contains every view—login, register, book, my bookings, provider, admin—and we show or hide sections with JavaScript. Style is in one CSS file. We have two JS files: `api.js` is the API client that wraps all endpoints; `app.js` holds the SPA logic, state, and form handling. The contract with the backend is simple: the frontend only talks to `/api.php`; there’s no PHP logic in the pages.”

**Key points to stress:**  
- Single HTML shell, multiple “views” toggled by JS.  
- `api.js` = all server communication; `app.js` = UI and state.  
- Docs: API.md and FRONTEND-CONTRACT.md.

---

## Slide 11: Frontend — Single-Page App (app.js)

**Say:**  
“We keep a small state object: current user, current view, cached providers and services, and the selected time slot when booking. We have six views and only one is visible at a time. The header appears only when logged in, and we show provider or admin links based on role. On load we call `auth/me`; if we get a user we set the default view by role—customer goes to book, admin to admin, provider to provider. Otherwise we show login. All forms submit via the API and we show a global success or error message that auto-hides.”

**Key points to stress:**  
- One state object drives the whole UI.  
- Role determines default view and visible nav.  
- `auth/me` on load defines “am I logged in?”

---

## Slide 12: Frontend — Customer: Book & My Bookings

**Say:**  
“On the Book view the user picks a provider, then a service, then a date. They can either choose from predefined available slots we load from the API or enter a custom time for the provider to accept or reschedule. Submitting calls the create-booking API and we refresh the upcoming-bookings preview on the same page. My Bookings lists all their bookings with provider and service names; they can cancel from there, which calls the cancel endpoint and refreshes the list.”

**Key points to stress:**  
- Two ways to book: predefined slot or custom time.  
- Book view has a live “upcoming bookings” preview.  
- My Bookings is the full list with cancel.

---

## Slide 13: Frontend — Provider View (Tabs)

**Say:**  
“The provider area has three tabs. My services lists their services and lets them add, edit, or delete via the provider services API. My time slots is the same for availability—add a date and time range, and update or delete as needed. Incoming bookings shows bookings for them; they can approve, reject, or suggest a new time with the reschedule API. So the provider can fully manage their offerings and calendar and respond to requests.”

**Key points to stress:**  
- Three tabs: services, slots, bookings.  
- Full CRUD on services and slots.  
- Approve, reject, reschedule on each booking.

---

## Slide 14: Frontend — Admin View (Tabs)

**Say:**  
“Admin also has three tabs. Overview shows counts—total users and bookings by status—from the admin overview API. Users lists all users with an option to include soft-deleted; we can update name, role, or soft-delete via the admin users API. All bookings lists every booking and lets the admin approve or reject, using the same booking status endpoint. So admins get a dashboard and full user and booking control.”

**Key points to stress:**  
- Overview = high-level stats.  
- Users = list + edit + soft-delete.  
- All bookings = system-wide list + approve/reject.

---

## Slide 15: Frontend — Notifications & UX

**Say:**  
“There’s a notification icon in the header; clicking opens a dropdown with the list from the notifications API, and the user can mark one or many as read. We show loading state on buttons—‘Please wait…’ and disabled—during any submit. Any API error is shown in the global message bar with the error text from the response, and we use CSS to keep the layout readable and responsive across screens.”

**Key points to stress:**  
- Notifications in header; mark read in place.  
- Loading and errors are handled globally.  
- One message area for success and error.

---

## Slide 16: Frontend — API Client (api.js)

**Say:**  
“The API client uses the same origin and the path `/api.php`. It exposes get, post, patch, and delete with optional query or body. Every request sends credentials so the session cookie is included, and we set JSON content-type when there’s a body. If the response isn’t OK we throw an error with status and the parsed body so app.js can show the backend’s error message. We also expose convenience methods grouped by area—auth, services, bookings, and so on—so the rest of the app stays simple.”

**Key points to stress:**  
- One place for all server calls.  
- Credentials and JSON are set consistently.  
- Errors carry status and body for user-facing messages.

---

## Slide 17: Integration & Testing

**Say:**  
“We documented the API in API.md with every endpoint, method, and body format. The frontend contract is in FRONTEND-CONTRACT.md so both sides know the boundaries. We have a Postman collection for manual testing. Backend unit tests use PHPUnit with mocks so we don’t need a database; we run them with composer test. We also have seed data so you can quickly get a provider and service in the DB for demos.”

**Key points to stress:**  
- API.md = single source of truth for the API.  
- Tests are automated and don’t require DB.  
- Seed data speeds up demos and manual testing.

---

## Slide 18: Summary — Backend Delivered

**Say:**  
“On the backend we delivered a full JSON API with one entry point and path-based routing, complete auth including forgot and reset password, services and time slots with public availability and provider CRUD, and bookings with create, cancel, approve, reject, and reschedule with overlap prevention. We added notifications for status changes, admin overview and user management with soft delete, and middleware for auth and roles. We also delivered the database schema, migrations, seed, and documentation.”

**Key points to stress:**  
- One API, all features behind it.  
- Overlap prevention and notifications are core.  
- Admin and provider capabilities are fully supported.

---

## Slide 19: Summary — Frontend Delivered

**Say:**  
“On the frontend we delivered a single-page app with one HTML file and view-based navigation, login and register with role-based default view, the full book flow with predefined and custom slots and an upcoming preview, My Bookings with cancel, the provider area for services, slots, and incoming bookings with approve/reject/reschedule, and the admin area for overview, users, and all bookings. We have notifications in the header, a centralized API client, and consistent loading and error handling with a responsive layout.”

**Key points to stress:**  
- One SPA for all roles.  
- Each role gets the right views and actions.  
- API client and UX patterns are consistent.

---

## Slide 20: Thank You / Q&A

**Say:**  
“So that’s ehist-sabs: a Smart Appointment Booking System with a PHP and MySQL backend, a REST-style API, and a single-page frontend. We have auth, roles, bookings with conflict prevention, notifications, and full provider and admin tools. Everything is documented and testable. You can run it with `php -S localhost:8000 -t public` and open localhost:8000. Happy to take questions.”

**Key points to stress:**  
- Recap: backend + frontend + docs + tests.  
- One command to run.  
- Invite questions on any part of the stack.
