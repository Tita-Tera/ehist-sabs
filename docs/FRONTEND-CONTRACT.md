# Frontend / Backend contract

**The frontend team owns the entire `public/` folder** (HTML, CSS, JS, assets, `index.php` or `index.html`, login/dashboard pages, etc.). Replace or add any files there as needed.

**The backend exposes a single API** that the frontend calls for all data and auth. No backend logic runs in your pages.

---

## API only

- **Base URL:** Same origin as the site, path: **`/api.php`**  
  Examples: `https://yoursite.com/api.php`, `http://localhost:8000/api.php`
- **Full reference:** [API.md](API.md) — endpoints, methods, request/response formats.

**Check API is up:**  
`GET /api.php` or `GET /api.php/health` → `{"api":"ehist-sabs","status":"ok",...}`

---

## What the API provides

- **Auth:** register, login, logout, current user (`/api.php/auth/*`). Session cookie is set on login; send it with subsequent requests (same-origin or with credentials).
- **Services:** list providers, list services by provider (`/api.php/services/*`).
- **Bookings:** list, create, cancel, approve/reject (`/api.php/bookings/*`) — requires auth.

All request/response bodies are **JSON**. Set `Content-Type: application/json` for POST/PATCH.

---

## CORS

The API sends `Access-Control-Allow-Origin: *` and allows common methods/headers so the frontend can call it from another port or domain during development. For production, the backend can restrict the origin.

---

You do not need to change any file under `app/` or `app/config/`. Use only the API and the docs above.
