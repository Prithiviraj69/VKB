========================================================================
                 VISHWAKARMA BANDHU (VKB) - MOBILE APP
========================================================================
VKB Mobile Application is a cross-platform companion app built using Flutter
and Dart. It provides a complete interface for managing, verifying, and 
approving beneficiaries, NGOs, employers, contractors, and schemes.
------------------------------------------------------------------------
1. SYSTEM ROLES & TEST CREDENTIALS
------------------------------------------------------------------------
The system supports multiple roles, each dynamically presenting scoped dashboards, 
actions, and menus matching the main Web Portal:
a) OWNER DEPARTMENT ADMIN (Full Oversight, Stats, Schemes, Registries)
   - Email: jbc@gmail.com
   - Password: password
b) GOVERNMENT DEPARTMENT ADMIN (Scoped Department Oversight, Stats, Registries)
   - Email: industryadmin@gmail.com
   - Password: password
c) DEPARTMENT VERIFIER (Reviews Worker Profiles, Verifies/Rejects Workers)
   - Email: verifier@gmail.com
   - Password: password
d) DEPARTMENT APPROVER (Reviews Verified Worker Profiles, Issues Reg No)
   - Email: approver@gmail.com
   - Password: password
e) NGO ADMIN (Onboards NGO Staff, Employers, Contractors, and Workers)
   - Email: ngo@gmail.com
   - Password: password
f) NGO VERIFIER (Verifies NGO Scheme Applications)
   - Email: ngoverifier@gmail.com
   - Password: password
g) NGO APPROVER (Approves NGO Scheme Applications, Direct Worker Approvals)
   - Email: ngoapprover@gmail.com
   - Password: password
h) EMPLOYER (Onboards Contractors, Registers Beneficiaries)
   - Email: employer@gmail.com
   - Password: password
i) CONTRACTOR (Registers Beneficiaries / Workers)
   - Email: contractor@gmail.com
   - Password: password
------------------------------------------------------------------------
2. HOW TO RUN THE APP
------------------------------------------------------------------------
Prerequisites:
- Flutter SDK (3.x.x stable recommended)
- Android Studio / VS Code with Flutter extension
- Connected physical device or emulator
Steps to Run:
1. Open terminal inside the 'app' directory:
   cd c:/Users/Moulik/Downloads/website/Vishwakarma Bandhu/app
2. Fetch flutter packages:
   flutter pub get
3. Run the application:
   flutter run
------------------------------------------------------------------------
3. FEATURES & RECENT SYNCHRONIZATIONS
------------------------------------------------------------------------
- Dynamic Date Pickers: Formatted DD-MM-YYYY date pickers in worker registration.
- Ration Card OCR Integration: Scans ration card, extracts members, and saves family inline in MongoDB collection.
- Unified Verification Lifecycles: Full sync between mobile and WebApp verify/approve.
- Automated API link propagation: Ensures ngo_id links properly across nested hierarchies.
========================================================================
