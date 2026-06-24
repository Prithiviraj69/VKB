import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../services/api_service.dart';
import '../services/csv_downloader.dart' as downloader;
import 'super_admin_login.dart';

class SuperAdminDashboardScreen extends StatefulWidget {
  const SuperAdminDashboardScreen({super.key});

  @override
  State<SuperAdminDashboardScreen> createState() => _SuperAdminDashboardScreenState();
}

class _SuperAdminDashboardScreenState extends State<SuperAdminDashboardScreen> {
  final _apiService = ApiService();
  String _activeTab = 'users'; // 'users' or 'labour'
  String? _adminName = 'Super Admin';
  String? _adminEmail = '';
  String? _token;

  // --- User Management State ---
  List<dynamic> _usersList = [];
  bool _isLoadingUsers = false;
  final _createUserFormKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  String _selectedRole = 'approver'; // 'approver' or 'verifier'
  bool _isCreatingUser = false;
  String? _userMessage;
  bool _userMessageIsError = false;

  // --- Labour Data State ---
  List<dynamic> _labourList = [];
  bool _isLoadingLabour = false;
  String _searchName = '';
  String _selectedStatus = ''; // empty means 'All'
  int _currentPage = 1;
  int _totalPages = 1;
  int _totalLabourRecords = 0;
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadAdminData();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadAdminData() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _adminName = prefs.getString('admin_name') ?? 'Super Admin';
      _adminEmail = prefs.getString('admin_email') ?? '';
      _token = prefs.getString('auth_token');
    });

    if (_token == null) {
      _redirectToLogin();
    } else {
      _fetchUsers();
      _fetchLabourData();
    }
  }

  void _redirectToLogin() {
    Future.delayed(Duration.zero, () {
      if (mounted) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const SuperAdminLoginScreen()),
        );
      }
    });
  }

  Future<void> _handleLogout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('admin_name');
    await prefs.remove('admin_email');
    _redirectToLogin();
  }

  // ==================== USER MANAGEMENT METHODS ====================

  Future<void> _fetchUsers() async {
    if (_token == null) return;
    setState(() => _isLoadingUsers = true);

    try {
      final response = await http.get(
        Uri.parse('${_apiService.baseUrl}/super-admin/users'),
        headers: {
          'Authorization': 'Bearer $_token',
          'Accept': 'application/json',
        },
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        setState(() {
          _usersList = data['data'] ?? [];
        });
      }
    } catch (_) {} finally {
      setState(() => _isLoadingUsers = false);
    }
  }

  Future<void> _createUser() async {
    if (!_createUserFormKey.currentState!.validate() || _token == null) return;

    setState(() {
      _isCreatingUser = true;
      _userMessage = null;
    });

    try {
      final response = await http.post(
        Uri.parse('${_apiService.baseUrl}/super-admin/create-user'),
        headers: {
          'Authorization': 'Bearer $_token',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'name': _nameController.text.trim(),
          'email': _emailController.text.trim(),
          'password': _passwordController.text,
          'role': _selectedRole,
        }),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 201 && data['success'] == true) {
        setState(() {
          _userMessage = 'User created successfully!';
          _userMessageIsError = false;
          _nameController.clear();
          _emailController.clear();
          _passwordController.clear();
          _selectedRole = 'approver';
        });
        _fetchUsers();
      } else {
        setState(() {
          _userMessage = data['message'] ?? 'Failed to create user.';
          _userMessageIsError = true;
        });
      }
    } catch (e) {
      setState(() {
        _userMessage = 'Connection error.';
        _userMessageIsError = true;
      });
    } finally {
      setState(() => _isCreatingUser = false);
    }
  }

  Future<void> _toggleUserStatus(String userId, bool currentStatus) async {
    if (_token == null) return;

    try {
      final response = await http.patch(
        Uri.parse('${_apiService.baseUrl}/super-admin/user/$userId/toggle-status'),
        headers: {
          'Authorization': 'Bearer $_token',
          'Accept': 'application/json',
        },
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        _fetchUsers();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(data['message'] ?? 'Status updated'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (_) {}
  }

  Future<void> _deleteUser(String userId, bool isLabour) async {
    if (_token == null) return;

    try {
      final response = await http.delete(
        Uri.parse('${_apiService.baseUrl}/super-admin/user/$userId'),
        headers: {
          'Authorization': 'Bearer $_token',
          'Accept': 'application/json',
        },
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(data['message'] ?? 'Deleted successfully'),
            backgroundColor: Colors.green,
          ),
        );
        if (isLabour) {
          _fetchLabourData(page: _currentPage);
        } else {
          _fetchUsers();
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(data['message'] ?? 'Delete failed'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Connection error while deleting'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _confirmDeleteUser(String userId, String name, {bool isLabour = false}) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirm Delete', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text('Are you sure you want to permanently delete $name? This action cannot be undone and will delete all associated records.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(ctx).pop();
              _deleteUser(userId, isLabour);
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Delete', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  // ==================== LABOUR DATA METHODS ====================

  Future<void> _fetchLabourData({int page = 1}) async {
    if (_token == null) return;
    setState(() => _isLoadingLabour = true);

    try {
      String url = '${_apiService.baseUrl}/super-admin/labour-data?page=$page';
      if (_selectedStatus.isNotEmpty) {
        url += '&status=$_selectedStatus';
      }
      if (_searchName.isNotEmpty) {
        url += '&search=${Uri.encodeComponent(_searchName)}';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $_token',
          'Accept': 'application/json',
        },
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        final paginatedData = data['data'];
        setState(() {
          _labourList = paginatedData['data'] ?? [];
          _currentPage = paginatedData['current_page'] ?? 1;
          _totalPages = paginatedData['last_page'] ?? 1;
          _totalLabourRecords = paginatedData['total'] ?? 0;
        });
      }
    } catch (_) {} finally {
      setState(() => _isLoadingLabour = false);
    }
  }

  Future<void> _exportToCsv() async {
    if (_token == null) return;

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Generating CSV export...'), duration: Duration(seconds: 1)),
    );

    try {
      String url = '${_apiService.baseUrl}/super-admin/labour-data?export=true';
      if (_selectedStatus.isNotEmpty) {
        url += '&status=$_selectedStatus';
      }
      if (_searchName.isNotEmpty) {
        url += '&search=${Uri.encodeComponent(_searchName)}';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $_token',
          'Accept': 'application/json',
        },
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        final List<dynamic> exportList = data['data'] ?? [];
        if (exportList.isEmpty) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('No records found to export.'), backgroundColor: Colors.red),
          );
          return;
        }

        // Create CSV Content
        final header = 'Name,DOB,Gender,Marital Status,Job Profile,Status,Address,Created At\n';
        final buffer = StringBuffer();
        buffer.write(header);

        for (var item in exportList) {
          final name = _escapeCsvValue(item['name']);
          final dob = _escapeCsvValue(item['dob']);
          final gender = _escapeCsvValue(item['gender']);
          final marital = _escapeCsvValue(item['marital_status']);
          final job = _escapeCsvValue(item['job_profile']);
          final status = _escapeCsvValue(item['status']);
          final address = _escapeCsvValue(item['address']);
          final createdAt = _escapeCsvValue(item['created_at']);

          buffer.write('$name,$dob,$gender,$marital,$job,$status,$address,$createdAt\n');
        }

        final fileName = 'Labour_Registrations_${DateTime.now().millisecondsSinceEpoch}.csv';
        downloader.downloadCsv(buffer.toString(), fileName);

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('CSV download triggered successfully!'), backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error exporting CSV: $e'), backgroundColor: Colors.red),
      );
    }
  }

  String _escapeCsvValue(dynamic val) {
    if (val == null) return '';
    String strVal = val.toString().replaceAll('\r', '').replaceAll('\n', ' ');
    if (strVal.contains(',') || strVal.contains('"') || strVal.contains('\'')) {
      strVal = strVal.replaceAll('"', '""');
      return '"$strVal"';
    }
    return strVal;
  }

  // ==================== RENDERING UI METHODS ====================

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Row(
        children: [
          // Sidebar
          _buildSidebar(),

          // Main Content
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header
                _buildHeader(),

                // Tab Content
                Expanded(
                  child: Container(
                    color: const Color(0xFFF3F4F6),
                    padding: const EdgeInsets.all(24.0),
                    child: _activeTab == 'users' ? _buildUserManagementTab() : _buildLabourDataTab(),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSidebar() {
    return Container(
      width: 260,
      color: const Color(0xFF1E293B),
      child: Column(
        children: [
          // Logo Section
          Container(
            padding: const EdgeInsets.all(24.0),
            alignment: Alignment.center,
            decoration: const BoxDecoration(
              border: Border(bottom: BorderSide(color: Color(0xFF334155), width: 1)),
            ),
            child: Row(
              children: [
                const Icon(Icons.admin_panel_settings_rounded, color: Color(0xFFFF8F00), size: 28),
                const SizedBox(width: 12),
                const Text(
                  'VishwaKarma',
                  style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Navigation Links
          _buildSidebarItem(
            icon: Icons.people_rounded,
            title: 'User Management',
            tabName: 'users',
          ),
          _buildSidebarItem(
            icon: Icons.list_alt_rounded,
            title: 'Labour Data',
            tabName: 'labour',
          ),

          const Spacer(),

          // Admin Profile Summary
          Container(
            padding: const EdgeInsets.all(16.0),
            color: const Color(0xFF0F172A),
            child: Row(
              children: [
                const CircleAvatar(
                  backgroundColor: Color(0xFFFF8F00),
                  child: Icon(Icons.person, color: Colors.white),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        _adminName ?? 'Super Admin',
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        _adminEmail ?? '',
                        style: const TextStyle(color: Colors.grey, fontSize: 11),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.logout_rounded, color: Colors.redAccent, size: 20),
                  tooltip: 'Logout',
                  onPressed: _handleLogout,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSidebarItem({required IconData icon, required String title, required String tabName}) {
    final isActive = _activeTab == tabName;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12.0, vertical: 4.0),
      child: ListTile(
        onTap: () {
          setState(() {
            _activeTab = tabName;
          });
        },
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        tileColor: isActive ? const Color(0xFFFF8F00) : Colors.transparent,
        leading: Icon(icon, color: isActive ? Colors.white : const Color(0xFF94A3B8)),
        title: Text(
          title,
          style: TextStyle(
            color: isActive ? Colors.white : const Color(0xFFE2E8F0),
            fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
            fontSize: 14,
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      height: 70,
      padding: const EdgeInsets.symmetric(horizontal: 24.0),
      decoration: const BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black12,
            blurRadius: 1,
            offset: Offset(0, 1),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            _activeTab == 'users' ? 'User Management Portal' : 'Labour Registrations Database',
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
          ),
          const Chip(
            label: Text('Super Admin Role', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
            backgroundColor: Color(0xFFE65100),
          ),
        ],
      ),
    );
  }

  // ==================== USER MANAGEMENT TAB ====================

  Widget _buildUserManagementTab() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Create User Form
        Container(
          width: 380,
          padding: const EdgeInsets.all(24.0),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: Colors.grey.withOpacity(0.1),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Form(
            key: _createUserFormKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'Create New Administrative User',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Register Approver and Verifier accounts.',
                  style: TextStyle(fontSize: 12, color: Colors.grey),
                ),
                const Divider(height: 32),

                if (_userMessage != null) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: _userMessageIsError ? Colors.red.shade50 : Colors.green.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: _userMessageIsError ? Colors.red.shade200 : Colors.green.shade200),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          _userMessageIsError ? Icons.error_outline : Icons.check_circle_outline,
                          color: _userMessageIsError ? Colors.red.shade700 : Colors.green.shade700,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _userMessage!,
                            style: TextStyle(
                              color: _userMessageIsError ? Colors.red.shade900 : Colors.green.shade900,
                              fontSize: 13,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                ],

                const Text('Full Name *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _nameController,
                  decoration: const InputDecoration(
                    hintText: 'e.g. Ramesh Sharma',
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                  validator: (v) => (v == null || v.isEmpty) ? 'Please enter name' : null,
                ),
                const SizedBox(height: 16),

                const Text('Email Address *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    hintText: 'e.g. ramesh@company.com',
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                  validator: (v) {
                    if (v == null || v.isEmpty) return 'Please enter email';
                    if (!RegExp(r'^[^@]+@[^@]+\.[^@]+').hasMatch(v)) return 'Enter valid email';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                const Text('Password *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    hintText: 'Minimum 6 characters',
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                  validator: (v) => (v == null || v.length < 6) ? 'Password must be >= 6 chars' : null,
                ),
                const SizedBox(height: 16),

                const Text('Role Assignment *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: _selectedRole,
                  decoration: const InputDecoration(
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'approver', child: Text('Approver')),
                    DropdownMenuItem(value: 'verifier', child: Text('Verifier')),
                  ],
                  onChanged: (val) {
                    if (val != null) {
                      setState(() {
                        _selectedRole = val;
                      });
                    }
                  },
                ),
                const SizedBox(height: 32),

                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton(
                    onPressed: _isCreatingUser ? null : _createUser,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFE65100),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: _isCreatingUser
                        ? const CircularProgressIndicator(color: Colors.white)
                        : const Text('Create Administrative User', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
          ),
        ),

        const SizedBox(width: 24),

        // Users List
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(24.0),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Users List',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                    ),
                    IconButton(
                      icon: const Icon(Icons.refresh_rounded, color: Color(0xFFE65100)),
                      onPressed: _fetchUsers,
                      tooltip: 'Refresh Users',
                    ),
                  ],
                ),
                const Divider(height: 24),

                Expanded(
                  child: _isLoadingUsers
                      ? const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)))
                      : _usersList.isEmpty
                          ? const Center(child: Text('No administrative users registered yet.'))
                          : SingleChildScrollView(
                              scrollDirection: Axis.vertical,
                              child: Table(
                                columnWidths: const {
                                  0: FlexColumnWidth(2),
                                  1: FlexColumnWidth(3),
                                  2: FlexColumnWidth(1.5),
                                  3: FlexColumnWidth(1.2),
                                  4: FlexColumnWidth(1.2),
                                  5: FlexColumnWidth(1.2),
                                },
                                border: TableBorder(
                                  horizontalInside: BorderSide(color: Colors.grey.shade200, width: 1),
                                ),
                                children: [
                                  // Header Row
                                  TableRow(
                                    decoration: BoxDecoration(color: Colors.grey.shade50),
                                    children: const [
                                      Padding(padding: EdgeInsets.all(12), child: Text('Name', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Email', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Role', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Status', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Toggle Status', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Delete', style: TextStyle(fontWeight: FontWeight.bold))),
                                    ],
                                  ),
                                  // Data Rows
                                  ..._usersList.map((user) {
                                    final userId = user['id'] ?? user['_id'] ?? '';
                                    final isActive = user['is_active'] == true || user['is_active'] == 1;
                                    final userRole = user['role'] ?? 'worker';

                                    return TableRow(
                                      children: [
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(user['name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.w500)),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(user['email'] ?? user['mobile'] ?? 'N/A'),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Chip(
                                            label: Text(
                                              userRole.toString().toUpperCase(),
                                              style: TextStyle(
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                                color: userRole == 'approver'
                                                    ? Colors.blue.shade900
                                                    : userRole == 'verifier'
                                                        ? Colors.teal.shade900
                                                        : Colors.orange.shade900,
                                              ),
                                            ),
                                            backgroundColor: userRole == 'approver'
                                                ? Colors.blue.shade100
                                                : userRole == 'verifier'
                                                    ? Colors.teal.shade100
                                                    : Colors.orange.shade100,
                                            padding: EdgeInsets.zero,
                                          ),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(
                                            isActive ? 'Active' : 'Inactive',
                                            style: TextStyle(
                                              color: isActive ? Colors.green : Colors.red,
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(4),
                                          child: Switch(
                                            value: isActive,
                                            activeColor: Colors.green,
                                            onChanged: (val) {
                                              _toggleUserStatus(userId, isActive);
                                            },
                                          ),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(4),
                                          child: IconButton(
                                            icon: const Icon(Icons.delete_forever_rounded, color: Colors.red),
                                            onPressed: () {
                                              _confirmDeleteUser(userId, user['name'] ?? 'this user');
                                            },
                                          ),
                                        ),
                                      ],
                                    );
                                  }),
                                ],
                              ),
                            ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // ==================== LABOUR DATA TAB ====================

  Widget _buildLabourDataTab() {
    return Container(
      padding: const EdgeInsets.all(24.0),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Filter Bar
          Row(
            children: [
              // Search input
              Expanded(
                child: TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFFE65100)),
                    hintText: 'Search labourers by name...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  onChanged: (val) {
                    setState(() {
                      _searchName = val.trim();
                    });
                    _fetchLabourData(page: 1);
                  },
                ),
              ),
              const SizedBox(width: 16),

              // Status Dropdown filter
              Container(
                width: 200,
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<String>(
                    value: _selectedStatus,
                    items: const [
                      DropdownMenuItem(value: '', child: Text('All Statuses')),
                      DropdownMenuItem(value: 'pending', child: Text('Pending')),
                      DropdownMenuItem(value: 'approved', child: Text('Approved')),
                      DropdownMenuItem(value: 'rejected', child: Text('Rejected')),
                    ],
                    onChanged: (val) {
                      if (val != null) {
                        setState(() {
                          _selectedStatus = val;
                        });
                        _fetchLabourData(page: 1);
                      }
                    },
                  ),
                ),
              ),
              const SizedBox(width: 16),

              // Export CSV Button
              SizedBox(
                height: 48,
                child: ElevatedButton.icon(
                  onPressed: _exportToCsv,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2E7D32),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  icon: const Icon(Icons.download_rounded),
                  label: const Text('Export to CSV', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),

          const SizedBox(height: 24),

          // Labour List Table
          Expanded(
            child: _isLoadingLabour
                ? const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)))
                : _labourList.isEmpty
                    ? const Center(child: Text('No labour registration records matching filters.'))
                    : Column(
                        children: [
                          Expanded(
                            child: SingleChildScrollView(
                              scrollDirection: Axis.vertical,
                              child: Table(
                                columnWidths: const {
                                  0: FlexColumnWidth(2),
                                  1: FlexColumnWidth(1.2),
                                  2: FlexColumnWidth(1),
                                  3: FlexColumnWidth(1.2),
                                  4: FlexColumnWidth(1.5),
                                  5: FlexColumnWidth(1.2),
                                  6: FlexColumnWidth(2.5),
                                  7: FlexColumnWidth(1.2),
                                  8: FlexColumnWidth(1),
                                },
                                border: TableBorder(
                                  horizontalInside: BorderSide(color: Colors.grey.shade200, width: 1),
                                ),
                                children: [
                                  TableRow(
                                    decoration: BoxDecoration(color: Colors.grey.shade50),
                                    children: const [
                                      Padding(padding: EdgeInsets.all(12), child: Text('Name', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('DOB', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Gender', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Marital Status', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Job Profile', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Status', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Address', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Reg Date', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Delete', style: TextStyle(fontWeight: FontWeight.bold))),
                                    ],
                                  ),
                                  ..._labourList.map((labour) {
                                    final status = labour['status']?.toString().toLowerCase() ?? 'pending';
                                    final dateStr = labour['created_at'] != null 
                                        ? labour['created_at'].toString().split('T').first 
                                        : 'N/A';
                                    final labourId = labour['id'] ?? labour['_id'] ?? '';

                                    return TableRow(
                                      children: [
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(labour['name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.w500)),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(labour['dob'] ?? 'N/A'),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(labour['gender']?.toString().toUpperCase() ?? 'N/A'),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(labour['marital_status'] ?? 'N/A'),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(labour['job_profile'] ?? 'N/A'),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Chip(
                                            label: Text(
                                              status.toUpperCase(),
                                              style: TextStyle(
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                                color: status == 'approved'
                                                    ? Colors.green.shade900
                                                    : status == 'rejected'
                                                        ? Colors.red.shade900
                                                        : Colors.orange.shade900,
                                              ),
                                            ),
                                            backgroundColor: status == 'approved'
                                                ? Colors.green.shade100
                                                : status == 'rejected'
                                                    ? Colors.red.shade100
                                                    : Colors.orange.shade100,
                                            padding: EdgeInsets.zero,
                                          ),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(labour['address'] ?? 'N/A', maxLines: 2, overflow: TextOverflow.ellipsis),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(dateStr),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(4),
                                          child: IconButton(
                                            icon: const Icon(Icons.delete_forever_rounded, color: Colors.red),
                                            onPressed: () {
                                              _confirmDeleteUser(labourId, labour['name'] ?? 'this labour', isLabour: true);
                                            },
                                          ),
                                        ),
                                      ],
                                    );
                                  }),
                                ],
                              ),
                            ),
                          ),

                          // Pagination Controls
                          const SizedBox(height: 16),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Total Records: $_totalLabourRecords',
                                style: const TextStyle(color: Colors.grey, fontSize: 13),
                              ),
                              Row(
                                children: [
                                  IconButton(
                                    icon: const Icon(Icons.chevron_left_rounded),
                                    onPressed: _currentPage > 1
                                        ? () => _fetchLabourData(page: _currentPage - 1)
                                        : null,
                                  ),
                                  Text(
                                    'Page $_currentPage of $_totalPages',
                                    style: const TextStyle(fontWeight: FontWeight.bold),
                                  ),
                                  IconButton(
                                    icon: const Icon(Icons.chevron_right_rounded),
                                    onPressed: _currentPage < _totalPages
                                        ? () => _fetchLabourData(page: _currentPage + 1)
                                        : null,
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ],
                      ),
          ),
        ],
      ),
    );
  }
}
