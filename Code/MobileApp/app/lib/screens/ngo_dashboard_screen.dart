import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:file_picker/file_picker.dart';
import '../services/api_service.dart';
import '../models/models.dart';
import 'login_screen.dart';

class NgoDashboardScreen extends StatefulWidget {
  const NgoDashboardScreen({super.key});

  @override
  State<NgoDashboardScreen> createState() => _NgoDashboardScreenState();
}

class _NgoDashboardScreenState extends State<NgoDashboardScreen> {
  final ApiService _apiService = ApiService();
  int _currentIndex = 0;

  // Role-based state
  String _userRole = 'ngo'; // 'ngo', 'employer', or 'contractor'
  String _userName = '';
  String _userEmail = '';

  bool _isLoadingStats = true;
  bool _isLoadingUsers = true;
  bool _isLoadingSchemes = true;
  bool _isLoadingWorkers = true;

  // Stats data
  Map<String, dynamic> _stats = {
    'employers_count': 0,
    'contractors_count': 0,
    'beneficiaries_count': 0,
    'schemes_count': 0,
  };

  // Lists
  List<dynamic> _users = []; // Onboarded employers and contractors
  List<dynamic> _filteredUsers = [];
  List<dynamic> _workers = []; // Onboarded beneficiaries
  List<dynamic> _filteredWorkers = [];
  List<Scheme> _schemes = [];
  List<Scheme> _filteredSchemes = [];

  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _initData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _initData() async {
    await _loadUserInfo();
    await _refreshAll();
  }

