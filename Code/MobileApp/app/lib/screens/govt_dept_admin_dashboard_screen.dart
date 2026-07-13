import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import 'login_screen.dart';

class GovtDeptAdminDashboardScreen extends StatefulWidget {
  const GovtDeptAdminDashboardScreen({super.key});

  @override
  State<GovtDeptAdminDashboardScreen> createState() => _GovtDeptAdminDashboardScreenState();
}

class _GovtDeptAdminDashboardScreenState extends State<GovtDeptAdminDashboardScreen> {
  final ApiService _apiService = ApiService();
  int _currentIndex = 0;

  bool _isLoadingStats = true;
  bool _isLoadingUsers = true;
  bool _isLoadingProfile = true;

  Map<String, dynamic> _stats = {
    'verifiers': 0,
    'approvers': 0,
    'pending_workers': 0,
    'verified_workers': 0,
    'approved_workers': 0,
  };

  List<dynamic> _users = [];
  List<dynamic> _filteredUsers = [];
  Map<String, dynamic>? _department;

  final TextEditingController _searchController = TextEditingController();
  String _selectedFilterRole = 'all';

  // Logged-in admin info
  String _adminName = '';
  String _adminEmail = '';

  @override
  void initState() {
    super.initState();
    _initData();
  }

  Future<void> _initData() async {
    await _loadAdminInfo();
    _fetchStats();
    _fetchUsers();
    _fetchDepartmentDetails();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadAdminInfo() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _adminName = prefs.getString('user_name') ?? 'Department Admin';
      _adminEmail = prefs.getString('user_email') ?? '';
    });
  }

  Future<void> _fetchStats() async {
    setState(() {
      _isLoadingStats = true;
    });
    final res = await _apiService.getDashboardStats();
    if (res['success'] == true) {
      setState(() {
        _stats = Map<String, dynamic>.from(res['data'] ?? {});
        _isLoadingStats = false;
      });
    } else {
      setState(() {
        _stats = {
          'verifiers': 0,
          'approvers': 0,
          'pending_workers': 0,
          'verified_workers': 0,
          'approved_workers': 0,
        };
        _isLoadingStats = false;
      });
    }
  }

  Future<void> _fetchUsers() async {
    setState(() {
      _isLoadingUsers = true;
    });
    final res = await _apiService.getRegisteredUsers();
    if (res['success'] == true) {
      setState(() {
        _users = res['data'] ?? [];
        _users = _users.where((u) => u['role'] == 'dept-verifier' || u['role'] == 'dept-approver' || u['role'] == 'verifier' || u['role'] == 'approver').toList();
        _filteredUsers = List.from(_users);
        _isLoadingUsers = false;
      });
    } else {
      setState(() {
        _isLoadingUsers = false;
      });
    }
  }

  Future<void> _fetchDepartmentDetails() async {
    setState(() {
      _isLoadingProfile = true;
    });
    final res = await _apiService.getDepartmentDetails();
    if (res['success'] == true) {
      setState(() {
        _department = res['data'];
        _isLoadingProfile = false;
      });
    } else {
      setState(() {
        _isLoadingProfile = false;
      });
    }
  }

  void _triggerSearch() {
    final query = _searchController.text.toLowerCase().trim();
    setState(() {
      _filteredUsers = _users.where((user) {
        final matchesSearch = query.isEmpty ||
            (user['name']?.toString().toLowerCase().contains(query) ?? false) ||
            (user['email']?.toString().toLowerCase().contains(query) ?? false) ||
            (user['mobile']?.toString().toLowerCase().contains(query) ?? false);

        bool matchesRole = true;
        if (_selectedFilterRole == 'verifier') {
          matchesRole = user['role'] == 'dept-verifier' || user['role'] == 'verifier';
        } else if (_selectedFilterRole == 'approver') {
          matchesRole = user['role'] == 'dept-approver' || user['role'] == 'approver';
        }

        return matchesSearch && matchesRole;
      }).toList();
    });
  }

  Future<void> _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirm Logout', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Logout', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await _apiService.logout();
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    }
  }

  // UI BUILDERS
  Widget _buildDashboardTab() {
    if (_isLoadingStats) {
      return const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)));
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFE65100), Color(0xFFFF9800)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Row(
              children: [
                const CircleAvatar(
                  radius: 30,
                  backgroundColor: Colors.white24,
                  child: Icon(Icons.corporate_fare_rounded, color: Colors.white, size: 36),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _department?['department_name'] ?? 'Govt Department',
                        style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Dept Admin Panel',
                        style: TextStyle(color: Colors.white.withOpacity(0.85), fontSize: 13),
                      ),
                    ],
                  ),
                )
              ],
            ),
          ),
          const SizedBox(height: 24),
          const Text('Staff Overview', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87)),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  title: 'Verifiers',
                  value: _stats['verifiers'].toString(),
                  icon: Icons.verified_user_rounded,
                  color: Colors.blue.shade700,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildStatCard(
                  title: 'Approvers',
                  value: _stats['approvers'].toString(),
                  icon: Icons.gavel_rounded,
                  color: Colors.indigo.shade700,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          const Text('Worker Registry Progress', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87)),
          const SizedBox(height: 12),
          _buildProgressStats(),
        ],
      ),
    );
  }

  Widget _buildStatCard({required String title, required String value, required IconData icon, required Color color}) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade100),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 8, offset: const Offset(0, 4)),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
            child: Icon(icon, color: color, size: 24),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: TextStyle(color: Colors.grey.shade600, fontSize: 12, fontWeight: FontWeight.w500)),
              const SizedBox(height: 4),
              Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.black87)),
            ],
          )
        ],
      ),
    );
  }

  Widget _buildProgressStats() {
    return Column(
      children: [
        _buildProgressRow('Pending verification', _stats['pending_workers'] ?? 0, Colors.amber),
        const SizedBox(height: 12),
        _buildProgressRow('Verified', _stats['verified_workers'] ?? 0, Colors.blue),
        const SizedBox(height: 12),
        _buildProgressRow('Approved', _stats['approved_workers'] ?? 0, Colors.green),
      ],
    );
  }

  Widget _buildProgressRow(String label, int count, Color color) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade100),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(width: 12, height: 12, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
              const SizedBox(width: 12),
              Text(label, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500, color: Colors.black87)),
            ],
          ),
          Text(count.toString(), style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87)),
        ],
      ),
    );
  }

  Widget _buildStaffTab() {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(12),
          color: Colors.white,
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _searchController,
                  onChanged: (_) => _triggerSearch(),
                  decoration: InputDecoration(
                    hintText: 'Search staff name/email...',
                    prefixIcon: const Icon(Icons.search),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                    filled: true,
                    fillColor: Colors.grey.shade100,
                    contentPadding: EdgeInsets.zero,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              PopupMenuButton<String>(
                icon: const Icon(Icons.filter_list_rounded),
                onSelected: (val) {
                  setState(() {
                    _selectedFilterRole = val;
                    _triggerSearch();
                  });
                },
                itemBuilder: (ctx) => [
                  const PopupMenuItem(value: 'all', child: Text('All Roles')),
                  const PopupMenuItem(value: 'verifier', child: Text('Verifiers Only')),
                  const PopupMenuItem(value: 'approver', child: Text('Approvers Only')),
                ],
              )
            ],
          ),
        ),
        Expanded(
          child: _isLoadingUsers
              ? const Center(child: CircularProgressIndicator())
              : _filteredUsers.isEmpty
                  ? const Center(child: Text('No staff users found.'))
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _filteredUsers.length,
                      itemBuilder: (ctx, index) {
                        final user = _filteredUsers[index];
                        final bool isVerifier = user['role'] == 'dept-verifier' || user['role'] == 'verifier';
                        final bool isActive = user['is_active'] == true || user['is_active'] == 1 || user['status'] == 'active';
                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 0,
                          color: Colors.white,
                          child: ListTile(
                            leading: CircleAvatar(
                              backgroundColor: isVerifier ? Colors.blue.shade50 : Colors.indigo.shade50,
                              child: Icon(
                                isVerifier ? Icons.verified_user_rounded : Icons.gavel_rounded,
                                color: isVerifier ? Colors.blue : Colors.indigo,
                              ),
                            ),
                            title: Text(user['name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold)),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(user['email'] ?? 'N/A', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                if (user['mobile'] != null)
                                  Text(user['mobile'], style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                              ],
                            ),
                            trailing: Switch(
                              value: isActive,
                              activeColor: const Color(0xFFE65100),
                              onChanged: (_) async {
                                final res = await _apiService.toggleUserStatus(user['id']?.toString() ?? '');
                                if (res['success'] == true) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('User status updated successfully.')),
                                  );
                                  _fetchUsers();
                                }
                              },
                            ),
                          ),
                        );
                      },
                    ),
        )
      ],
    );
  }

  void _showOnboardDialog() {
    final nameCtrl = TextEditingController();
    final emailCtrl = TextEditingController();
    final mobileCtrl = TextEditingController();
    final passwordCtrl = TextEditingController();
    String selectedRole = 'dept-verifier';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            top: 20,
            left: 20,
            right: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Onboard New Staff', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                  ],
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: selectedRole,
                  decoration: const InputDecoration(labelText: 'Role', border: OutlineInputBorder()),
                  items: const [
                    DropdownMenuItem(value: 'dept-verifier', child: Text('Department Verifier')),
                    DropdownMenuItem(value: 'dept-approver', child: Text('Department Approver')),
                  ],
                  onChanged: (val) {
                    if (val != null) {
                      setModalState(() {
                        selectedRole = val;
                      });
                    }
                  },
                ),
                const SizedBox(height: 12),
                TextField(controller: nameCtrl, decoration: const InputDecoration(labelText: 'Full Name', border: OutlineInputBorder())),
                const SizedBox(height: 12),
                TextField(controller: emailCtrl, decoration: const InputDecoration(labelText: 'Email Address', border: OutlineInputBorder())),
                const SizedBox(height: 12),
                TextField(controller: mobileCtrl, decoration: const InputDecoration(labelText: 'Mobile Number', border: OutlineInputBorder())),
                const SizedBox(height: 12),
                TextField(
                  controller: passwordCtrl,
                  obscureText: true,
                  decoration: const InputDecoration(labelText: 'Password', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: () async {
                    if (nameCtrl.text.isEmpty || emailCtrl.text.isEmpty || mobileCtrl.text.isEmpty || passwordCtrl.text.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please fill all fields')));
                      return;
                    }
                    final res = await _apiService.registerUser({
                      'name': nameCtrl.text.trim(),
                      'email': emailCtrl.text.trim(),
                      'mobile': mobileCtrl.text.trim(),
                      'password': passwordCtrl.text,
                      'role': selectedRole,
                    });
                    if (res['success'] == true) {
                      Navigator.pop(ctx);
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Staff member registered successfully.')));
                      _fetchUsers();
                      _fetchStats();
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'] ?? 'Failed to register')));
                    }
                  },
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE65100), padding: const EdgeInsets.symmetric(vertical: 14)),
                  child: const Text('Submit', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildProfileTab() {
    if (_isLoadingProfile) {
      return const Center(child: CircularProgressIndicator());
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            elevation: 0,
            color: Colors.white,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  const CircleAvatar(
                    radius: 40,
                    backgroundColor: Color(0xFFFBE9E7),
                    child: Icon(Icons.admin_panel_settings_rounded, color: Color(0xFFE65100), size: 48),
                  ),
                  const SizedBox(height: 16),
                  Text(_adminName, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  Text(_adminEmail, style: TextStyle(color: Colors.grey.shade600, fontSize: 14)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),
          const Text('Department Information', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            elevation: 0,
            color: Colors.white,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  _buildProfileRow('Name', _department?['department_name'] ?? 'N/A'),
                  const Divider(),
                  _buildProfileRow('Code', _department?['department_code']?.toString() ?? 'N/A'),
                  const Divider(),
                  _buildProfileRow('Owner State', _department?['state'] ?? 'National'),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: Colors.grey.shade600, fontWeight: FontWeight.w500)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black87)),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: const Text('Govt Department Panel', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFE65100),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            onPressed: _handleLogout,
          )
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: [
          _buildDashboardTab(),
          _buildStaffTab(),
          _buildProfileTab(),
        ],
      ),
      floatingActionButton: _currentIndex == 1
          ? FloatingActionButton(
              onPressed: _showOnboardDialog,
              backgroundColor: const Color(0xFFE65100),
              child: const Icon(Icons.add, color: Colors.white),
            )
          : null,
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        selectedItemColor: const Color(0xFFE65100),
        unselectedItemColor: Colors.grey.shade500,
        type: BottomNavigationBarType.fixed,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.dashboard_rounded), label: 'Dashboard'),
          BottomNavigationBarItem(icon: Icon(Icons.people_alt_rounded), label: 'Staff'),
          BottomNavigationBarItem(icon: Icon(Icons.person_rounded), label: 'Profile'),
        ],
      ),
    );
  }
}
