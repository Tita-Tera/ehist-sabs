# API reference (Postman / Frontend)

**Base URL:** `http://localhost:8000/api.php` (or your deployed origin + `/api.php`)

**Health check:** `GET /api.php` or `GET /api.php/health` → `{"api":"ehist-sabs","status":"ok",...}`

**Headers for JSON body:** `Content-Type: application/json`

**Auth:** Login/register set a session cookie. Postman sends cookies automatically—after login, use the same Postman session for protected routes.

---

## Quick test: Register & Login in Postman

1. **Start the server** (and ensure MySQL is running + schema imported):
   ```bash
   php -S localhost:8000 -t public
   ```

2. **Import the collection** (optional): In Postman → Import → Upload `docs/postman-collection.json`. Use the **Auth → Register** and **Auth → Login** requests.

3. **Register**
   - **POST** `http://localhost:8000/api.php/auth/register`
   - **Headers:** `Content-Type: application/json`
   - **Body (raw, JSON):**
     ```json
     {"email":"test@example.com","password":"password123","name":"Test User"}
     ```
   - Expected: `200` → `{"success":true,"user_id":1}` (or similar). If you get `503` or DB error, create the database and run `database/schema.sql`.

4. **Login**
   - **POST** `http://localhost:8000/api.php/auth/login`
   - **Headers:** `Content-Type: application/json`
   - **Body (raw, JSON):**
     ```json
     {"email":"test@example.com","password":"password123"}
     ```
   - Expected: `200` → `{"success":true,"user":{"id":1,"email":"test@example.com","name":"Test User","role_id":3}}`. Postman will store the session cookie.

5. **Me** (optional): **GET** `http://localhost:8000/api.php/auth/me` — should return the same user while the session is valid.

---

## Quick test: Bookings in Postman

Bookings require a **logged-in customer** and at least one **provider** and **service** in the DB.

1. **Seed a provider + service** (once):
   ```bash
   mysql -u root -p ehist_sabs < database/seed.sql
   ```
   This adds `provider@example.com` (password: `provider123`) and a "Consultation" service. Get real ids with **GET** `/api.php/services/providers` and **GET** `/api.php/services/2` (use the provider id you see).

2. **Login as a customer** (Auth → Login with your test customer). Keep the same Postman session (cookie).

3. **List bookings**  
   **GET** `http://localhost:8000/api.php/bookings`  
   Expected: `200` → `{"bookings":[]}` at first.

4. **Create a booking**  
   **POST** `http://localhost:8000/api.php/bookings`  
   **Headers:** `Content-Type: application/json`  
   **Body (raw, JSON):** use real `provider_id` and `service_id` from step 1 (e.g. `2` and `1` if you ran seed as-is):
   ```json
   {"provider_id":2,"service_id":1,"slot_date":"2026-03-15","start_time":"09:00:00","end_time":"09:30:00"}
   ```
   Expected: `200` → `{"success":true,"booking_id":1}`.

5. **List bookings again** — you should see the new booking.

6. **Cancel a booking**  
   **POST** `http://localhost:8000/api.php/bookings/1/cancel`  
   (Use the real booking id from the list.) Expected: `200` → `{"success":true}`.

7. **Approve/Reject** (admin only): **POST** `/api.php/bookings/1/status` with body `{"status":"approved"}` or `{"status":"rejected"}`. Requires logging in as an admin user.

**Admin – list/manage users:** Log in as an administrator, then **GET** `/api.php/admin/users` to list users; **PATCH** `/api.php/admin/users/2` with body `{"name":"New Name"}`, `{"role_id":2}`, or `{"deleted_at":true}` to update or soft-delete. Use `?deleted=1` to include soft-deleted users in the list.

---

## 1. Start the server (Ubuntu)

From the project root:

```bash
cd /home/tita-tera-official/dev/ehist-sabs
php -S localhost:8000 -t public
```

Keep this terminal open. API is at `http://localhost:8000/api.php/...`.

---

## 2. Endpoints

### Auth (no auth required)

| Method | URL | Body (raw JSON) | Notes |
|--------|-----|-----------------|--------|
| **POST** | `http://localhost:8000/api.php/auth/register` | `{"email":"user@example.com","password":"secret123","name":"Jane Doe"}` | Creates customer. |
| **POST** | `http://localhost:8000/api.php/auth/login` | `{"email":"user@example.com","password":"secret123"}` | Sets session cookie. |
| **GET** | `http://localhost:8000/api.php/auth/me` | — | Current user (requires cookie). |
| **POST** | `http://localhost:8000/api.php/auth/logout` | — | Clears session. |
| **POST** | `http://localhost:8000/api.php/auth/forgot-password` | `{"email":"user@example.com"}` | Request reset; returns `token` (for testing; in production send by email). |
| **POST** | `http://localhost:8000/api.php/auth/reset-password` | `{"token":"...","password":"newpass123"}` | Reset password (token from forgot-password). |

**Validation:** Register/login require valid email; register requires password ≥6 chars. Bookings require `slot_date` Y-m-d and `start_time`/`end_time` H:i or H:i:s.

### Services (no auth required)

