# 📌 ehist-sabs (Smart Appointment Booking System)

## 📝 Project Description

**ehist-sabs** is a **web-based appointment booking system** designed to simplify scheduling between customers and service providers.
It provides a structured, secure, and efficient way for users to book appointments, for providers to manage their schedules, and for administrators to oversee the system.

Key features include:

* Role-based access: **Administrator**, **Service Provider**, **Customer**
* Secure authentication and session management
* Appointment booking with conflict prevention
* Real-time notifications for booking status
* Fully responsive frontend using **Bootstrap**
* Scalable and maintainable backend built with **PHP OOP**
* Persistent storage using **MySQL**

This project was developed as part of a team assignment for **[Your Course Name]** and demonstrates the complete software development lifecycle: design, modeling, implementation, testing, and documentation.

---

## 🧰 Technology Stack

* **Frontend:** HTML, CSS, Bootstrap, JavaScript, AJAX
* **Backend:** PHP (Object-Oriented)
* **Database:** MySQL
* **Version Control & Deployment:** GitHub, Vercel / Netlify

---

## 👥 Team Roles

| Team Member  | Responsibilities                                                                               |
| ------------ | ---------------------------------------------------------------------------------------------- |
| **Tera**     | Backend logic & APIs, System architecture, PHP OOP design, Middleware, Security                |
| **Emmanuel** | Database schema & design, MySQL tables, constraints, indexing, sample data, query optimization |
| **Anah**     | System design & modeling, Use case and class diagrams, Activity diagrams                       |
| **Ngang**    | Frontend development support, HTML, CSS, Bootstrap, assist with GitHub workflow                |

> Each member focuses on their area of expertise to ensure efficient development within the project timeline.

---

## 📂 Folder Structure

```plaintext
ehist-sabs/
│
├── app/                  # Backend logic (PHP OOP)
│   ├── config/           # Database connection, configuration
│   ├── Models/           # Database models (User, Booking, Service, etc.)
│   ├── Controllers/      # Handles AJAX requests
│   ├── Services/         # Business logic
│   └── Middleware/       # Authentication & role-based access
│
├── public/               # Frontend & API entry (SPA + API)
│   ├── index.php         # Single-page app (login, book, my bookings, provider, admin)
│   ├── api.php           # REST-style API entry (routes to app/)
│   ├── style.css
│   ├── app.js
│   ├── api.js
│   └── .htaccess
│
├── database/             # Schema, migrations, seed data
│   ├── schema.sql
│   ├── seed.sql
│   └── migrations/
│
├── docs/                 # Project documentation
│   ├── API.md
│   ├── FRONTEND-CONTRACT.md
│   ├── apache-setup.md
│   └── postman-collection.json
│
├── presentation/         # Speaker notes & Q&A for defense/presentation
│
├── README.md             # Project description & instructions
└── .gitignore            # Files to ignore in GitHub
```

---

## 🚀 Workflow & Development Process

1. **Branch Strategy**

   * `main` → Production-ready code
   * `develop` → Integration branch
   * `feature-*` → Each new feature

2. **Daily Workflow**

   * Pull latest `develop`
   * Switch to your feature branch
   * Commit changes regularly
   * Push and create Pull Request (PR)
   * Code review by Tera (or assigned reviewer)
   * Merge into `develop`

3. **Two-Week Sprint Overview**

   * Week 1: Requirements, database setup, frontend layout, authentication
   * Week 2: Booking logic, admin dashboard, notifications, integration, testing, documentation, final review

---

## 🛠 System Features

### Customer

* Register / Login
* View service providers
* Book appointments
* Cancel appointments
* View booking history

### Service Provider

* Define available time slots
* View upcoming appointments
* Cancel availability
* Manage their schedule

### Administrator

* Manage users
* Approve / Reject bookings
* View system overview (bookings, users)
* Monitor notifications

### System Rules

* No double booking
* No overlapping appointments
* Secure authentication & role-based access
* Passwords stored hashed

---

## 📄 Documentation

All project documentation is located in the `docs/` folder:

1. **Requirements** – Functional and non-functional requirements
2. **Analysis** – Problem analysis and proposed system
3. **Design** – ER diagrams, use case, activity, class diagrams
4. **Testing** – Test cases, bug logs, test results
5. **User Manual** – Step-by-step guide with screenshots

---

## 🔧 Backend setup

1. **Copy environment file:** `cp .env.example .env` and set your database credentials.
2. **Create database and import schema:**  
   `mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ehist_sabs;"`  
   `mysql -u root -p ehist_sabs < database/schema.sql`
3. **Run the app:**  
   - **PHP built-in server:** `php -S localhost:8000 -t public` → `http://localhost:8000`  
   - **Apache + MySQL:** see [docs/apache-setup.md](docs/apache-setup.md) for VirtualHost and permissions.

**API entry:** `public/api.php`  
- `POST /api.php/auth/login` — login  
- `POST /api.php/auth/register` — register  
- `GET /api.php/auth/me` — current user  
- `POST /api.php/auth/logout` — logout  
- `GET /api.php/services/providers` — list providers  
- `GET /api.php/services/{id}` — services by provider  
- `GET /api.php/bookings` — list bookings (auth)  
- `POST /api.php/bookings` — create booking (auth)  
- `POST /api.php/bookings/{id}/cancel` — cancel (auth)  
- `POST /api.php/bookings/{id}/status` — approve/reject (admin, body: `{"status":"approved"}`)

---

## 🧪 Testing

Unit tests use **PHPUnit**. No database is required (tests mock dependencies).

```bash
composer install
composer test
```

Or run PHPUnit directly:

```bash
./vendor/bin/phpunit
```

Tests live in `tests/Unit/` and cover auth (register, login attempt) and booking (create validation, overlap, permissions).

---

## 💻 Deployment

The project can be deployed on **Vercel** or **Netlify** for frontend and **PHP server with MySQL** for backend.
Ensure the database is imported from `database/schema.sql`.

---

## 📝 Contribution Guidelines

* Commit often with meaningful messages
* Push to your assigned feature branch
* Review code before merging into `develop`
* Communicate changes to the team if database schema or APIs are updated
* Keep frontend & backend separated
* Keep folder structure consistent

---

## ⚡ Contact / Team

For questions or clarifications, reach out to **Tera** (Project Lead) or the assigned team member responsible for the specific module.