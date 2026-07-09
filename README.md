# Vishwakarma Bandhu (VKB)

Vishwakarma Bandhu is a state-of-the-art digital platform designed to facilitate the onboarding, verification, and registration of artisans (beneficiaries) and manage their eligibility for various welfare schemes. The system consists of a web administration portal and a mobile application, working in unison over a unified MongoDB database.

---

## 📂 Repository Structure

The repository is structured logically to separate project assets, documentation, and the codebases for different platforms:

```text
├── Code/
│   ├── WebApp/             # Laravel Web Administration Portal & API Services
│   └── MobileApp/          # Flutter Mobile Application for Artisans
├── Documents/              # Excel sheets, project templates, and financial forms
├── Resources/              # Shared assets and project resources
├── Test-plan/              # Test plans and verification checklists
├── User Manual/            # User guides and onboard walk-through manuals
└── README.md               # Main project documentation
```

---

## 🚀 Key Web Application Features

1. **Ration Card PDF Verification (PDS Integration):**
   * Automatically parses and verifies uploaded Ration Card PDFs.
   * Extracts family member names, ages, relationships, and genders automatically to minimize manual data entry.

2. **Dual-Sync Family Database Design:**
   * To support both nested document representations (Web Portal) and relational query matching (Mobile App), family members are written to:
     * The `family_members` nested array inside the `workers` collection.
     * Individual records in the `worker_family` collection (referencing `worker_id`).
   * Read operations seamlessly fallback to and fetch from both collections, ensuring full compatibility between web and mobile-registered users.

3. **Multi-Role Administration Portal:**
   * **Super Admin:** General dashboard, department onboarding, trades/profiles management.
   * **Owner Department Admin:** Full control over registration approvals, verifications, and reporting.
   * **Govt Department / NGO / Contractor / Employer:** Localized onboarding pipelines for beneficiaries with custom verification status indicators.
   * **Verifier / Approver:** Auditing and validation screens for scheme applications.

4. **Security & Auditing:**
   * Real-time activity logs tracked in the `activity_logs` collection.
   * Secure, signed URL serving for protected files (e.g. signatures, profile photos, documents).

---

## 🛠️ Technology Stack

* **Backend Framework:** Laravel 11.x
* **Language Runtime:** PHP 8.3
* **Database:** MongoDB
* **Theme & UI:** Custom Vanilla CSS & Javascript with dynamic responsive layouts
* **Mobile Runtime:** Flutter (Dart)

---

## 💻 Web App Installation & Local Setup

To set up and run the Web Administration portal locally, follow these steps:

### Prerequisites
* **PHP 8.2 or 8.3** (with `mongodb` extension enabled)
* **Composer** (PHP dependency manager)
* **MongoDB Server** (Local instance or MongoDB Atlas cluster connection URI)

### Setup Steps
1. Navigate to the WebApp directory:
   ```bash
   cd Code/WebApp
   ```
2. Copy the environment template file and configure your credentials:
   ```bash
   cp .env.example .env
   ```
   * *Note: Ensure you configure `DB_URI` or `MONGODB_URI` with your MongoDB connection details, and `APP_URL` to match your local server environment.*

3. Install PHP dependencies using Composer:
   ```bash
   composer install
   ```
4. Generate the application key:
   ```bash
   php artisan key:generate
   ```
5. Run the database seeders to populate initial roles, departments, trades, and a default Super Admin account:
   ```bash
   php artisan db:seed
   ```
6. Start the local development server:
   ```bash
   php artisan serve
   ```
   * *By default, the application will be hosted at `http://127.0.0.1:8000`.*

---

## 🧹 Database Maintenance Utilities

### Family Member Synchronization
Existing legacy records registered via the web application (where family members resided exclusively in the inline `family_members` array) can be synchronized to the `worker_family` collection for mobile app visibility by running the sync utility script:
```bash
php Code/WebApp/scratch/heal_family_members.php
```
This script scans the `workers` collection, filters out duplicates, and registers any missing members in the `worker_family` database collection.