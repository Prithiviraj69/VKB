# Vishwakarma Bandhu (VKB) - Mobile Application

This folder contains the Flutter-based cross-platform mobile application for the Vishwakarma Bandhu project. It provides an on-the-go interface for artisans, contractors, employers, NGOs, and department verifiers/approvers to manage registrations, track scheme applications, and complete verification pipelines.

---

## 🛠️ Tech Stack & Key Configs

* **Framework:** Flutter 3.x (Dart)
* **API Consumer:** REST client using the `http` package
* **State & Local Storage:** `shared_preferences` (for sessions and local configurations)
* **System Requirements:** Flutter SDK installed on local machine, physical device or emulator for testing.

---

## 📂 Codebase Directory Overview

* **`lib/screens/`**: Contains Flutter UI screens and dashboards (e.g. `login_screen.dart`, `owner_dept_admin_dashboard_screen.dart`, `verifier_approver_dashboard_screen.dart`, `ngo_dashboard_screen.dart`).
* **`lib/services/`**: Contains API adapters (e.g. `api_service.dart` which encapsulates authentication, onboarding, worker registries, verification pipelines, and scheme endpoints).
* **`lib/models/`**: Defines Dart data models and JSON serialization logic (e.g. `models.dart` for mapping scheme configurations, worker cards, and families).
* **`lib/main.dart`**: The application entry point managing routing configurations, themes, and initial state checks.

---

## ⚙️ How to Run Locally

1. Install Flutter dependencies:
   ```bash
   flutter pub get
   ```
2. Open `lib/services/api_service.dart` and configure the base URL to match your backend API server:
   ```dart
   static const String baseUrl = 'http://YOUR_LOCAL_BACKEND_IP:8833/api';
   ```
3. Check connected devices (emulator, simulator, or physical hardware):
   ```bash
   flutter devices
   ```
4. Run the local application on your target device:
   ```bash
   flutter run
   ```