| Method | URL | Body | Notes |
|--------|-----|------|--------|
| **GET** | `http://localhost:8000/api.php/services/providers` | — | List all service providers. |
| **GET** | `http://localhost:8000/api.php/services/1` | — | Services for provider id `1`. |

### Provider: own services (auth required, role `service_provider`)

Logged-in provider can create, update, and delete only their own services.

| Method | URL | Body (raw JSON) | Notes |
|--------|-----|-----------------|--------|
| **GET** | `http://localhost:8000/api.php/provider/services` | — | List current provider’s services. |
| **POST** | `http://localhost:8000/api.php/provider/services` | `{"name":"Consultation","description":"30 min session","duration_min":30}` | Create service. `name` required; `duration_min` 1–1440, default 60. |
| **PATCH** | `http://localhost:8000/api.php/provider/services/1` | `{"name":"Updated name","description":"...","duration_min":45}` | Update service (only if owned by current provider). Partial body allowed. |
| **DELETE** | `http://localhost:8000/api.php/provider/services/1` | — | Delete service (only if owned by current provider). |

- **403** – `{"error":"Insufficient permissions"}` if user is not a service provider.
- **404** – `{"error":"Not found"}` when updating/deleting a service that does not exist or belongs to another provider.

### Time slots

| Method | URL | Body | Notes |
|--------|-----|------|--------|
| **GET** | `http://localhost:8000/api.php/time-slots/available?provider_id=1&date=2026-03-15` | — | Available slots for a provider (no auth). Omit `date` for today. |
| **GET** | `http://localhost:8000/api.php/time-slots` | — | List current provider’s slots (auth, provider). Optional: `date_from`, `date_to`. |
| **POST** | `http://localhost:8000/api.php/time-slots` | `{"slot_date":"2026-03-15","start_time":"09:00:00","end_time":"10:00:00"}` | Create slot (auth, provider). |
| **PATCH** | `http://localhost:8000/api.php/time-slots/1` | `{"slot_date":"2026-03-16","is_available":0}` | Update slot (auth, provider or admin). |
| **DELETE** | `http://localhost:8000/api.php/time-slots/1` | — | Delete slot (auth, provider or admin). |

### Bookings (auth required: login first, then same Postman session)

| Method | URL | Body (raw JSON) | Notes |
|--------|-----|-----------------|--------|
| **GET** | `http://localhost:8000/api.php/bookings` | — | List bookings for current user. |
| **POST** | `http://localhost:8000/api.php/bookings` | `{"provider_id":1,"service_id":1,"slot_date":"2026-03-10","start_time":"09:00:00","end_time":"10:00:00"}` | Create booking (customer). |
| **POST** | `http://localhost:8000/api.php/bookings/1/cancel` | — | Cancel booking id 1. |
| **POST** | `http://localhost:8000/api.php/bookings/1/status` | `{"status":"approved"}` or `{"status":"rejected"}` | Admin only. |

**Pagination:** `GET /bookings` accepts query `?limit=20&offset=0` (default limit 50, max 100).

### Notifications (auth required)

| Method | URL | Body | Notes |
|--------|-----|------|--------|
| **GET** | `http://localhost:8000/api.php/notifications` | — | List current user’s notifications. `?unread_only=1` for unread only. |
| **POST** | `http://localhost:8000/api.php/notifications/read` | `{"ids":[1,2]}` | Mark notifications as read. |
| **POST** | `http://localhost:8000/api.php/notifications/1/read` | — | Mark notification id 1 as read. |

Notifications are created when a booking is approved, rejected, or cancelled (for the customer).

### Admin (auth required, role `administrator`)

| Method | URL | Body | Notes |
|--------|-----|------|--------|
| **GET** | `http://localhost:8000/api.php/admin/overview` | — | Counts: `users_total`, `bookings` by status (pending, approved, rejected, cancelled). |
| **GET** | `http://localhost:8000/api.php/admin/users` | — | List all users. `?deleted=1` to include soft-deleted. |
| **PATCH** | `http://localhost:8000/api.php/admin/users/2` | `{"name":"..."}`, `{"role_id":1\|2\|3}`, `{"deleted_at":true}` | Update or soft-delete user. |

---

## 3. Postman setup

1. **New request** → set method and URL (e.g. `POST` `http://localhost:8000/api.php/auth/login`).
2. **Headers:** add `Content-Type: application/json` for POST with body.
3. **Body:** choose **raw** → **JSON**, paste the JSON (e.g. for login: `{"email":"user@example.com","password":"secret123"}`).
4. **Cookies:** leave default (Postman will store and send the session cookie after login).
5. **Order to test:** Register → Login → (optional) GET auth/me → GET services/providers → GET bookings or POST bookings.

---

## 4. Example responses

- **200** – `{"success":true,"user":{...}}` (login) or `{"bookings":[...]}` (list).
- **400** – `{"error":"Email and password required"}`.
- **401** – `{"error":"Authentication required"}` or `{"error":"Invalid credentials"}`.
- **404** – `{"error":"Not found"}`.
- **503** – `{"error":"Database unavailable",...}` when MySQL is not running.
