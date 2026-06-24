import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'login_screen.dart';
import 'schemes_screen.dart';
import 'grievance_screen.dart';
import 'profile_screen.dart';
import 'documents_screen.dart';
import 'notifications_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final _apiService = ApiService();
  bool _isLoading = true;
  Map<String, dynamic> _dashboardData = {};

  @override
  void initState() {
    super.initState();
    _fetchDashboard();
  }

  Future<void> _fetchDashboard() async {
    setState(() => _isLoading = true);
    final res = await _apiService.getDashboard();
    if (mounted) {
      setState(() {
        _isLoading = false;
        if (res['success'] == true) {
          _dashboardData = res['data'] ?? {};
        }
      });
    }
  }

  Future<void> _handleLogout() async {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Logout', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('No', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.of(ctx).pop();
              await _apiService.logout();
              if (mounted) {
                Navigator.of(context).pushReplacement(
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE65100)),
            child: const Text('Yes', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final hasProfile = _dashboardData['has_profile'] ?? false;
    final profileScore = _dashboardData['profile_score'] ?? 0;
    final worker = _dashboardData['worker'];
    final workerName = worker != null ? worker['full_name'] : 'Dear User';
    final workerCode = worker != null ? worker['worker_code'] : 'Profile Incomplete';

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Vishwakarma Bandhu',
          style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
        ),
        centerTitle: true,
        backgroundColor: const Color(0xFFE65100),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_active_rounded),
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const NotificationsScreen()),
              ).then((_) => _fetchDashboard());
            },
          ),
        ],
      ),
      drawer: Drawer(
        child: Column(
          children: [
            UserAccountsDrawerHeader(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFFE65100), Color(0xFFFF8F00)],
                ),
              ),
              currentAccountPicture: Container(
                decoration: const BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.person_rounded,
                  size: 48,
                  color: Color(0xFFE65100),
                ),
              ),
              accountName: Text(
                workerName,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
              ),
              accountEmail: Text(
                workerCode,
                style: const TextStyle(color: Color(0xFFB0BEC5)),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.dashboard_rounded, color: Color(0xFFE65100)),
              title: const Text('Dashboard'),
              onTap: () => Navigator.of(context).pop(),
            ),
            ListTile(
              leading: const Icon(Icons.person_outline_rounded, color: Color(0xFFE65100)),
              title: const Text('My Profile'),
              onTap: () {
                Navigator.of(context).pop();
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const ProfileScreen()),
                ).then((_) => _fetchDashboard());
              },
            ),
            ListTile(
              leading: const Icon(Icons.assignment_rounded, color: Color(0xFFE65100)),
              title: const Text('Item01'),
              onTap: () {
                Navigator.of(context).pop();
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const SchemesScreen()),
                ).then((_) => _fetchDashboard());
              },
            ),
            ListTile(
              leading: const Icon(Icons.feedback_rounded, color: Color(0xFFE65100)),
              title: const Text('Item02'),
              onTap: () {
                Navigator.of(context).pop();
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const GrievanceScreen()),
                ).then((_) => _fetchDashboard());
              },
            ),
            ListTile(
              leading: const Icon(Icons.folder_open_rounded, color: Color(0xFFE65100)),
              title: const Text('Item03'),
              onTap: () {
                Navigator.of(context).pop();
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const DocumentsScreen()),
                ).then((_) => _fetchDashboard());
              },
            ),
            const Divider(),
            ListTile(
              leading: const Icon(Icons.exit_to_app_rounded, color: Colors.red),
              title: const Text('Logout', style: TextStyle(color: Colors.red)),
              onTap: _handleLogout,
            ),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)))
          : RefreshIndicator(
              onRefresh: _fetchDashboard,
              color: const Color(0xFFE65100),
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Gradient Profile Complete Card
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFE65100), Color(0xFFFF8F00)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFFE65100).withOpacity(0.3),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Hello, $workerName!',
                                  style: const TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  'Code: $workerCode',
                                  style: const TextStyle(
                                    fontSize: 14,
                                    color: Color(0xFFEEEEEE),
                                  ),
                                ),
                                const SizedBox(height: 16),
                                ElevatedButton(
                                  onPressed: () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(builder: (_) => const ProfileScreen()),
                                    ).then((_) => _fetchDashboard());
                                  },
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.white,
                                    foregroundColor: const Color(0xFFE65100),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                  ),
                                  child: Text(hasProfile ? 'Edit Details' : 'Complete Profile'),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 16),
                          // Circle indicator of completion
                          Stack(
                            alignment: Alignment.center,
                            children: [
                              SizedBox(
                                width: 80,
                                height: 80,
                                child: CircularProgressIndicator(
                                  value: profileScore / 100,
                                  backgroundColor: Colors.white24,
                                  color: Colors.white,
                                  strokeWidth: 8,
                                ),
                              ),
                              Text(
                                '$profileScore%',
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                    
                    // Alert if profile incomplete
                    if (!hasProfile)
                      Container(
                        width: double.infinity,
                        margin: const EdgeInsets.only(bottom: 20),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.red.shade50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.red.shade200),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.warning_amber_rounded, color: Colors.red.shade700, size: 28),
                            const SizedBox(width: 12),
                            const Expanded(
                              child: Text(
                                'Please complete your profile and Aadhaar verification to apply for government schemes.',
                                style: TextStyle(color: Color(0xFF7F1D1D), fontSize: 13, fontWeight: FontWeight.w500),
                              ),
                            ),
                          ],
                        ),
                      ),

                    const Text(
                      'Quick Services',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF374151),
                      ),
                    ),
                    const SizedBox(height: 12),
                    
                    // Grid View Services
                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      crossAxisSpacing: 16,
                      mainAxisSpacing: 16,
                      children: [
                        _buildMenuCard(
                          icon: Icons.assignment_rounded,
                          title: 'Item01',
                          subtitle: 'Check eligibility and apply',
                          badgeCount: _dashboardData['pending_schemes'],
                          color: const Color(0xFFE65100),
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => const SchemesScreen()),
                          ).then((_) => _fetchDashboard()),
                        ),
                        _buildMenuCard(
                          icon: Icons.feedback_rounded,
                          title: 'Item02',
                          subtitle: 'File complaint and track status',
                          badgeCount: _dashboardData['open_grievances'],
                          color: const Color(0xFF0288D1),
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => const GrievanceScreen()),
                          ).then((_) => _fetchDashboard()),
                        ),
                        _buildMenuCard(
                          icon: Icons.folder_open_rounded,
                          title: 'Item03',
                          subtitle: 'Upload Aadhaar, PAN and more',
                          badgeCount: _dashboardData['total_documents'],
                          color: const Color(0xFF43A047),
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => const DocumentsScreen()),
                          ).then((_) => _fetchDashboard()),
                        ),
                        _buildMenuCard(
                          icon: Icons.person_rounded,
                          title: 'Item04',
                          subtitle: 'View your personal details',
                          color: const Color(0xFF8E24AA),
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => const ProfileScreen()),
                          ).then((_) => _fetchDashboard()),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildMenuCard({
    required IconData icon,
    required String title,
    required String subtitle,
    int? badgeCount,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Ink(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.1),
              blurRadius: 8,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Stack(
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, color: color, size: 28),
                ),
                const SizedBox(height: 12),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1F2937),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: const TextStyle(
                    fontSize: 11,
                    color: Colors.grey,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
            if (badgeCount != null && badgeCount > 0)
              Positioned(
                right: 0,
                top: 0,
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: const BoxDecoration(
                    color: Colors.red,
                    shape: BoxShape.circle,
                  ),
                  constraints: const BoxConstraints(
                    minWidth: 20,
                    minHeight: 20,
                  ),
                  child: Text(
                    '$badgeCount',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

