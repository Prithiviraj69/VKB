# Vishwakarma Bandhu (VKB) - Web Administration Portal & APIs

This folder contains the Laravel-based backend and web administration portal for the Vishwakarma Bandhu project. It manages the registration pipelines, verifications, and welfare scheme allocations for artisans, while also serving REST APIs for the artisan mobile application.

---

## 🛠️ Tech Stack & Key Configs

* **Framework:** Laravel 11.x
* **Database:** MongoDB (using `mongodb/laravel-mongodb`)
* **Requirements:** PHP 8.2 or 8.3 with the `mongodb` extension.

---

## 📂 Codebase Directory Overview

* **`app/Http/Controllers/Web/`**: Contains dashboard controllers managing administrative web portals (Super Admin, Owner Department, Govt Departments, NGOs, Contractors, Employers).
* **`app/Http/Controllers/Api/`**: Serves endpoints for mobile app login, onboarding, profiles, location services, and scheme applications.
* **`app/Models/`**: MongoDB-compatible Eloquent models (e.g. `User`, `Worker`, `WorkerFamily`, `Scheme`, `SchemeApplication`, `Trade`).
* **`resources/views/`**: Blades for registration workflows, verifications, dashboard listings, and audit logs.
* **`routes/web.php` & `routes/api.php`**: Routing declarations for administrative portal views and API services.

---

## ⚙️ How to Run Locally

1. Install Composer dependencies:
   ```bash
   composer install
   ```
2. Copy configuration environment variables:
   ```bash
   cp .env.example .env
   ```
3. Set your MongoDB connection URI under `DB_URI` in `.env`.
4. Generate the application key:
   ```bash
   php artisan key:generate
   ```
5. Seed initial data (default users, departments, trades, systems):
   ```bash
   php artisan db:seed
   ```
6. Run the local server:
   ```bash
   php artisan serve
   ```
