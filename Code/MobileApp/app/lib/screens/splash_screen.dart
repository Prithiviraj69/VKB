import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import 'login_screen.dart';
import 'dashboard_screen.dart';
import 'profile_screen.dart';
import 'institution_dashboard_screen.dart';
import 'owner_dept_admin_dashboard_screen.dart';
import 'verifier_approver_dashboard_screen.dart';
import 'ngo_dashboard_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    );
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeIn),
    );
    _controller.forward();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    final apiService = ApiService();
    await Future.delayed(const Duration(milliseconds: 2500));

    // ── Step 1: No stored token → always go to Login ───────────────────────
    if (!await apiService.isLoggedIn()) {
      _goTo(const LoginScreen());
      return;
    }

    final prefs = await SharedPreferences.getInstance();
    final role = prefs.getString('user_role') ?? '';

    // ── Step 2: Block super-admin from mobile app ──────────────────────────
    if (role == 'super-admin') {
      await apiService.clearToken();
      _goTo(const LoginScreen());
      return;
    }

    // ── Step 3: Institutional / dept roles → their dashboards ─────────────
    if (role.isNotEmpty && role != 'beneficiary') {
      switch (role) {
        case 'owner-dept-admin':
          _goTo(const OwnerDeptAdminDashboardScreen());
          break;
        case 'dept-verifier':
        case 'dept-approver':
        case 'ngo-verifier':
        case 'ngo-approver':
          _goTo(const VerifierApproverDashboardScreen());
          break;
        case 'ngo':
        case 'employer':
        case 'contractor':
          _goTo(const NgoDashboardScreen());
          break;
        default:
          _goTo(const InstitutionDashboardScreen());
      }
      return;
    }

    // ── Step 4: Beneficiary — validate token by calling getProfile ─────────
    // This ensures a force-closed / stale session always hits the server.
    // If the token is invalid, server returns Unauthenticated → go to Login.
    final res = await apiService.getProfile();

    if (!mounted) return;

    final isSuccess = res['success'] == true;
    final isAuthError = !isSuccess ||
        res['message'] == 'Unauthenticated.' ||
        res['status'] == 401;

    if (isAuthError) {
      // Session expired or invalid token → clear and go to Login
      await apiService.clearToken();
      _goTo(const LoginScreen());
      return;
    }

    // ── Step 5: Route based on profile completeness ────────────────────────
    // getProfile returns has_profile=true when worker record exists and
    // either job_profile is filled OR worker was dept-registered.
    final hasProfile = res['has_profile'] == true;

    if (hasProfile) {
      _goTo(const DashboardScreen());
    } else {
      // New self-registered beneficiary who hasn't completed their profile
      _goTo(const ProfileScreen(isFirstTime: true));
    }
  }

  /// Helper: navigate to [screen], replacing the splash screen.
  void _goTo(Widget screen) {
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => screen),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        color: const Color(0xFFE65100),
        child: FadeTransition(
          opacity: _fadeAnimation,
          child: Image.asset(
            'assets/images/splash.png',
            fit: BoxFit.cover,
            width: double.infinity,
            height: double.infinity,
          ),
        ),
      ),
    );
  }
}