  Future<void> _loadUserInfo() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _userRole = prefs.getString('user_role') ?? 'ngo';
      _userName = prefs.getString('user_name') ?? 'Institution Admin';
      _userEmail = prefs.getString('user_email') ?? '';
    });
  }

  Future<void> _refreshAll() async {
    _fetchStats();
    _fetchUsers();
    _fetchWorkers();
    _fetchSchemes();
  }

  Future<void> _fetchStats() async {
    if (!mounted) return;
    setState(() => _isLoadingStats = true);
    final res = await _apiService.getDashboardStats();
    if (res['success'] == true && mounted) {
      setState(() {
        _stats = Map<String, dynamic>.from(res['data'] ?? {});
        _isLoadingStats = false;
      });
    } else {
      if (mounted) setState(() => _isLoadingStats = false);
    }
  }

  Future<void> _fetchUsers() async {
    if (!mounted) return;
    setState(() => _isLoadingUsers = true);
    final res = await _apiService.getRegisteredUsers();
    if (res['success'] == true && mounted) {
      setState(() {
        _users = res['data'] ?? [];
        _filteredUsers = List.from(_users);
        _isLoadingUsers = false;
        _applySearch();
      });
    } else {
      if (mounted) setState(() => _isLoadingUsers = false);
    }
  }

  Future<void> _fetchWorkers() async {
    if (!mounted) return;
    setState(() => _isLoadingWorkers = true);
    final res = await _apiService.getWorkers('all');
    if (res['success'] == true && mounted) {
      setState(() {
        _workers = res['data'] ?? [];
        _filteredWorkers = List.from(_workers);
        _isLoadingWorkers = false;
        _applySearch();
      });
    } else {
      if (mounted) setState(() => _isLoadingWorkers = false);
    }
  }

  Future<void> _fetchSchemes() async {
    if (!mounted) return;
    setState(() => _isLoadingSchemes = true);
    try {
      final list = await _apiService.getSchemes();
      if (mounted) {
        setState(() {
          _schemes = list;
          _filteredSchemes = List.from(_schemes);
          _isLoadingSchemes = false;
          _applySearch();
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoadingSchemes = false);
    }
  }

  void _applySearch() {
    final query = _searchController.text.toLowerCase().trim();
    final tabName = _getTabName(_currentIndex);

    setState(() {
      if (tabName == 'Staff') {
        final staff = _users.where((u) => u['role'] == 'ngo-verifier' || u['role'] == 'ngo-approver').toList();
        _filteredUsers = staff.where((u) {
          final name = u['name']?.toString().toLowerCase() ?? '';
          final email = u['email']?.toString().toLowerCase() ?? '';
          final mob = u['mobile']?.toString() ?? '';
          return query.isEmpty || name.contains(query) || email.contains(query) || mob.contains(query);
        }).toList();
      } else if (tabName == 'Employer') {
        final employers = _users.where((u) => u['role'] == 'employer').toList();
        _filteredUsers = employers.where((u) {
          final name = u['name']?.toString().toLowerCase() ?? '';
          final email = u['email']?.toString().toLowerCase() ?? '';
          final mob = u['mobile']?.toString() ?? '';
          return query.isEmpty || name.contains(query) || email.contains(query) || mob.contains(query);
        }).toList();
      } else if (tabName == 'Contractors') {
        final contractors = _users.where((u) => u['role'] == 'contractor').toList();
        _filteredUsers = contractors.where((u) {
          final name = u['name']?.toString().toLowerCase() ?? '';
          final email = u['email']?.toString().toLowerCase() ?? '';
          final mob = u['mobile']?.toString() ?? '';
          return query.isEmpty || name.contains(query) || email.contains(query) || mob.contains(query);
        }).toList();
      } else if (tabName == 'Beneficiaries') {
        _filteredWorkers = _workers.where((w) {
          final name = w['full_name']?.toString().toLowerCase() ?? '';
          final code = w['worker_code']?.toString().toLowerCase() ?? '';
          final mob = w['mobile_alt']?.toString() ?? w['user_details']?['mobile']?.toString() ?? '';
          final ration = w['ration_card_number']?.toString().toLowerCase() ?? '';
          return query.isEmpty || name.contains(query) || code.contains(query) || mob.contains(query) || ration.contains(query);
        }).toList();
      } else if (tabName == 'Schemes') {
        _filteredSchemes = _schemes.where((s) {
          return query.isEmpty || s.name.toLowerCase().contains(query) || s.description.toLowerCase().contains(query);
        }).toList();
      }
    });
  }

  // Helper to map tabs based on role
  List<Map<String, dynamic>> _getTabs() {
    if (_userRole == 'ngo') {
      return [
        {'label': 'Dashboard', 'icon': Icons.dashboard_rounded},
        {'label': 'Staff', 'icon': Icons.supervised_user_circle_rounded},
        {'label': 'Employer', 'icon': Icons.work_rounded},
        {'label': 'Contractors', 'icon': Icons.handyman_rounded},
        {'label': 'Beneficiaries', 'icon': Icons.people_alt_rounded},
        {'label': 'Schemes', 'icon': Icons.assignment_rounded},
      ];
    } else if (_userRole == 'employer') {
      return [
        {'label': 'Dashboard', 'icon': Icons.dashboard_rounded},
        {'label': 'Contractors', 'icon': Icons.handyman_rounded},
        {'label': 'Beneficiaries', 'icon': Icons.people_alt_rounded},
      ];
    } else {
      // Contractor
      return [
        {'label': 'Dashboard', 'icon': Icons.dashboard_rounded},
        {'label': 'Beneficiaries', 'icon': Icons.people_alt_rounded},
      ];
    }
  }

  String _getTabName(int index) {
    final tabs = _getTabs();
    if (index >= 0 && index < tabs.length) {
      return tabs[index]['label'] as String;
    }
    return '';
  }

  Future<void> _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirm Logout'),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(onPressed: () => Navigator.of(ctx).pop(false), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Logout', style: TextStyle(color: Colors.white)),
          )
        ],
      ),
    );

    if (confirm == true && mounted) {
      await _apiService.logout();
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    }
  }

  // ─── TAB BUILDERS ─────────────────────────────────────────────────────────

  Widget _buildDashboardTab() {
    if (_isLoadingStats) {
      return const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A)));
    }

    final String displayName = _userRole == 'ngo'
        ? 'NGO Dashboard'
        : _userRole == 'employer'
            ? 'Employer Dashboard'
            : 'Contractor Dashboard';

    return RefreshIndicator(
      onRefresh: _refreshAll,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Brand Banner Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 10, offset: const Offset(0, 4)),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Welcome, $_userName',
                    style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    displayName,
                    style: TextStyle(color: Colors.grey.shade400, fontSize: 13, fontWeight: FontWeight.w500),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 28),
            const Text(
              'Dashboard Overview',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 16),

            // Dynamic grid based on role
            GridView.count(
              crossAxisCount: 2,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              childAspectRatio: 1.15,
              children: _getStatsCards(),
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _getStatsCards() {
    final List<Widget> cards = [];

    if (_userRole == 'ngo') {
      cards.add(_buildStatCard('Employers', _stats['employers_count']?.toString() ?? '0', Icons.work_rounded, Colors.orange, 1));
      cards.add(_buildStatCard('Contractors', _stats['contractors_count']?.toString() ?? '0', Icons.handyman_rounded, Colors.brown, 2));
      cards.add(_buildStatCard('Beneficiaries', _stats['beneficiaries_count']?.toString() ?? '0', Icons.people_alt_rounded, Colors.indigo, 3));
      cards.add(_buildStatCard('Schemes', _stats['schemes_count']?.toString() ?? '0', Icons.assignment_rounded, Colors.teal, 4));
    } else if (_userRole == 'employer') {
      cards.add(_buildStatCard('Contractors', _stats['contractors_count']?.toString() ?? '0', Icons.handyman_rounded, Colors.brown, 1));
      cards.add(_buildStatCard('Beneficiaries', _stats['beneficiaries_count']?.toString() ?? '0', Icons.people_alt_rounded, Colors.indigo, 2));
    } else if (_userRole == 'contractor') {
      cards.add(_buildStatCard('Beneficiaries', _stats['beneficiaries_count']?.toString() ?? '0', Icons.people_alt_rounded, Colors.indigo, 1));
    }

    return cards;
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color, int targetIndex) {
    return GestureDetector(
      onTap: () {
        setState(() {
          _currentIndex = targetIndex;
        });
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(color: Colors.grey.withOpacity(0.08), blurRadius: 8, offset: const Offset(0, 3)),
          ],
          border: Border.all(color: Colors.grey.shade100),
        ),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                CircleAvatar(
                  backgroundColor: color.withOpacity(0.1),
                  radius: 20,
                  child: Icon(icon, color: color, size: 20),
                ),
                Text(
                  value,
                  style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: color),
                ),
              ],
            ),
            Text(
              label,
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.grey.shade700),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStaffTab() {
    final icon = Icons.supervised_user_circle_rounded;
    final color = Colors.purple;

    return Column(
      children: [
        _buildSearchBar('Search staff...'),
        Expanded(
          child: _isLoadingUsers
              ? const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A)))
              : _filteredUsers.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(icon, size: 56, color: Colors.grey.shade300),
                          const SizedBox(height: 12),
                          Text('No staff users found', style: TextStyle(color: Colors.grey.shade500, fontSize: 15)),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _filteredUsers.length,
                      itemBuilder: (ctx, idx) {
                        final u = _filteredUsers[idx];
                        final isVerifier = u['role'] == 'ngo-verifier';
                        final isActive = u['is_active'] == true;
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            boxShadow: [
                              BoxShadow(color: Colors.grey.withOpacity(0.06), blurRadius: 6, offset: const Offset(0, 2)),
                            ],
                            border: Border.all(color: Colors.grey.shade100),
                          ),
                          child: ListTile(
                            leading: Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: color.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Icon(
                                isVerifier ? Icons.verified_user_rounded : Icons.gavel_rounded,
                                color: color,
                                size: 20,
                              ),
                            ),
                            title: Text(u['name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SizedBox(height: 3),
                                Text('Role: ${isVerifier ? 'Verifier' : 'Approver'}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12, color: Colors.purple)),
                                Text('Email: ${u['email'] ?? 'N/A'}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                if (u['mobile'] != null)
                                  Text('Contact: ${u['mobile']}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                if (u['designation'] != null && u['designation'].toString().isNotEmpty)
                                  Text('Designation: ${u['designation']}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                              ],
                            ),
                            trailing: Switch(
                              value: isActive,
                              activeColor: const Color(0xFF0F172A),
                              onChanged: (val) async {
                                final res = await _apiService.toggleUserStatus(u['id']);
                                if (res['success'] == true) {
                                  _fetchUsers();
                                }
                              },
                            ),
                          ),
                        );
                      },
                    ),
        ),
      ],
    );
  }

  // ─── EMPLOYERS TAB ────────────────────────────────────────────────────────

  Widget _buildEmployersTab() {
    return _buildOnboardedUsersList('employer');
  }

  // ─── CONTRACTORS TAB ──────────────────────────────────────────────────────

  Widget _buildContractorsTab() {
    return _buildOnboardedUsersList('contractor');
  }

  Widget _buildOnboardedUsersList(String role) {
    final isEmployer = role == 'employer';
    final label = isEmployer ? 'Employer' : 'Contractor';
    final icon = isEmployer ? Icons.work_rounded : Icons.handyman_rounded;
    final color = isEmployer ? Colors.orange : Colors.brown;

    return Column(
      children: [
        _buildSearchBar('Search ${label}s...'),
        Expanded(
          child: _isLoadingUsers
              ? const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A)))
              : _filteredUsers.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(icon, size: 56, color: Colors.grey.shade300),
                          const SizedBox(height: 12),
                          Text('No ${label}s found', style: TextStyle(color: Colors.grey.shade500, fontSize: 15)),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _filteredUsers.length,
                      itemBuilder: (ctx, idx) {
                        final u = _filteredUsers[idx];
                        final isActive = u['is_active'] == true;
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            boxShadow: [
                              BoxShadow(color: Colors.grey.withOpacity(0.06), blurRadius: 6, offset: const Offset(0, 2)),
                            ],
                            border: Border.all(color: Colors.grey.shade100),
                          ),
                          child: ListTile(
                            leading: Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: color.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Icon(icon, color: color, size: 20),
                            ),
                            title: Text(u['name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SizedBox(height: 3),
                                Text('Email: ${u['email'] ?? 'N/A'}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                if (u['mobile'] != null)
                                  Text('Contact: ${u['mobile']}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                if (u['reg_no'] != null && u['reg_no'].toString().isNotEmpty)
                                  Text(isEmployer ? 'Reg/License: ${u['reg_no']}' : 'License No: ${u['reg_no']}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                if (u['address'] != null && u['address'].toString().isNotEmpty)
                                  Text('Address: ${u['address']}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                              ],
                            ),
                            trailing: Switch(
                              value: isActive,
                              activeColor: const Color(0xFF0F172A),
                              onChanged: (val) async {
                                final res = await _apiService.toggleUserStatus(u['id']);
                                if (res['success'] == true) {
                                  _fetchUsers();
                                }
                              },
                            ),
                          ),
                        );
                      },
                    ),
        ),
      ],
    );
  }

  // ─── BENEFICIARIES TAB ────────────────────────────────────────────────────

  Widget _buildBeneficiariesTab() {
    return Column(
      children: [
        _buildSearchBar('Search beneficiaries...'),
        Expanded(
          child: _isLoadingWorkers
              ? const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A)))
              : _filteredWorkers.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.people_alt_rounded, size: 56, color: Colors.grey.shade300),
                          const SizedBox(height: 12),
                          Text('No beneficiaries registered yet', style: TextStyle(color: Colors.grey.shade500, fontSize: 15)),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _filteredWorkers.length,
                      itemBuilder: (ctx, idx) {
                        final w = _filteredWorkers[idx];
                        final isVerified = w['is_verified'] == true || w['is_verified'] == 1 || w['status'] == 'verified';
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            boxShadow: [
                              BoxShadow(color: Colors.grey.withOpacity(0.06), blurRadius: 6, offset: const Offset(0, 2)),
                            ],
                            border: Border.all(color: Colors.grey.shade100),
                          ),
                          child: ListTile(
                            leading: const CircleAvatar(
                              backgroundColor: Color(0xFFEFF6FF),
                              child: Icon(Icons.person, color: Color(0xFF1D4ED8)),
                            ),
                            title: Text(w['full_name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SizedBox(height: 3),
                                Text('Code: ${w['worker_code'] ?? 'N/A'}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                Text('Mobile: ${w['mobile_alt'] ?? w['user_details']?['mobile'] ?? 'N/A'}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                if (w['ration_card_number'] != null)
                                  Text('Ration Card: ${w['ration_card_number']}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                              ],
                            ),
                            trailing: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: isVerified ? Colors.green.shade50 : Colors.orange.shade50,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: isVerified ? Colors.green.shade200 : Colors.orange.shade200),
                              ),
                              child: Text(
                                isVerified ? 'Verified' : 'Pending',
                                style: TextStyle(
                                  color: isVerified ? Colors.green.shade800 : Colors.orange.shade800,
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
        ),
      ],
    );
  }

  // ─── SCHEMES TAB ──────────────────────────────────────────────────────────

  Widget _buildSchemesTab() {
    return Column(
      children: [
        _buildSearchBar('Search schemes...'),
        Expanded(
          child: _isLoadingSchemes
              ? const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A)))
              : _filteredSchemes.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.assignment_rounded, size: 56, color: Colors.grey.shade300),
                          const SizedBox(height: 12),
                          Text('No schemes found', style: TextStyle(color: Colors.grey.shade500, fontSize: 15)),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _filteredSchemes.length,
                      itemBuilder: (ctx, idx) {
                        final s = _filteredSchemes[idx];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          elevation: 1,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        s.name,
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A)),
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: s.isActive ? Colors.green.shade50 : Colors.red.shade50,
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        s.isActive ? 'Active' : 'Inactive',
                                        style: TextStyle(color: s.isActive ? Colors.green.shade700 : Colors.red.shade700, fontSize: 10, fontWeight: FontWeight.bold),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  s.description,
                                  maxLines: 3,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                                ),
                                const Divider(height: 20),
                                Row(
                                  children: [
                                    Icon(Icons.link_rounded, size: 16, color: Colors.grey.shade500),
                                    const SizedBox(width: 4),
                                    Expanded(
                                      child: Text(
                                        s.applyUrl ?? '',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: TextStyle(color: Colors.blue.shade700, fontSize: 12),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
        ),
      ],
    );
  }

  // ─── SEARCH BAR WIDGET ────────────────────────────────────────────────────

  Widget _buildSearchBar(String hint) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _searchController,
              onChanged: (val) => _applySearch(),
              decoration: InputDecoration(
                hintText: hint,
                prefixIcon: const Icon(Icons.search_rounded),
                filled: true,
                fillColor: Colors.grey.shade100,
                contentPadding: EdgeInsets.zero,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ─── BOTTOM SHEETS & DIALOGS ──────────────────────────────────────────────

  void _showOnboardUserDialog() {
    final tabName = _getTabName(_currentIndex);
    final isEmployer = tabName == 'Employer';
    final role = isEmployer ? 'employer' : 'contractor';
    final label = isEmployer ? 'Employer' : 'Contractor';

    final nameCtrl = TextEditingController();
    final regNoCtrl = TextEditingController();
    final mobileCtrl = TextEditingController();
    final contactCtrl = TextEditingController();
    final addressCtrl = TextEditingController();
    final emailCtrl = TextEditingController();
    final passCtrl = TextEditingController();
    final confirmPassCtrl = TextEditingController();

    final formKey = GlobalKey<FormState>();
    bool isLoading = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            left: 20, right: 20, top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: SingleChildScrollView(
            child: Form(
              key: formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Text(
                        'Onboard New $label',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const Spacer(),
                      IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // Name
                  TextFormField(
                    controller: nameCtrl,
                    decoration: InputDecoration(
                      labelText: isEmployer ? 'Employer / Factory Name *' : 'Contractor / Thekedar Name *',
                      border: const OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),

                  // Reg / License
                  TextFormField(
                    controller: regNoCtrl,
                    decoration: InputDecoration(
                      labelText: isEmployer ? 'Reg No / License *' : 'License No *',
                      border: const OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),

                  // Mobile
                  TextFormField(
                    controller: mobileCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'Contact Mobile *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),

                  // Contact Person (For employer only)
                  if (isEmployer) ...[
                    TextFormField(
                      controller: contactCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Contact Person Name *',
                        border: OutlineInputBorder(),
                      ),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                    const SizedBox(height: 12),
                  ],

                  // Address
                  TextFormField(
                    controller: addressCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      labelText: isEmployer ? 'Office/Factory Address *' : 'Office Address *',
                      border: const OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 16),

                  // Credentials
                  const Text('Login Credentials', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 10),
                  TextFormField(
                    controller: emailCtrl,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(
                      labelText: 'Login Email *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || !v.contains('@')) ? 'Valid email required' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: passCtrl,
                    obscureText: true,
                    decoration: const InputDecoration(
                      labelText: 'Password *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || v.length < 6) ? 'Password min 6 characters' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: confirmPassCtrl,
                    obscureText: true,
                    decoration: const InputDecoration(
                      labelText: 'Confirm Password *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v != passCtrl.text) ? 'Passwords do not match' : null,
                  ),
                  const SizedBox(height: 20),

                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: isLoading ? null : () async {
                        if (!formKey.currentState!.validate()) return;
                        setModalState(() => isLoading = true);

                        final payload = {
                          'role': role,
                          'name': nameCtrl.text.trim(),
                          'mobile': mobileCtrl.text.trim(),
                          'email': emailCtrl.text.trim(),
                          'password': passCtrl.text.trim(),
                          'designation': isEmployer ? contactCtrl.text.trim() : 'Contractor',
                          'address': addressCtrl.text.trim(),
                          'reg_no': regNoCtrl.text.trim(),
                        };

                        final res = await _apiService.registerUser(payload);
                        setModalState(() => isLoading = false);

                        if (mounted) {
                          Navigator.pop(ctx);
                          if (res['success'] == true) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text('$label onboarded successfully!'), backgroundColor: Colors.green),
                            );
                            _fetchUsers();
                            _fetchStats();
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text(res['message'] ?? 'Failed to onboard $label'), backgroundColor: Colors.red),
                            );
                          }
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: isLoading
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : Text('Onboard $label', style: const TextStyle(color: Colors.white)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showOnboardStaffDialog() {
    final nameCtrl = TextEditingController();
    final mobileCtrl = TextEditingController();
    final emailCtrl = TextEditingController();
    final passCtrl = TextEditingController();
    final confirmPassCtrl = TextEditingController();
    final designationCtrl = TextEditingController();
    String selectedRole = 'ngo-verifier';

    final formKey = GlobalKey<FormState>();
    bool isLoading = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            left: 20, right: 20, top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: SingleChildScrollView(
            child: Form(
              key: formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      const Text(
                        'Onboard NGO Staff',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const Spacer(),
                      IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // Role Dropdown
                  DropdownButtonFormField<String>(
                    value: selectedRole,
                    decoration: const InputDecoration(
                      labelText: 'Staff Role *',
                      border: OutlineInputBorder(),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'ngo-verifier', child: Text('NGO Verifier')),
                      DropdownMenuItem(value: 'ngo-approver', child: Text('NGO Approver')),
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

                  // Name
                  TextFormField(
                    controller: nameCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Full Name *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),

                  // Mobile
                  TextFormField(
                    controller: mobileCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'Contact Mobile *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),

                  // Designation
                  TextFormField(
                    controller: designationCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Designation *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 16),

                  // Credentials
                  const Text('Login Credentials', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 10),
                  
                  // Email
                  TextFormField(
                    controller: emailCtrl,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(
                      labelText: 'Login Email *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || !v.contains('@')) ? 'Valid email required' : null,
                  ),
                  const SizedBox(height: 12),
                  
                  // Password
                  TextFormField(
                    controller: passCtrl,
                    obscureText: true,
                    decoration: const InputDecoration(
                      labelText: 'Password *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v == null || v.length < 6) ? 'Password min 6 characters' : null,
                  ),
                  const SizedBox(height: 12),
                  
                  // Confirm Password
                  TextFormField(
                    controller: confirmPassCtrl,
                    obscureText: true,
                    decoration: const InputDecoration(
                      labelText: 'Confirm Password *',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => (v != passCtrl.text) ? 'Passwords do not match' : null,
                  ),
                  const SizedBox(height: 20),

                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: isLoading ? null : () async {
                        if (!formKey.currentState!.validate()) return;
                        setModalState(() => isLoading = true);

                        final payload = {
                          'role': selectedRole,
                          'name': nameCtrl.text.trim(),
                          'mobile': mobileCtrl.text.trim(),
                          'email': emailCtrl.text.trim(),
                          'password': passCtrl.text.trim(),
                          'designation': designationCtrl.text.trim(),
                        };

                        final res = await _apiService.registerUser(payload);
                        setModalState(() => isLoading = false);

                        if (mounted) {
                          Navigator.pop(ctx);
                          if (res['success'] == true) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('NGO Staff onboarded successfully!'), backgroundColor: Colors.green),
                            );
                            _fetchUsers();
                            _fetchStats();
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text(res['message'] ?? 'Failed to onboard NGO staff'), backgroundColor: Colors.red),
                            );
                          }
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: isLoading
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text('Onboard Staff', style: TextStyle(color: Colors.white)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showOnboardBeneficiaryDialog() {
    int currentStep = 1;
    bool isLoading = false;
    String? errorMsg;

    // Step 1: Mobile
    final mobileCtrl = TextEditingController();
    
    // Step 2: Ration Card & OCR
    final rationNoCtrl = TextEditingController();
    bool pdfSelected = false;
    String? pdfName;
    List<int>? pdfBytes;
    bool isProcessing = false;
    List<Map<String, dynamic>> parsedMembers = [];
    Map<String, dynamic>? selectedMember;

    // Step 3: Details
    final nameCtrl = TextEditingController();
    final dobCtrl = TextEditingController();
    final addressCtrl = TextEditingController();
    String gender = 'male';

    List<Trade> trades = [];
    bool isLoadingTrades = true;
    String? selectedJobProfile;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          if (isLoadingTrades) {
            _apiService.getTrades().then((list) {
              setModalState(() {
                trades = list;
                isLoadingTrades = false;
                if (trades.isNotEmpty) {
                  selectedJobProfile = trades.first.name;
                }
              });
            }).catchError((_) {
              setModalState(() {
                isLoadingTrades = false;
              });
            });
          }

          return Padding(
            padding: EdgeInsets.only(
              left: 20, right: 20, top: 20,
              bottom: MediaQuery.of(context).viewInsets.bottom + 20,
            ),
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Text(
                        'Register Beneficiary (Step $currentStep/3)',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const Spacer(),
                      IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                    ],
                  ),
                  const SizedBox(height: 16),
                  if (errorMsg != null) ...[
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.red.shade200)),
                      child: Text(errorMsg!, style: TextStyle(color: Colors.red.shade800, fontSize: 13)),
                    ),
                    const SizedBox(height: 12),
                  ],

                  // STEP 1: MOBILE CHECK
                  if (currentStep == 1) ...[
                    const Text('Enter Beneficiary Mobile Number', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                    const SizedBox(height: 8),
                    TextField(
                      controller: mobileCtrl,
                      keyboardType: TextInputType.phone,
                      maxLength: 10,
                      decoration: const InputDecoration(
                        labelText: 'Mobile Number',
                        border: OutlineInputBorder(),
                        counterText: '',
                      ),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: isLoading ? null : () async {
                        final mob = mobileCtrl.text.trim();
                        if (mob.length != 10) {
                          setModalState(() => errorMsg = 'Please enter a valid 10-digit mobile number.');
                          return;
                        }
                        setModalState(() { isLoading = true; errorMsg = null; });
                        
                        final res = await _apiService.checkMobile(mob);
                        setModalState(() { isLoading = false; });
                        
                        if (res['success'] == true) {
                          if (res['exists'] == true) {
                            setModalState(() => errorMsg = 'This mobile number is already registered.');
                          } else {
                            setModalState(() {
                              currentStep = 2;
                              errorMsg = null;
                            });
                          }
                        } else {
                          setModalState(() => errorMsg = res['message'] ?? 'Failed to check mobile number.');
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: isLoading
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text('Check Availability & Next', style: TextStyle(color: Colors.white)),
                    ),
                  ],

                  // STEP 2: RATION CARD PDF UPLOAD & PARSING
                  if (currentStep == 2) ...[
                    const Text('Ration Card Verification (Upload PDF)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 10),
                    GestureDetector(
                      onTap: isProcessing ? null : () async {
                        setModalState(() { pdfSelected = false; parsedMembers = []; errorMsg = null; });
                        try {
                          final result = await FilePicker.platform.pickFiles(
                            type: FileType.custom,
                            allowedExtensions: ['pdf'],
                            withData: true,
                          );
                          if (result != null && result.files.isNotEmpty) {
                            final file = result.files.first;
                            setModalState(() {
                              pdfSelected = true;
                              pdfName = file.name;
                              pdfBytes = file.bytes;
                            });
                          }
                        } catch (e) {
                          setModalState(() { errorMsg = 'Failed to select file: $e'; });
                        }
                      },
                      child: Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: pdfSelected ? Colors.green.shade50 : Colors.grey.shade50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: pdfSelected ? Colors.green.shade300 : Colors.grey.shade300),
                        ),
                        child: Column(
                          children: [
                            Icon(pdfSelected ? Icons.picture_as_pdf_rounded : Icons.upload_file_rounded, size: 36, color: pdfSelected ? Colors.green : Colors.grey),
                            const SizedBox(height: 6),
                            Text(pdfSelected ? (pdfName ?? 'ration_card.pdf') : 'Upload Ration Card PDF', style: TextStyle(color: pdfSelected ? Colors.green.shade700 : Colors.grey.shade600, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    ),
                    if (pdfSelected && pdfBytes != null && parsedMembers.isEmpty && !isProcessing) ...[
                      const SizedBox(height: 12),
                      ElevatedButton.icon(
                        onPressed: () async {
                          setModalState(() { isProcessing = true; errorMsg = null; });
                          try {
                            final result = await _apiService.parseRationCardPdf(
                              pdfBytes!,
                              pdfName ?? 'ration.pdf',
                              rationCardNumber: rationNoCtrl.text.trim(),
                            );
                            if (result['success'] == true) {
                              final List members = result['members'] ?? [];
                              final String? extractedRationNo = result['ration_number'] as String?;
                              final String? extractedAddress = result['address'] as String?;
                              setModalState(() {
                                parsedMembers = members.cast<Map<String, dynamic>>();
                                if (extractedRationNo != null && extractedRationNo.isNotEmpty) {
                                  rationNoCtrl.text = extractedRationNo;
                                }
                                if (extractedAddress != null && extractedAddress.isNotEmpty) {
                                  addressCtrl.text = extractedAddress;
                                }
                                isProcessing = false;
                                if (parsedMembers.isNotEmpty) {
                                  selectedMember = parsedMembers.first;
                                }
                              });
                            } else {
                              setModalState(() {
                                isProcessing = false;
                                errorMsg = result['message'] ?? 'Failed to parse Ration Card PDF.';
                              });
                            }
                          } catch (e) {
                            setModalState(() {
                              isProcessing = false;
                              errorMsg = 'Error processing PDF: $e';
                            });
                          }
                        },
                        icon: const Icon(Icons.analytics_rounded, color: Colors.white),
                        label: const Text('Parse PDF & Extract Members', style: TextStyle(color: Colors.white)),
                        style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF0F172A)),
                      ),
                    ],
                    if (isProcessing) ...[
                      const SizedBox(height: 16),
                      const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A))),
                      const SizedBox(height: 6),
                      const Center(child: Text('Extracting details from Ration Card PDF...')),
                    ],
                    if (parsedMembers.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      const Text('Select Beneficiary from Family List:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<Map<String, dynamic>>(
                        isExpanded: true,
                        value: selectedMember,
                        decoration: const InputDecoration(border: OutlineInputBorder()),
                        items: parsedMembers.map((m) {
                          final rel = m['relation'] ?? 'Member';
                          final age = m['age'] ?? '';
                          return DropdownMenuItem(
                            value: m,
                            child: Text('${m['name']} ($rel, Age: $age)'),
                          );
                        }).toList(),
                        onChanged: (val) {
                          if (val != null) setModalState(() => selectedMember = val);
                        },
                      ),
                      const SizedBox(height: 20),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton(
                              onPressed: () => setModalState(() => currentStep = 1),
                              child: const Text('Back'),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: ElevatedButton(
                              onPressed: (selectedMember == null || isLoading) ? null : () async {
                                final rationNo = rationNoCtrl.text.trim();
                                if (rationNo.isNotEmpty) {
                                  setModalState(() { isLoading = true; errorMsg = null; });
                                  try {
                                    final checkResult = await _apiService.checkRationCardExists(rationNo);
                                    if (checkResult['exists'] == true) {
                                      setModalState(() {
                                        isLoading = false;
                                        errorMsg = checkResult['message'] ?? 'This Ration Card Number is already registered.';
                                      });
                                      return;
                                    }
                                  } catch (_) {}
                                  setModalState(() { isLoading = false; });
                                }

                                nameCtrl.text = selectedMember!['name'] ?? '';
                                gender = (selectedMember!['gender'] as String? ?? 'male').toLowerCase();
                                if (gender != 'male' && gender != 'female') gender = 'other';
                                setModalState(() {
                                  currentStep = 3;
                                  errorMsg = null;
                                });
                              },
                              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF0F172A)),
                              child: isLoading
                                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                                  : const Text('Next', style: TextStyle(color: Colors.white)),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],

                  // STEP 3: DETAILS CONFIRMATION & SUBMIT
                  if (currentStep == 3) ...[
                    const Text('Confirm Beneficiary Details', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 12),
                    TextField(
                      controller: nameCtrl,
                      readOnly: true,
                      decoration: const InputDecoration(labelText: 'Full Name (Selected)', border: OutlineInputBorder(), filled: true),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: dobCtrl,
                      readOnly: true,
                      onTap: () async {
                        final DateTime? picked = await showDatePicker(
                          context: context,
                          initialDate: DateTime.now().subtract(const Duration(days: 365 * 18)),
                          firstDate: DateTime(1900),
                          lastDate: DateTime.now(),
                        );
                        if (picked != null) {
                          final day = picked.day.toString().padLeft(2, '0');
                          final month = picked.month.toString().padLeft(2, '0');
                          final year = picked.year.toString();
                          setModalState(() {
                            dobCtrl.text = "$day-$month-$year";
                          });
                        }
                      },
                      decoration: const InputDecoration(
                        labelText: 'Date of Birth (DD-MM-YYYY) *',
                        border: OutlineInputBorder(),
                        hintText: 'Select Date of Birth',
                        suffixIcon: Icon(Icons.calendar_today),
                      ),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      value: gender,
                      decoration: const InputDecoration(labelText: 'Gender', border: OutlineInputBorder()),
                      items: const [
                        DropdownMenuItem(value: 'male', child: Text('Male')),
                        DropdownMenuItem(value: 'female', child: Text('Female')),
                        DropdownMenuItem(value: 'other', child: Text('Other')),
                      ],
                      onChanged: (val) {
                        if (val != null) setModalState(() => gender = val);
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: addressCtrl,
                      decoration: const InputDecoration(labelText: 'Aadhaar Address', border: OutlineInputBorder()),
                    ),
                    const SizedBox(height: 12),
                    if (isLoadingTrades)
                      const Center(child: CircularProgressIndicator())
                    else
                      DropdownButtonFormField<String>(
                        value: selectedJobProfile,
                        decoration: const InputDecoration(labelText: 'Job Profile / Trade *', border: OutlineInputBorder()),
                        items: trades.map((t) => DropdownMenuItem(value: t.name, child: Text(t.name))).toList(),
                        onChanged: (val) {
                          if (val != null) setModalState(() => selectedJobProfile = val);
                        },
                      ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () => setModalState(() => currentStep = 2),
                            child: const Text('Back'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: isLoading ? null : () async {
                              if (dobCtrl.text.isEmpty || addressCtrl.text.isEmpty || selectedJobProfile == null) {
                                setModalState(() => errorMsg = 'Please fill DOB, Address, and select a Job Profile.');
                                return;
                              }
                              setModalState(() { isLoading = true; errorMsg = null; });
                              
                              final List<Map<String, dynamic>> familyPayload = parsedMembers.map((m) => {
                                'name': m['name'],
                                'relation': m['relation'],
                                'gender': m['gender'],
                                'age': m['age'],
                              }).toList();

                              String apiDob = '';
                              if (dobCtrl.text.isNotEmpty) {
                                final parts = dobCtrl.text.split('-');
                                if (parts.length == 3) {
                                  apiDob = '${parts[2]}-${parts[1]}-${parts[0]}';
                                }
                              }

                              final res = await _apiService.registerBeneficiaryComplete({
                                'full_name': nameCtrl.text.trim(),
                                'mobile': mobileCtrl.text.trim(),
                                'dob': apiDob,
                                'gender': gender,
                                'aadhaar_address': addressCtrl.text.trim(),
                                'ration_card_number': rationNoCtrl.text.trim(),
                                'job_profile': selectedJobProfile!,
                                'family': familyPayload,
                              });

                              setModalState(() { isLoading = false; });

                              if (res['success'] == true) {
                                Navigator.pop(ctx);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Beneficiary registered successfully!'), backgroundColor: Colors.green),
                                );
                                _fetchWorkers();
                                _fetchStats();
                              } else {
                                setModalState(() => errorMsg = res['message'] ?? 'Registration failed.');
                              }
                            },
                            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF0F172A)),
                            child: isLoading
                                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                                : const Text('Register Beneficiary', style: TextStyle(color: Colors.white)),
                        ),
                      ),
                    ],
                  ),
                ],
              ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _showAddSchemeSheet() {
    final titleCtrl = TextEditingController();
    final descCtrl = TextEditingController();
    final urlCtrl = TextEditingController();
    bool isSaving = false;
    String? errorMsg;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            left: 20, right: 20, top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  children: [
                    const Text('Add New Scheme', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    const Spacer(),
                    IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                  ],
                ),
                const SizedBox(height: 16),
                if (errorMsg != null) ...[
                  Text(errorMsg!, style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                ],
                TextField(
                  controller: titleCtrl,
                  decoration: const InputDecoration(labelText: 'Scheme Title', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: descCtrl,
                  maxLines: 3,
                  decoration: const InputDecoration(labelText: 'Description', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: urlCtrl,
                  decoration: const InputDecoration(labelText: 'Apply URL', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: isSaving ? null : () async {
                    if (titleCtrl.text.isEmpty || descCtrl.text.isEmpty || urlCtrl.text.isEmpty) {
                      setModalState(() => errorMsg = 'Please fill all fields.');
                      return;
                    }
                    setModalState(() => isSaving = true);
                    final res = await _apiService.createScheme(
                      titleCtrl.text.trim(),
                      descCtrl.text.trim(),
                      urlCtrl.text.trim(),
                    );
                    setModalState(() => isSaving = false);
                    if (res['success'] == true) {
                      Navigator.pop(ctx);
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Scheme submitted successfully!'), backgroundColor: Colors.green),
                      );
                      _fetchSchemes();
                      _fetchStats();
                    } else {
                      setModalState(() => errorMsg = res['message'] ?? 'Failed to submit scheme.');
                    }
                  },
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF0F172A)),
                  child: isSaving
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Submit Scheme', style: TextStyle(color: Colors.white)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ─── MAIN BUILD METHOD ────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final tabs = _getTabs();
    final tabName = _getTabName(_currentIndex);

    // Map active view based on selected tab label
    Widget activeBody;
    switch (tabName) {
      case 'Dashboard':
        activeBody = _buildDashboardTab();
        break;
      case 'Staff':
        activeBody = _buildStaffTab();
        break;
      case 'Employer':
        activeBody = _buildEmployersTab();
        break;
      case 'Contractors':
        activeBody = _buildContractorsTab();
        break;
      case 'Beneficiaries':
        activeBody = _buildBeneficiariesTab();
        break;
      case 'Schemes':
        activeBody = _buildSchemesTab();
        break;
      default:
        activeBody = const Center(child: Text('Unknown Tab'));
    }

    final showFAB = (tabName == 'Staff' || tabName == 'Employer' || tabName == 'Contractors' || tabName == 'Beneficiaries' || tabName == 'Schemes');
    IconData fabIcon = Icons.add;
    VoidCallback? fabAction;

    if (tabName == 'Staff') {
      fabIcon = Icons.group_add_rounded;
      fabAction = _showOnboardStaffDialog;
    } else if (tabName == 'Employer') {
      fabIcon = Icons.add_business_rounded;
      fabAction = _showOnboardUserDialog;
    } else if (tabName == 'Contractors') {
      fabIcon = Icons.add_moderator_rounded;
      fabAction = _showOnboardUserDialog;
    } else if (tabName == 'Beneficiaries') {
      fabIcon = Icons.person_add_alt_1_rounded;
      fabAction = _showOnboardBeneficiaryDialog;
    } else if (tabName == 'Schemes') {
      fabIcon = Icons.post_add_rounded;
      fabAction = _showAddSchemeSheet;
    }

    final String appBarTitle = _userRole == 'ngo'
        ? 'NGO Admin Console'
        : _userRole == 'employer'
            ? 'Employer Console'
            : 'Contractor Console';

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Text(appBarTitle, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF0F172A),
        elevation: 1,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            onPressed: _handleLogout,
          )
        ],
      ),
      body: activeBody,
      floatingActionButton: showFAB
          ? FloatingActionButton(
              onPressed: fabAction,
              backgroundColor: const Color(0xFF0F172A),
              foregroundColor: Colors.white,
              child: Icon(fabIcon),
            )
          : null,
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        selectedItemColor: const Color(0xFF0F172A),
        unselectedItemColor: Colors.grey.shade500,
        type: BottomNavigationBarType.fixed,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
            _searchController.clear();
          });
          _applySearch();
        },
        items: tabs.map((t) {
          return BottomNavigationBarItem(
            icon: Icon(t['icon'] as IconData),
            label: t['label'] as String,
          );
        }).toList(),
      ),
    );
  }
}
