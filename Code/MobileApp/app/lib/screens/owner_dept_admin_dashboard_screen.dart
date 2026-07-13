import 'dart:convert';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../models/models.dart';
import 'login_screen.dart';
import 'package:file_picker/file_picker.dart';


class OwnerDeptAdminDashboardScreen extends StatefulWidget {
  const OwnerDeptAdminDashboardScreen({super.key});

  @override
  State<OwnerDeptAdminDashboardScreen> createState() => _OwnerDeptAdminDashboardScreenState();
}

class _OwnerDeptAdminDashboardScreenState extends State<OwnerDeptAdminDashboardScreen> {
  final ApiService _apiService = ApiService();
  int _currentIndex = 0;
  int _selectedSubTab = 0;

  // State data
  bool _isLoadingStats = true;
  bool _isLoadingUsers = true;
  bool _isLoadingProfile = true;
  bool _isLoadingWorkers = true;

  Map<String, dynamic> _stats = {
    'verifiers': 0,
    'approvers': 0,
    'pending_workers': 0,
    'verified_workers': 0,
    'approved_workers': 0,
  };

  List<dynamic> _allUsers = [];
  List<dynamic> _users = [];
  List<dynamic> _filteredUsers = [];
  Map<String, dynamic>? _department;
  int _selectedRegistryTab = 0;
  int _selectedOnboardTab = 0; // 0=NGO 1=Employer 2=Contractor
  bool _isLoadingOnboard = false;

  List<dynamic> _workersPending = [];
  List<dynamic> _workersVerified = [];
  List<dynamic> _workersApproved = [];
  List<dynamic> _workersRejected = [];
  List<dynamic> _filteredWorkersPending = [];
  List<dynamic> _filteredWorkersVerified = [];
  List<dynamic> _filteredWorkersApproved = [];
  List<dynamic> _filteredWorkersRejected = [];

  final TextEditingController _searchController = TextEditingController();
  String _selectedFilterRole = 'all';

  // Logged-in admin info
  String _adminName = '';
  String _adminEmail = '';
  String _adminRole = '';

  // Schemes Management State
  List<Scheme> _schemesList = [];
  bool _isLoadingSchemes = true;

  // Password Management Controllers & State
  final _currentPassCtrl = TextEditingController();
  final _newPassCtrl = TextEditingController();
  final _confirmNewPassCtrl = TextEditingController();
  bool _isUpdatingPassword = false;
  String? _passErrorMsg;
  String? _passSuccessMsg;

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
    _fetchWorkers();
    _fetchSchemes();
  }

  List<dynamic> get _ngos => _allUsers.where((u) => u['role'] == 'ngo').toList();
  List<dynamic> get _employers => _allUsers.where((u) => u['role'] == 'employer').toList();
  List<dynamic> get _contractors => _allUsers.where((u) => u['role'] == 'contractor').toList();

  @override
  void dispose() {
    _searchController.dispose();
    _currentPassCtrl.dispose();
    _newPassCtrl.dispose();
    _confirmNewPassCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadAdminInfo() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _adminName = prefs.getString('user_name') ?? 'Department Admin';
      _adminEmail = prefs.getString('user_email') ?? '';
      _adminRole = prefs.getString('user_role') ?? '';
    });
  }

  Future<void> _fetchSchemes() async {
    setState(() => _isLoadingSchemes = true);
    try {
      final list = await _apiService.getSchemes();
      setState(() {
        _schemesList = list;
        _isLoadingSchemes = false;
      });
    } catch (_) {
      setState(() => _isLoadingSchemes = false);
    }
  }

  void _showAddSchemeDialog() {
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
        builder: (context, setModalState) {
          return Padding(
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
                    children: [
                      const Text(
                        'Add New Scheme',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const Spacer(),
                      IconButton(
                        onPressed: () => Navigator.pop(ctx),
                        icon: const Icon(Icons.close),
                      ),
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
                  TextField(
                    controller: titleCtrl,
                    decoration: const InputDecoration(labelText: 'Scheme Title', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: descCtrl,
                    maxLines: 3,
                    decoration: const InputDecoration(labelText: 'Scheme Description', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: urlCtrl,
                    decoration: const InputDecoration(labelText: 'Apply URL (website)', hintText: 'http://example.com', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: isSaving ? null : () async {
                      final title = titleCtrl.text.trim();
                      final desc = descCtrl.text.trim();
                      final url = urlCtrl.text.trim();

                      if (title.isEmpty || desc.isEmpty || url.isEmpty) {
                        setModalState(() => errorMsg = 'Please fill all fields');
                        return;
                      }

                      if (!url.startsWith('http://') && !url.startsWith('https://')) {
                        setModalState(() => errorMsg = 'Apply URL must start with http:// or https://');
                        return;
                      }

                      setModalState(() { isSaving = true; errorMsg = null; });
                      final res = await _apiService.createScheme(title, desc, url);
                      setModalState(() { isSaving = false; });

                      if (res['success'] == true) {
                        Navigator.pop(ctx);
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Scheme added successfully!'), backgroundColor: Colors.green),
                        );
                        _fetchSchemes();
                      } else {
                        setModalState(() => errorMsg = res['message'] ?? 'Failed to add scheme');
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0F172A),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    child: isSaving
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Add Scheme', style: TextStyle(color: Colors.white)),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _showEditSchemeDialog(Scheme scheme) {
    final titleCtrl = TextEditingController(text: scheme.name);
    final descCtrl = TextEditingController(text: scheme.description);
    final urlCtrl = TextEditingController(text: scheme.applyUrl ?? '');
    bool isSaving = false;
    String? errorMsg;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          return Padding(
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
                    children: [
                      const Text(
                        'Edit Scheme',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const Spacer(),
                      IconButton(
                        onPressed: () => Navigator.pop(ctx),
                        icon: const Icon(Icons.close),
                      ),
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
                  TextField(
                    controller: titleCtrl,
                    decoration: const InputDecoration(labelText: 'Scheme Title', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: descCtrl,
                    maxLines: 3,
                    decoration: const InputDecoration(labelText: 'Scheme Description', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: urlCtrl,
                    decoration: const InputDecoration(labelText: 'Apply URL (website)', hintText: 'http://example.com', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: isSaving ? null : () async {
                      final title = titleCtrl.text.trim();
                      final desc = descCtrl.text.trim();
                      final url = urlCtrl.text.trim();

                      if (title.isEmpty || desc.isEmpty || url.isEmpty) {
                        setModalState(() => errorMsg = 'Please fill all fields');
                        return;
                      }

                      if (!url.startsWith('http://') && !url.startsWith('https://')) {
                        setModalState(() => errorMsg = 'Apply URL must start with http:// or https://');
                        return;
                      }

                      setModalState(() { isSaving = true; errorMsg = null; });
                      final res = await _apiService.updateScheme(scheme.id, title, desc, url);
                      setModalState(() { isSaving = false; });

                      if (res['success'] == true) {
                        Navigator.pop(ctx);
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Scheme updated successfully!'), backgroundColor: Colors.green),
                        );
                        _fetchSchemes();
                      } else {
                        setModalState(() => errorMsg = res['message'] ?? 'Failed to update scheme');
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0F172A),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    child: isSaving
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Update Scheme', style: TextStyle(color: Colors.white)),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _confirmDeleteScheme(Scheme scheme) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Scheme', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text('Are you sure you want to delete "${scheme.name}"? This action cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await _apiService.deleteScheme(scheme.id);
              if (res['success'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Scheme deleted successfully!'), backgroundColor: Colors.green),
                );
                _fetchSchemes();
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Failed to delete scheme'), backgroundColor: Colors.red),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Delete', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  Widget _buildManageSchemesTab() {
    if (_isLoadingSchemes) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_schemesList.isEmpty) {
      return RefreshIndicator(
        onRefresh: _fetchSchemes,
        child: Center(
          child: ListView(
            shrinkWrap: true,
            children: [
              Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.assignment_late_rounded, size: 48, color: Colors.grey.shade400),
                  const SizedBox(height: 12),
                  Text(
                    'No schemes added yet',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.grey.shade500),
                  ),
                ],
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchSchemes,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _schemesList.length,
        itemBuilder: (context, index) {
          final scheme = _schemesList[index];
          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            elevation: 1,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.assignment_rounded, color: Colors.indigo.shade700, size: 24),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          scheme.name,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: scheme.isActive ? Colors.green.shade50 : Colors.red.shade50,
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: scheme.isActive ? Colors.green.shade200 : Colors.red.shade200),
                        ),
                        child: Text(
                          scheme.isActive ? 'Active' : 'Inactive',
                          style: TextStyle(color: scheme.isActive ? Colors.green.shade800 : Colors.red.shade800, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    scheme.description,
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 14),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Code: ${scheme.code ?? 'N/A'}',
                        style: TextStyle(color: Colors.grey.shade500, fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                      TextButton.icon(
                        onPressed: () async {
                          if (scheme.applyUrl != null) {
                            final uri = Uri.tryParse(scheme.applyUrl!);
                            if (uri != null && await canLaunchUrl(uri)) {
                              await launchUrl(uri);
                            }
                          }
                        },
                        icon: const Icon(Icons.open_in_new_rounded, size: 16),
                        label: const Text('Visit Link'),
                      ),
                    ],
                  ),
                  const Divider(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      TextButton.icon(
                        onPressed: () async {
                          final res = await _apiService.toggleSchemeActive(scheme.id);
                          if (res['success'] == true) {
                            _fetchSchemes();
                          }
                        },
                        icon: Icon(scheme.isActive ? Icons.toggle_on_rounded : Icons.toggle_off_rounded, color: scheme.isActive ? Colors.green : Colors.grey, size: 20),
                        label: Text(scheme.isActive ? 'Deactivate' : 'Activate', style: TextStyle(color: scheme.isActive ? Colors.green.shade700 : Colors.grey.shade700, fontSize: 13, fontWeight: FontWeight.bold)),
                      ),
                      const Spacer(),
                      IconButton(
                        onPressed: () => _showEditSchemeDialog(scheme),
                        icon: const Icon(Icons.edit_rounded, color: Colors.blue, size: 20),
                        tooltip: 'Edit Scheme',
                      ),
                      IconButton(
                        onPressed: () => _confirmDeleteScheme(scheme),
                        icon: const Icon(Icons.delete_rounded, color: Colors.red, size: 20),
                        tooltip: 'Delete Scheme',
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
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
        _allUsers = res['data'] ?? [];
        // Filter out beneficiaries, since department admins only manage verifiers and approvers in their Staff tab
        _users = _allUsers.where((u) => u['role'] == 'dept-verifier' || u['role'] == 'dept-approver' || u['role'] == 'verifier' || u['role'] == 'approver').toList();
        _filteredUsers = List.from(_users);
        _isLoadingUsers = false;
      });
    } else {
      setState(() {
        _isLoadingUsers = false;
      });
    }
  }

  Future<void> _fetchWorkers() async {
    setState(() {
      _isLoadingWorkers = true;
    });

    final resPending = await _apiService.getWorkers('pending');
    final resVerified = await _apiService.getWorkers('verified');
    final resApproved = await _apiService.getWorkers('approved');
    final resRejected = await _apiService.getWorkers('rejected');

    if (mounted) {
      setState(() {
        _workersPending = resPending['success'] == true ? (resPending['data'] ?? []) : [];
        _workersVerified = resVerified['success'] == true ? (resVerified['data'] ?? []) : [];
        _workersApproved = resApproved['success'] == true ? (resApproved['data'] ?? []) : [];
        _workersRejected = resRejected['success'] == true ? (resRejected['data'] ?? []) : [];

        _filteredWorkersPending = List.from(_workersPending);
        _filteredWorkersVerified = List.from(_workersVerified);
        _filteredWorkersApproved = List.from(_workersApproved);
        _filteredWorkersRejected = List.from(_workersRejected);

        _isLoadingWorkers = false;
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
      if (_currentIndex == 1) {
        _filteredUsers = _users.where((user) {
          final matchesSearch = query.isEmpty ||
              (user['name']?.toString().toLowerCase().contains(query) ?? false) ||
              (user['email']?.toString().toLowerCase().contains(query) ?? false) ||
              (user['mobile']?.toString().toLowerCase().contains(query) ?? false) ||
              (user['designation']?.toString().toLowerCase().contains(query) ?? false);

          bool matchesRole = true;
          if (_selectedFilterRole == 'verifier') {
            matchesRole = user['role'] == 'dept-verifier' || user['role'] == 'verifier';
          } else if (_selectedFilterRole == 'approver') {
            matchesRole = user['role'] == 'dept-approver' || user['role'] == 'approver';
          }

          return matchesSearch && matchesRole;
        }).toList();
      } else if (_currentIndex == 2) {
        _filteredWorkersPending = _workersPending.where((w) {
          final name = w['full_name']?.toString().toLowerCase() ?? '';
          final mobile = w['user_details']?['mobile']?.toString() ?? w['mobile_alt']?.toString() ?? '';
          final code = w['worker_code']?.toString().toLowerCase() ?? '';
          return query.isEmpty || name.contains(query) || mobile.contains(query) || code.contains(query);
        }).toList();

        _filteredWorkersVerified = _workersVerified.where((w) {
          final name = w['full_name']?.toString().toLowerCase() ?? '';
          final mobile = w['user_details']?['mobile']?.toString() ?? w['mobile_alt']?.toString() ?? '';
          final code = w['worker_code']?.toString().toLowerCase() ?? '';
          return query.isEmpty || name.contains(query) || mobile.contains(query) || code.contains(query);
        }).toList();

        _filteredWorkersApproved = _workersApproved.where((w) {
          final name = w['full_name']?.toString().toLowerCase() ?? '';
          final mobile = w['user_details']?['mobile']?.toString() ?? w['mobile_alt']?.toString() ?? '';
          final code = w['worker_code']?.toString().toLowerCase() ?? '';
          return query.isEmpty || name.contains(query) || mobile.contains(query) || code.contains(query);
        }).toList();

        _filteredWorkersRejected = _workersRejected.where((w) {
          final name = w['full_name']?.toString().toLowerCase() ?? '';
          final mobile = w['user_details']?['mobile']?.toString() ?? w['mobile_alt']?.toString() ?? '';
          final code = w['worker_code']?.toString().toLowerCase() ?? '';
          return query.isEmpty || name.contains(query) || mobile.contains(query) || code.contains(query);
        }).toList();
      }
    });
  }

  Future<void> _exportSelfRegisteredCSV() async {
    final List<dynamic> allSelfRegistered = [];
    final allLists = [_workersPending, _workersVerified, _workersApproved, _workersRejected];
    for (var list in allLists) {
      for (var w in list) {
        if (w['registration_source'] == 'self') {
          allSelfRegistered.add(w);
        }
      }
    }

    if (allSelfRegistered.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No self-registered beneficiaries found to export.')),
      );
      return;
    }

    String csv = 'Worker Code,Full Name,Father Name,Mobile,Date of Birth,Gender,Job Profile,Status,Created At\n';
    
    for (var w in allSelfRegistered) {
      final code = w['worker_code'] ?? 'N/A';
      final name = w['full_name'] ?? 'N/A';
      final father = w['father_name'] ?? 'N/A';
      final mobile = w['user_details']?['mobile'] ?? w['mobile_alt'] ?? 'N/A';
      final dob = w['dob'] ?? 'N/A';
      final gender = w['gender'] ?? 'N/A';
      final job = w['job_profile'] ?? 'N/A';
      final status = w['status'] ?? 'pending';
      final date = w['created_at'] != null ? w['created_at'].toString().split(' ')[0] : 'N/A';

      csv += '"$code","$name","$father","$mobile","$dob","$gender","$job","$status","$date"\n';
    }

    try {
      final bytes = utf8.encode(csv);
      final base64Csv = base64Encode(bytes);
      final url = 'data:text/csv;base64,$base64Csv';
      
      if (await canLaunchUrl(Uri.parse(url))) {
        await launchUrl(Uri.parse(url));
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not trigger download. Please try again.')),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error exporting CSV: $e')),
      );
    }
  }

  void _showSchemesListDialog(bool activeOnly) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A))),
    );
    
    try {
      final List<Scheme> allSchemes = await _apiService.getSchemes();
      if (mounted) {
        Navigator.pop(context); // pop loading dialog
      }

      final filtered = allSchemes.where((s) => s.isActive == activeOnly).toList();

      if (mounted) {
        showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
          builder: (ctx) => DraggableScrollableSheet(
            initialChildSize: 0.8,
            minChildSize: 0.5,
            maxChildSize: 0.95,
            expand: false,
            builder: (context, scrollController) => Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        activeOnly ? 'Active Schemes' : 'Inactive Schemes',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Expanded(
                    child: filtered.isEmpty
                        ? const Center(child: Text('No schemes found.'))
                        : ListView.builder(
                            controller: scrollController,
                            itemCount: filtered.length,
                            itemBuilder: (c, idx) {
                              final s = filtered[idx];
                              return Card(
                                margin: const EdgeInsets.only(bottom: 12),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                elevation: 0,
                                color: Colors.grey.shade50,
                                child: ListTile(
                                  contentPadding: const EdgeInsets.all(16),
                                  title: Text(s.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                                  subtitle: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const SizedBox(height: 6),
                                      Text(s.description, maxLines: 3, overflow: TextOverflow.ellipsis),
                                      const SizedBox(height: 8),
                                      Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                            decoration: BoxDecoration(
                                              color: activeOnly ? Colors.green.withOpacity(0.1) : Colors.red.withOpacity(0.1),
                                              borderRadius: BorderRadius.circular(6),
                                            ),
                                            child: Text(
                                              activeOnly ? 'Active' : 'Inactive',
                                              style: TextStyle(
                                                color: activeOnly ? Colors.green : Colors.red,
                                                fontSize: 11,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                          Text(s.ministry, style: TextStyle(color: Colors.grey.shade600, fontSize: 11)),
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
              ),
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        Navigator.pop(context); // pop loading if not already
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load schemes: $e')),
        );
      }
    }
  }

  Future<void> _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirm Logout'),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Logout', style: TextStyle(color: Colors.red)),
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

  void _showOnboardUserDialog() {
    final fullNameController = TextEditingController();
    final designationController = TextEditingController();
    final mobileController = TextEditingController();
    final emailController = TextEditingController();
    final passwordController = TextEditingController();
    final confirmPasswordController = TextEditingController();

    String selectedRole = 'dept-verifier'; // Default choice

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        bool isSubmitting = false;
        String? modalError;
        return StatefulBuilder(
          builder: (context, setModalState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: Row(
                children: const [
                  Icon(Icons.person_add_rounded, color: Color(0xFF0F172A)),
                  SizedBox(width: 8),
                  Text(
                    'Onboard User',
                    style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (modalError != null) ...[
                      Text(
                        modalError!,
                        style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                      const SizedBox(height: 12),
                    ],
                    const Text('Role *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: selectedRole,
                      items: const [
                        DropdownMenuItem(value: 'dept-verifier', child: Text("Department Verifier")),
                        DropdownMenuItem(value: 'dept-approver', child: Text("Department Approver")),
                      ],
                      onChanged: (val) {
                        if (val != null) {
                          setModalState(() {
                            selectedRole = val;
                          });
                        }
                      },
                      decoration: InputDecoration(
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text('Full Name *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: fullNameController,
                      decoration: InputDecoration(
                        hintText: 'Enter full name',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text('Designation *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: designationController,
                      decoration: InputDecoration(
                        hintText: 'e.g., LDC, Supervisor',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text('Mobile *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: mobileController,
                      keyboardType: TextInputType.phone,
                      maxLength: 10,
                      decoration: InputDecoration(
                        hintText: '10-digit mobile number',
                        counterText: '',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text('Login Email *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: emailController,
                      keyboardType: TextInputType.emailAddress,
                      decoration: InputDecoration(
                        hintText: 'Enter login email',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text('Password *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: passwordController,
                      obscureText: true,
                      decoration: InputDecoration(
                        hintText: 'Minimum 6 characters',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text('Confirm Password *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: confirmPasswordController,
                      obscureText: true,
                      decoration: InputDecoration(
                        hintText: 'Re-enter password',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: isSubmitting ? null : () => Navigator.of(ctx).pop(),
                  child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
                ),
                ElevatedButton(
                  onPressed: isSubmitting
                      ? null
                      : () async {
                          final name = fullNameController.text.trim();
                          final designation = designationController.text.trim();
                          final mobile = mobileController.text.trim();
                          final email = emailController.text.trim();
                          final password = passwordController.text.trim();
                          final confirmPassword = confirmPasswordController.text.trim();

                          if (name.isEmpty || designation.isEmpty || mobile.isEmpty || email.isEmpty || password.isEmpty || confirmPassword.isEmpty) {
                            setModalState(() {
                              modalError = 'All fields marked * are required.';
                            });
                            return;
                          }
                          if (mobile.length != 10 || int.tryParse(mobile) == null) {
                            setModalState(() {
                              modalError = 'Mobile number must be a valid 10-digit number.';
                            });
                            return;
                          }
                          if (!email.contains('@')) {
                            setModalState(() {
                              modalError = 'Please enter a valid email address.';
                            });
                            return;
                          }
                          if (password.length < 6) {
                            setModalState(() {
                              modalError = 'Password must be at least 6 characters.';
                            });
                            return;
                          }
                          if (password != confirmPassword) {
                            setModalState(() {
                              modalError = 'Password and Confirm Password do not match.';
                            });
                            return;
                          }

                          setModalState(() {
                            isSubmitting = true;
                            modalError = null;
                          });

                          final payload = {
                            'role': selectedRole,
                            'name': name,
                            'designation': designation,
                            'mobile': mobile,
                            'email': email,
                            'password': password
                          };

                          final res = await _apiService.registerUser(payload);
                          setModalState(() {
                            isSubmitting = false;
                          });

                          if (res['success'] == true) {
                            Navigator.of(ctx).pop();
                            _fetchUsers(); // reload users list
                            _fetchStats(); // update dashboard stats
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(res['message'] ?? 'User onboarded successfully'),
                                  backgroundColor: Colors.green,
                                ),
                              );
                            }
                          } else {
                            setModalState(() {
                              modalError = res['message'] ?? 'Failed to onboard user.';
                            });
                          }
                        },
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF0F172A)),
                  child: isSubmitting
                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Onboard', style: TextStyle(color: Colors.white)),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _showProfileDetailSheet(Map<String, dynamic> user) {
    bool isActive = user['is_active'] ?? true;
    bool isUpdating = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.all(24),
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.7,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user['name'] ?? 'N/A',
                          style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          user['role'] == 'dept-verifier' || user['role'] == 'verifier' ? 'Verifier' : 'Approver',
                          style: const TextStyle(
                            color: Color(0xFFE65100),
                            fontWeight: FontWeight.bold,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                ],
              ),
              const Divider(height: 24),
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Active/Inactive status toggle box
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Login Authorization',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  isActive ? 'Active (Can sign in)' : 'Inactive (Cannot sign in)',
                                  style: TextStyle(
                                    color: isActive ? Colors.green : Colors.red,
                                    fontSize: 13,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                            isUpdating
                                ? const CircularProgressIndicator()
                                : Switch(
                                    value: isActive,
                                    activeColor: Colors.green,
                                    onChanged: (val) async {
                                      setModalState(() {
                                        isUpdating = true;
                                      });
                                      final res = await _apiService.toggleUserStatus(user['id']);
                                      setModalState(() {
                                        isUpdating = false;
                                      });
                                      if (res['success'] == true) {
                                        setModalState(() {
                                          isActive = res['is_active'] == true;
                                        });
                                        _fetchUsers(); // Refresh background list
                                      } else {
                                        if (mounted) {
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(
                                              content: Text(res['message'] ?? 'Failed to update login status'),
                                              backgroundColor: Colors.red,
                                            ),
                                          );
                                        }
                                      }
                                    },
                                  ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),
                      const Text(
                        'Full Details',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                      ),
                      const SizedBox(height: 12),
                      _buildInfoField('Designation', user['designation']),
                      _buildInfoField('Mobile Number', user['mobile']),
                      _buildInfoField('Login Email', user['email']),
                      _buildInfoField('Department Code', user['department_code']?.toString()),
                      _buildInfoField('Registered On', user['created_at']?.toString().split('T').first),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInfoField(String label, String? value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(
              label,
              style: TextStyle(color: Colors.grey.shade600, fontWeight: FontWeight.bold, fontSize: 14),
            ),
          ),
          Expanded(
            child: Text(
              value ?? 'N/A',
              style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 14, color: Colors.black87),
            ),
          ),
        ],
      ),
    );
  }

  // ================= TABS =================

  Widget _buildDashboardTab() {
    if (_isLoadingStats) {
      return const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A)));
    }

    final int govtDeptsCount  = _stats['govt_depts_count'] ?? 0;
    final int ngosCount       = _stats['ngos_count'] ?? 0;
    final int employersCount  = _stats['employers_count'] ?? 0;
    final int contractorsCount = _stats['contractors_count'] ?? 0;

    return RefreshIndicator(
      onRefresh: () async {
        await _fetchStats();
      },
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Welcome banner
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Welcome, $_adminName',
                    style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _adminEmail,
                    style: TextStyle(color: Colors.grey.shade400, fontSize: 14),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.orange.shade800,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      _adminRole == 'owner-dept-admin' ? 'Owner Admin' : 'Dept Admin',
                      style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // SECTION 1: Beneficiary Registry Status
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Beneficiary Registry Status',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
                ElevatedButton.icon(
                  onPressed: _exportSelfRegisteredCSV,
                  icon: const Icon(Icons.download_rounded, size: 16),
                  label: const Text('CSV', style: TextStyle(fontSize: 12)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF0F172A),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                )
              ],
            ),
            const SizedBox(height: 16),
            GridView.count(
              crossAxisCount: 2,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              childAspectRatio: 1.15,
              children: [
                _buildStatCard(
                  'Pending Beneficiary',
                  (_workersPending.length + _workersVerified.length).toString(),
                  Icons.hourglass_empty_rounded,
                  Colors.amber,
                  onTap: () {
                    setState(() {
                      _currentIndex = 2;
                      _selectedSubTab = 0;
                    });
                  },
                ),
                _buildStatCard(
                  'Active Beneficiary',
                  _workersApproved.length.toString(),
                  Icons.check_circle_rounded,
                  Colors.green,
                  onTap: () {
                    setState(() {
                      _currentIndex = 2;
                      _selectedSubTab = 2;
                    });
                  },
                ),
                _buildStatCard(
                  'Inactive Beneficiary',
                  _workersRejected.length.toString(),
                  Icons.cancel_outlined,
                  Colors.red,
                  onTap: () {
                    setState(() {
                      _currentIndex = 2;
                      _selectedSubTab = 3;
                    });
                  },
                ),
              ],
            ),
            const SizedBox(height: 24),

            // SECTION 2: Schemes & Applications
            const Text(
              'Schemes & Applications',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 16),
            GridView.count(
              crossAxisCount: 2,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              childAspectRatio: 1.15,
              children: [
                _buildStatCard(
                  'Active Schemes',
                  _stats['active_schemes_global']?.toString() ?? '0',
                  Icons.toggle_on_rounded,
                  Colors.teal,
                  onTap: () => _showSchemesListDialog(true),
                ),
                _buildStatCard(
                  'Inactive Schemes',
                  _stats['inactive_schemes_global']?.toString() ?? '0',
                  Icons.toggle_off_rounded,
                  Colors.red,
                  onTap: () => _showSchemesListDialog(false),
                ),
                _buildStatCard(
                  'Total Applications',
                  _stats['total_applications_global']?.toString() ?? '0',
                  Icons.app_registration_rounded,
                  Colors.indigo,
                  onTap: () {
                    setState(() {
                      _currentIndex = 2;
                      _selectedSubTab = 2;
                    });
                  },
                ),
              ],
            ),
            const SizedBox(height: 24),

            // SECTION 3: Onboarded Institutions
            const Text(
              'Onboarded Institutions',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 16),
            GridView.count(
              crossAxisCount: 2,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              childAspectRatio: 1.15,
              children: [
                _buildStatCard(
                  'Govt Depts',
                  govtDeptsCount.toString(),
                  Icons.business_rounded,
                  Colors.blue,
                  onTap: () {
                    setState(() {
                      _currentIndex = 3;
                      _selectedRegistryTab = 0;
                    });
                  },
                ),
                _buildStatCard(
                  'NGOs',
                  ngosCount.toString(),
                  Icons.volunteer_activism_rounded,
                  Colors.purple,
                  onTap: () {
                    setState(() {
                      _currentIndex = 3;
                      _selectedRegistryTab = 1;
                    });
                  },
                ),
                _buildStatCard(
                  'Employers',
                  employersCount.toString(),
                  Icons.work_rounded,
                  Colors.orange,
                  onTap: () {
                    setState(() {
                      _currentIndex = 3;
                      _selectedRegistryTab = 2;
                    });
                  },
                ),
                _buildStatCard(
                  'Contractors',
                  contractorsCount.toString(),
                  Icons.handyman_rounded,
                  Colors.brown,
                  onTap: () {
                    setState(() {
                      _currentIndex = 3;
                      _selectedRegistryTab = 3;
                    });
                  },
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color, {VoidCallback? onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.1),
              blurRadius: 8,
              offset: const Offset(0, 4),
            )
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
                  child: Icon(icon, color: color, size: 22),
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

  Widget _buildManageUsersTab() {
    return Column(
      children: [
        // Search & Filter Box
        Container(
          color: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _searchController,
                      decoration: InputDecoration(
                        hintText: 'Search by Name, Mobile, Email...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        filled: true,
                        fillColor: Colors.grey.shade100,
                        contentPadding: const EdgeInsets.symmetric(vertical: 8),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  ElevatedButton(
                    onPressed: _triggerSearch,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0F172A),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    ),
                    child: const Text('Search'),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Filter by Role:',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: _selectedFilterRole,
                        items: const [
                          DropdownMenuItem(value: 'all', child: Text('All Users')),
                          DropdownMenuItem(value: 'verifier', child: Text('Verifiers')),
                          DropdownMenuItem(value: 'approver', child: Text('Approvers')),
                        ],
                        onChanged: (val) {
                          if (val != null) {
                            setState(() {
                              _selectedFilterRole = val;
                              _triggerSearch();
                            });
                          }
                        },
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        // Grid View
        Expanded(
          child: _isLoadingUsers
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: () async {
                    await _fetchUsers();
                    _triggerSearch();
                  },
                  child: _filteredUsers.isEmpty
                      ? ListView(
                          children: [
                            SizedBox(height: MediaQuery.of(context).size.height * 0.2),
                            Center(
                              child: Column(
                                children: [
                                  Icon(Icons.people_outline_rounded, size: 64, color: Colors.grey.shade300),
                                  const SizedBox(height: 12),
                                  Text(
                                    'No registered users found.',
                                    style: TextStyle(color: Colors.grey.shade500, fontSize: 16, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        )
                      : GridView.builder(
                          padding: const EdgeInsets.all(16),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            crossAxisSpacing: 16,
                            mainAxisSpacing: 16,
                            childAspectRatio: 0.85,
                          ),
                          itemCount: _filteredUsers.length,
                          itemBuilder: (context, i) {
                            final user = _filteredUsers[i];
                            final isVerifier = user['role'] == 'dept-verifier' || user['role'] == 'verifier';
                            final isActive = user['is_active'] ?? true;

                            return Card(
                              elevation: 3,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              child: InkWell(
                                onTap: () => _showProfileDetailSheet(user),
                                borderRadius: BorderRadius.circular(16),
                                child: Padding(
                                  padding: const EdgeInsets.all(12.0),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          CircleAvatar(
                                            backgroundColor: (isVerifier ? Colors.indigo : Colors.teal).withOpacity(0.1),
                                            radius: 18,
                                            child: Icon(
                                              isVerifier ? Icons.gavel_rounded : Icons.verified_user_rounded,
                                              color: isVerifier ? Colors.indigo : Colors.teal,
                                              size: 18,
                                            ),
                                          ),
                                          Container(
                                            width: 10,
                                            height: 10,
                                            decoration: BoxDecoration(
                                              shape: BoxShape.circle,
                                              color: isActive ? Colors.green : Colors.red,
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 6),
                                      Text(
                                        user['name'] ?? 'N/A',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                      ),
                                      Text(
                                        user['designation'] ?? 'Designation: N/A',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: TextStyle(color: Colors.grey.shade600, fontSize: 11, fontWeight: FontWeight.w500),
                                      ),
                                      const Divider(height: 8),
                                      Text(
                                        'Mobile: ${user['mobile'] ?? 'N/A'}',
                                        style: TextStyle(color: Colors.grey.shade700, fontSize: 10),
                                      ),
                                      Text(
                                        'Email: ${user['email'] ?? 'N/A'}',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: TextStyle(color: Colors.grey.shade700, fontSize: 10),
                                      ),
                                      Align(
                                        alignment: Alignment.bottomRight,
                                        child: Text(
                                          isVerifier ? 'Verifier' : 'Approver',
                                          style: TextStyle(
                                            color: isVerifier ? Colors.indigo : Colors.teal,
                                            fontSize: 10,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                ),
        ),
      ],
    );
  }

  Widget _buildMasterRegistryTab() {
    // kept for Govt Depts tab only
    return Column(
      children: [
        Container(
          color: Colors.white,
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _buildRegistrySubTabButton(0, 'Govt Depts'),
                _buildRegistrySubTabButton(1, 'NGOs'),
                _buildRegistrySubTabButton(2, 'Employers'),
                _buildRegistrySubTabButton(3, 'Contractors'),
              ],
            ),
          ),
        ),
        Container(
          color: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: TextField(
            controller: _searchController,
            onChanged: (v) => setState(() {}),
            decoration: InputDecoration(
              hintText: 'Search registry...',
              prefixIcon: const Icon(Icons.search),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
              filled: true,
              fillColor: Colors.grey.shade100,
              contentPadding: EdgeInsets.zero,
            ),
          ),
        ),
        Expanded(
          child: _isLoadingUsers
              ? const Center(child: CircularProgressIndicator())
              : _filteredRegistryList().isEmpty
                  ? const Center(child: Text('No records found.'))
                  : ListView.builder(
                      padding: const EdgeInsets.all(12),
                      itemCount: _filteredRegistryList().length,
                      itemBuilder: (ctx, idx) {
                        final u = _filteredRegistryList()[idx];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 0,
                          color: Colors.white,
                          child: ListTile(
                            title: Text(u['name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold)),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(u['email'] ?? 'N/A', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                if (u['mobile'] != null)
                                  Text(u['mobile'].toString(), style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
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

  // ─── ONBOARD HUB TAB ────────────────────────────────────────────────────────

  Widget _buildOnboardHubTab() {
    final tabs = [
      {'label': 'NGO', 'icon': Icons.volunteer_activism_rounded, 'color': Colors.purple, 'role': 'ngo'},
      {'label': 'Employer', 'icon': Icons.work_rounded, 'color': Colors.orange, 'role': 'employer'},
      {'label': 'Contractor', 'icon': Icons.handyman_rounded, 'color': Colors.brown, 'role': 'contractor'},
    ];
    final lists = [_ngos, _employers, _contractors];
    final currentList = lists[_selectedOnboardTab];
    final tabInfo = tabs[_selectedOnboardTab];

    return Column(
      children: [
        // Sub-tab selector
        Container(
          color: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: List.generate(tabs.length, (i) {
              final selected = _selectedOnboardTab == i;
              return GestureDetector(
                onTap: () => setState(() => _selectedOnboardTab = i),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 9),
                  decoration: BoxDecoration(
                    color: selected ? const Color(0xFF0F172A) : Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    children: [
                      Icon(tabs[i]['icon'] as IconData,
                        size: 15,
                        color: selected ? Colors.white : Colors.grey.shade600),
                      const SizedBox(width: 6),
                      Text(
                        tabs[i]['label'] as String,
                        style: TextStyle(
                          color: selected ? Colors.white : Colors.black87,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }),
          ),
        ),
        const Divider(height: 1),
        // List
        Expanded(
          child: _isLoadingUsers
              ? const Center(child: CircularProgressIndicator(color: Color(0xFF0F172A)))
              : currentList.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(tabInfo['icon'] as IconData, size: 56, color: Colors.grey.shade300),
                          const SizedBox(height: 12),
                          Text(
                            'No ${tabInfo['label']}s onboarded yet',
                            style: TextStyle(color: Colors.grey.shade500, fontSize: 15),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Tap + to register a new ${tabInfo['label']}',
                            style: TextStyle(color: Colors.grey.shade400, fontSize: 12),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: currentList.length,
                      itemBuilder: (ctx, idx) {
                        final u = currentList[idx];
                        final isActive = u['is_active'] == true;
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            boxShadow: [
                              BoxShadow(color: Colors.grey.withOpacity(0.08), blurRadius: 8, offset: const Offset(0, 2)),
                            ],
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Row(
                              children: [
                                Container(
                                  width: 46,
                                  height: 46,
                                  decoration: BoxDecoration(
                                    color: (tabInfo['color'] as Color).withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Icon(tabInfo['icon'] as IconData,
                                    color: tabInfo['color'] as Color, size: 22),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Expanded(
                                            child: Text(
                                              u['name'] ?? 'N/A',
                                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                            decoration: BoxDecoration(
                                              color: isActive ? Colors.green.withOpacity(0.1) : Colors.red.withOpacity(0.1),
                                              borderRadius: BorderRadius.circular(8),
                                            ),
                                            child: Text(
                                              isActive ? 'Active' : 'Inactive',
                                              style: TextStyle(
                                                color: isActive ? Colors.green : Colors.red,
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 3),
                                      Text(
                                        u['email'] ?? 'N/A',
                                        style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      if (u['mobile'] != null)
                                        Text(
                                          u['mobile'].toString(),
                                          style: TextStyle(color: Colors.grey.shade500, fontSize: 11),
                                        ),
                                      if (u['designation'] != null && u['designation'].toString().isNotEmpty)
                                        Text(
                                          'Contact: ${u['designation']}',
                                          style: TextStyle(color: Colors.grey.shade500, fontSize: 11),
                                        ),
                                    ],
                                  ),
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

  void _showOnboardHubRegisterDialog() {
    final types = ['ngo', 'employer', 'contractor'];
    final labels = ['NGO', 'Employer', 'Contractor'];
    final currentType = types[_selectedOnboardTab];
    final currentLabel = labels[_selectedOnboardTab];

    final nameCtrl = TextEditingController();
    final regNoCtrl = TextEditingController();
    final mobileCtrl = TextEditingController();
    final contactCtrl = TextEditingController();
    final addressCtrl = TextEditingController();
    final emailCtrl = TextEditingController();
    final passCtrl = TextEditingController(text: '123456');
    final formKey = GlobalKey<FormState>();
    bool isLoading = false;

    final addressLabel = currentType == 'employer' ? 'Factory Address' : 'Office Address';
    final regLabel = currentType == 'contractor' ? 'License No' : 'Reg No';

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
                        'Register $currentLabel',
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
                      labelText: '$currentLabel Name',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),

                  // Reg / License No
                  TextFormField(
                    controller: regNoCtrl,
                    decoration: InputDecoration(
                      labelText: regLabel,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),

                  // Mobile
                  TextFormField(
                    controller: mobileCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: InputDecoration(
                      labelText: 'Mobile No',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),

                  // Contact Person
                  TextFormField(
                    controller: contactCtrl,
                    decoration: InputDecoration(
                      labelText: 'Contact Person',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),

                  // Address
                  TextFormField(
                    controller: addressCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      labelText: addressLabel,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 20),

                  // Login Credentials section
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.blue.shade100),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.lock_rounded, size: 16, color: Colors.blue.shade700),
                            const SizedBox(width: 6),
                            Text(
                              'Login Credentials',
                              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue.shade700, fontSize: 14),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: emailCtrl,
                          keyboardType: TextInputType.emailAddress,
                          decoration: InputDecoration(
                            labelText: '$currentLabel Login Email',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            filled: true,
                            fillColor: Colors.white,
                          ),
                          validator: (v) {
                            if (v == null || v.trim().isEmpty) return 'Required';
                            if (!v.contains('@')) return 'Enter valid email';
                            return null;
                          },
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: passCtrl,
                          decoration: InputDecoration(
                            labelText: 'Password',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            filled: true,
                            fillColor: Colors.white,
                            helperText: 'Default: 123456',
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: isLoading ? null : () async {
                        if (!formKey.currentState!.validate()) return;
                        setModalState(() => isLoading = true);

                        final payload = {
                          'role': currentType,
                          'name': nameCtrl.text.trim(),
                          'mobile': mobileCtrl.text.trim(),
                          'email': emailCtrl.text.trim(),
                          'password': passCtrl.text.trim(),
                          'designation': contactCtrl.text.trim(),
                          'address': addressCtrl.text.trim(),
                          'reg_no': regNoCtrl.text.trim(),
                        };

                        final res = await _apiService.registerUser(payload);
                        setModalState(() => isLoading = false);

                        if (mounted) {
                          Navigator.pop(ctx);
                          if (res['success'] == true) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text('$currentLabel registered successfully!'),
                                backgroundColor: Colors.green,
                              ),
                            );
                            _fetchStats();
                            _fetchUsers();
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(res['message'] ?? 'Registration failed'),
                                backgroundColor: Colors.red,
                              ),
                            );
                          }
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: isLoading
                          ? const SizedBox(
                              width: 20, height: 20,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            )
                          : Text('Register $currentLabel'),
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

  Widget _buildRegistrySubTabButton(int idx, String label) {
    final bool isSelected = _selectedRegistryTab == idx;
    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedRegistryTab = idx;
        });
      },
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 6, vertical: 12),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF0F172A) : Colors.grey.shade100,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : Colors.black87,
            fontWeight: FontWeight.bold,
            fontSize: 13,
          ),
        ),
      ),
    );
  }

  List<dynamic> _filteredRegistryList() {
    final query = _searchController.text.toLowerCase().trim();
    List<dynamic> baseList = [];
    if (_selectedRegistryTab == 0) {
      baseList = _allUsers.where((u) => u['role'] == 'govt-dept-admin' || u['role'] == 'dept-owner' || u['role'] == 'owner-dept-admin').toList();
    } else if (_selectedRegistryTab == 1) {
      baseList = _allUsers.where((u) => u['role'] == 'ngo').toList();
    } else if (_selectedRegistryTab == 2) {
      baseList = _allUsers.where((u) => u['role'] == 'employer').toList();
    } else if (_selectedRegistryTab == 3) {
      baseList = _allUsers.where((u) => u['role'] == 'contractor').toList();
    }

    if (query.isEmpty) return baseList;
    return baseList.where((u) {
      final name = u['name']?.toString().toLowerCase() ?? '';
      final email = u['email']?.toString().toLowerCase() ?? '';
      return name.contains(query) || email.contains(query);
    }).toList();
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
    Map<String, dynamic>? selectedMember;
    List<Map<String, dynamic>> parsedMembers = [];

    // Step 3: Beneficiary Details
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
                    children: [
                      Text(
                        'Register Beneficiary (Step $currentStep/3)',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const Spacer(),
                      IconButton(
                        onPressed: () => Navigator.pop(ctx),
                        icon: const Icon(Icons.close),
                      ),
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

                  // STEP 3: FILL ADDITIONAL DETAILS & SUBMIT
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
                                  const SnackBar(content: Text('Beneficiary worker registered successfully with family!'), backgroundColor: Colors.green),
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

  Widget _buildProfileTab() {
    if (_isLoadingProfile) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_department == null) {
      return RefreshIndicator(
        onRefresh: () async {
          await _fetchDepartmentDetails();
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 32,
                      backgroundColor: Colors.white,
                      child: Icon(Icons.person_rounded, size: 36, color: Colors.blueGrey.shade800),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _adminName,
                            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.blueGrey.shade900),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _adminEmail,
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Colors.blueGrey.shade700),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              const Text(
                'Department Office Information',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              ),
              const SizedBox(height: 8),
              const Divider(),
              const SizedBox(height: 12),
              const Card(
                elevation: 0,
                color: Color(0xFFFFFBEB),
                shape: RoundedRectangleBorder(
                  side: BorderSide(color: Color(0xFFFDE68A)),
                  borderRadius: BorderRadius.all(Radius.circular(12)),
                ),
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(Icons.warning_amber_rounded, color: Color(0xFFD97706)),
                      SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          'Detailed department office configurations could not be fetched from the server. Please contact the administrator.',
                          style: TextStyle(color: Color(0xFF92400E), fontSize: 13, fontWeight: FontWeight.w500),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),
              const Text(
                'Change Account Password',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              ),
              const SizedBox(height: 8),
              const Divider(),
              const SizedBox(height: 12),
              Card(
                elevation: 1,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      if (_passErrorMsg != null) ...[
                        Text(_passErrorMsg!, style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 13)),
                        const SizedBox(height: 10),
                      ],
                      if (_passSuccessMsg != null) ...[
                        Text(_passSuccessMsg!, style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 13)),
                        const SizedBox(height: 10),
                      ],
                      TextField(
                        controller: _currentPassCtrl,
                        obscureText: true,
                        decoration: const InputDecoration(labelText: 'Current Password', border: OutlineInputBorder()),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _newPassCtrl,
                        obscureText: true,
                        decoration: const InputDecoration(labelText: 'New Password', border: OutlineInputBorder()),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _confirmNewPassCtrl,
                        obscureText: true,
                        decoration: const InputDecoration(labelText: 'Confirm New Password', border: OutlineInputBorder()),
                      ),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _isUpdatingPassword ? null : () async {
                          final curr = _currentPassCtrl.text;
                          final newP = _newPassCtrl.text;
                          final conf = _confirmNewPassCtrl.text;
                          if (curr.isEmpty || newP.isEmpty || conf.isEmpty) {
                            setState(() {
                              _passErrorMsg = 'Please fill all fields.';
                              _passSuccessMsg = null;
                            });
                            return;
                          }
                          if (newP != conf) {
                            setState(() {
                              _passErrorMsg = 'New passwords do not match.';
                              _passSuccessMsg = null;
                            });
                            return;
                          }
                          setState(() {
                            _isUpdatingPassword = true;
                            _passErrorMsg = null;
                            _passSuccessMsg = null;
                          });
                          final res = await _apiService.changePassword(curr, newP);
                          setState(() {
                            _isUpdatingPassword = false;
                          });
                          if (res['success'] == true) {
                            _currentPassCtrl.clear();
                            _newPassCtrl.clear();
                            _confirmNewPassCtrl.clear();
                            setState(() {
                              _passSuccessMsg = 'Password updated successfully!';
                            });
                          } else {
                            setState(() {
                              _passErrorMsg = res['message'] ?? 'Failed to update password.';
                            });
                          }
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF0F172A),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                        ),
                        child: _isUpdatingPassword
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Text('Update Password', style: TextStyle(color: Colors.white)),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () async {
        await _fetchDepartmentDetails();
      },
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Department Brand Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: const Color(0xFFFFE0B2),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: const Color(0xFFFFCC80)),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 32,
                    backgroundColor: Colors.white,
                    child: Icon(Icons.account_balance_rounded, size: 36, color: Colors.orange.shade800),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _department!['department_name'] ?? 'N/A',
                          style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.orange.shade900),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Code: ${_department!['department_code'] ?? 'N/A'}',
                          style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Colors.orange.shade800),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'Office Information',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),
            const Divider(),
            const SizedBox(height: 8),
            _buildProfileRow('Parent Dept', _department!['parent_department_name']),
             _buildProfileRow('Office Address', _department!['office_address']),
            _buildProfileRow('Contact Email', _department!['email']),
            const SizedBox(height: 24),
            const Text(
              'Change Account Password',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),
            const Divider(),
            const SizedBox(height: 12),
            Card(
              elevation: 1,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (_passErrorMsg != null) ...[
                      Text(_passErrorMsg!, style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 13)),
                      const SizedBox(height: 10),
                    ],
                    if (_passSuccessMsg != null) ...[
                      Text(_passSuccessMsg!, style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 13)),
                      const SizedBox(height: 10),
                    ],
                    TextField(
                      controller: _currentPassCtrl,
                      obscureText: true,
                      decoration: const InputDecoration(labelText: 'Current Password', border: OutlineInputBorder()),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _newPassCtrl,
                      obscureText: true,
                      decoration: const InputDecoration(labelText: 'New Password', border: OutlineInputBorder()),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _confirmNewPassCtrl,
                      obscureText: true,
                      decoration: const InputDecoration(labelText: 'Confirm New Password', border: OutlineInputBorder()),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: _isUpdatingPassword ? null : () async {
                        final curr = _currentPassCtrl.text;
                        final newP = _newPassCtrl.text;
                        final conf = _confirmNewPassCtrl.text;
                        if (curr.isEmpty || newP.isEmpty || conf.isEmpty) {
                          setState(() {
                            _passErrorMsg = 'Please fill all fields.';
                            _passSuccessMsg = null;
                          });
                          return;
                        }
                        if (newP != conf) {
                          setState(() {
                            _passErrorMsg = 'New passwords do not match.';
                            _passSuccessMsg = null;
                          });
                          return;
                        }
                        setState(() {
                          _isUpdatingPassword = true;
                          _passErrorMsg = null;
                          _passSuccessMsg = null;
                        });
                        final res = await _apiService.changePassword(curr, newP);
                        setState(() {
                          _isUpdatingPassword = false;
                        });
                        if (res['success'] == true) {
                          _currentPassCtrl.clear();
                          _newPassCtrl.clear();
                          _confirmNewPassCtrl.clear();
                          setState(() {
                            _passSuccessMsg = 'Password updated successfully!';
                          });
                        } else {
                          setState(() {
                            _passErrorMsg = res['message'] ?? 'Failed to update password.';
                          });
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: _isUpdatingPassword
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text('Update Password', style: TextStyle(color: Colors.white)),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBeneficiariesTab() {
    return DefaultTabController(
      key: ValueKey(_selectedSubTab),
      initialIndex: _selectedSubTab,
      length: 4,
      child: Column(
        children: [
          // Search Box
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    onChanged: (v) => _triggerSearch(),
                    decoration: InputDecoration(
                      hintText: 'Search by Name, Mobile, Code...',
                      prefixIcon: const Icon(Icons.search_rounded),
                      filled: true,
                      fillColor: Colors.grey.shade100,
                      contentPadding: const EdgeInsets.symmetric(vertical: 8),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide.none,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  icon: const Icon(Icons.refresh_rounded, color: Color(0xFF0F172A)),
                  onPressed: _fetchWorkers,
                )
              ],
            ),
          ),
          const TabBar(
            labelColor: Color(0xFF0F172A),
            unselectedLabelColor: Colors.grey,
            indicatorColor: Color(0xFF0F172A),
            isScrollable: true,
            tabs: [
              Tab(text: 'Pending Verify'),
              Tab(text: 'Pending Approve'),
              Tab(text: 'Approved / Done'),
              Tab(text: 'Inactive / Rejected'),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                _buildWorkerListView(_filteredWorkersPending, isPendingTab: true),
                _buildWorkerListView(_filteredWorkersVerified, isPendingTab: false),
                _buildWorkerListView(_filteredWorkersApproved, isPendingTab: false),
                _buildWorkerListView(_filteredWorkersRejected, isPendingTab: false),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildWorkerListView(List<dynamic> list, {required bool isPendingTab}) {
    if (_isLoadingWorkers) {
      return const Center(child: CircularProgressIndicator());
    }

    if (list.isEmpty) {
      return RefreshIndicator(
        onRefresh: _fetchWorkers,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Container(
            height: 300,
            alignment: Alignment.center,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.person_search_rounded, size: 64, color: Colors.grey.shade300),
                const SizedBox(height: 16),
                Text(
                  'No beneficiaries found',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.grey.shade500),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchWorkers,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount: list.length,
        itemBuilder: (context, index) {
          final worker = list[index];
          final String name = worker['full_name'] ?? 'Unknown Beneficiary';
          final String code = worker['worker_code'] ?? 'Incomplete Profile';
          final String mobile = worker['user_details']?['mobile'] ?? worker['mobile_alt'] ?? 'N/A';
          final double score = double.tryParse(worker['profile_score']?.toString() ?? '0') ?? 0;
          final String status = worker['status'] ?? '';
          final bool isVerified = worker['is_verified'] == true || worker['is_verified'] == 1;

          // Compute badge text & color
          String badgeText = 'Pending';
          Color badgeColor = Colors.amber;
          if (status == 'approved') {
            badgeText = 'Approved';
            badgeColor = Colors.green;
          } else if (isVerified) {
            badgeText = 'Verified';
            badgeColor = Colors.blue;
          }

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            elevation: 1,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            child: InkWell(
              borderRadius: BorderRadius.circular(14),
              onTap: () => _showWorkerDetailSheet(worker),
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 24,
                      backgroundColor: Colors.grey.shade200,
                      backgroundImage: (worker['photo_url'] != null && worker['photo_url'].toString().isNotEmpty)
                          ? NetworkImage(_apiService.resolveImageUrl(worker['photo_url']))
                          : null,
                      child: (worker['photo_url'] == null || worker['photo_url'].toString().isEmpty)
                          ? const Icon(Icons.person_rounded, size: 28, color: Colors.grey)
                          : null,
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            name,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.black87),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Code: $code',
                            style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Mobile: $mobile',
                            style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: badgeColor.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            badgeText,
                            style: TextStyle(color: badgeColor, fontWeight: FontWeight.bold, fontSize: 11),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            const Icon(Icons.analytics_outlined, size: 14, color: Colors.grey),
                            const SizedBox(width: 4),
                            Text(
                              '${score.toInt()}%',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.black87),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
  void _showWorkerActionDialog(Map<String, dynamic> worker, {required String action}) {
    final remarksCtrl = TextEditingController();
    String titleText = '';
    if (action == 'verify') titleText = 'Verify Beneficiary';
    if (action == 'approve') titleText = 'Approve Beneficiary';
    if (action == 'reject') titleText = 'Reject Beneficiary';

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(titleText, style: const TextStyle(fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Beneficiary: ${worker['full_name']}'),
            const SizedBox(height: 12),
            TextField(
              controller: remarksCtrl,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'Remarks / Notes',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            onPressed: () async {
              final remarks = remarksCtrl.text.trim();
              if (action == 'reject' && remarks.isEmpty) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Please enter rejection remarks.')),
                );
                return;
              }

              Navigator.pop(ctx); // Close dialog
              Navigator.pop(context); // Close details bottom sheet

              setState(() {
                _isLoadingWorkers = true;
              });

              final id = worker['id']?.toString() ?? worker['_id']?.toString() ?? '';
              Map<String, dynamic> res;
              if (action == 'verify') {
                res = await _apiService.verifyWorker(id, remarks.isNotEmpty ? remarks : 'Verified by Owner Dept');
              } else if (action == 'approve') {
                res = await _apiService.approveWorker(id, remarks.isNotEmpty ? remarks : 'Approved by Owner Dept');
              } else {
                res = await _apiService.rejectWorker(id, remarks);
              }

              if (res['success'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Beneficiary successfully ${action}ed.'),
                    backgroundColor: Colors.green,
                  ),
                );
                _fetchWorkers();
                _fetchStats();
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(res['message'] ?? 'Action failed.'),
                    backgroundColor: Colors.red,
                  ),
                );
                setState(() {
                  _isLoadingWorkers = false;
                });
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: action == 'reject' ? Colors.red : Colors.green,
            ),
            child: const Text('Submit', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _showWorkerDetailSheet(Map<String, dynamic> worker) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) {
          return Container(
            padding: EdgeInsets.only(
              left: 24,
              right: 24,
              top: 24,
              bottom: MediaQuery.of(context).viewInsets.bottom + 24,
            ),
            constraints: BoxConstraints(
              maxHeight: MediaQuery.of(context).size.height * 0.85,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        worker['full_name'] ?? 'Beneficiary Profile',
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded),
                      onPressed: () => Navigator.of(context).pop(),
                    ),
                  ],
                ),
                const Divider(height: 16),
                Expanded(
                  child: SingleChildScrollView(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const SizedBox(height: 8),
                        // Photo & name block
                        Center(
                          child: CircleAvatar(
                            radius: 40,
                            backgroundColor: const Color(0xFFF1F5F9),
                            backgroundImage: (worker['photo_url'] != null && worker['photo_url'].toString().isNotEmpty)
                                ? NetworkImage(_apiService.resolveImageUrl(worker['photo_url']))
                                : null,
                            child: (worker['photo_url'] == null || worker['photo_url'].toString().isEmpty)
                                ? const Icon(Icons.person_rounded, size: 40, color: Color(0xFF0F172A))
                                : null,
                          ),
                        ),
                        const SizedBox(height: 16),
                        
                        const Text('Personal Details', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A))),
                        const SizedBox(height: 8),
                        _buildDetailRow('Beneficiary Code', worker['worker_code']),
                        _buildDetailRow('Father\'s Name', worker['father_name']),
                        _buildDetailRow('Mother\'s Name', worker['mother_name']),
                        _buildDetailRow('DOB', worker['dob']),
                        _buildDetailRow('Gender', worker['gender']),
                        _buildDetailRow('Category', worker['category']),
                        _buildDetailRow('Religion', worker['religion']),
                        _buildDetailRow('Marital Status', worker['marital_status']),
                        _buildDetailRow('Education', worker['education']),
                        _buildDetailRow('Mobile (Alternate)', worker['mobile_alt']),
                        _buildDetailRow('Email', worker['email']),
                        _buildDetailRow('Aadhaar Address', worker['aadhaar_address']),
                        _buildDetailRow('Local Address', worker['local_address']),
                        _buildDetailRow('Identification Mark', worker['identification_mark']),
                        
                        const SizedBox(height: 20),
                        const Text('Job & Bank Details', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A))),
                        const SizedBox(height: 8),
                        _buildDetailRow('Job Profile', worker['job_profile']),
                        _buildDetailRow('Employer Name', worker['employer_name']),
                        _buildDetailRow('Employment Site', worker['employment_site']),
                        _buildDetailRow('Employment Status', worker['employment_status']),
                        _buildDetailRow('Bank Name & Branch', worker['bank_name_branch']),
                        _buildDetailRow('Account Number', worker['bank_account_number']),
                        _buildDetailRow('IFSC Code', worker['bank_ifsc']),

                        const SizedBox(height: 20),
                        const Text('Verification Details', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A))),
                        const SizedBox(height: 8),
                        _buildDetailRow('Verified State', (worker['is_verified'] == true || worker['is_verified'] == 1) ? 'Verified' : 'Pending Verification'),
                        _buildDetailRow('Verified At', worker['verified_at']),
                        _buildDetailRow('Verifier Remarks', worker['verifier_remarks']),
                        _buildDetailRow('Approval State', worker['status'] == 'approved' ? 'Approved' : 'Pending Approval'),
                        _buildDetailRow('Approved At', worker['approved_at']),
                        _buildDetailRow('Approver Remarks', worker['approver_remarks']),
                        const SizedBox(height: 12),
                        if (worker['status'] != 'approved' && worker['status'] != 'rejected') ...[
                          const SizedBox(height: 12),
                          const Divider(),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: () => _showWorkerActionDialog(worker, action: 'reject'),
                                  style: OutlinedButton.styleFrom(
                                    side: const BorderSide(color: Colors.red),
                                    foregroundColor: Colors.red,
                                    padding: const EdgeInsets.symmetric(vertical: 12),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                  child: const Text('Reject', style: TextStyle(fontWeight: FontWeight.bold)),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: ElevatedButton(
                                  onPressed: () {
                                    final bool isAlreadyVerified = worker['is_verified'] == true || worker['is_verified'] == 1;
                                    _showWorkerActionDialog(
                                      worker,
                                      action: isAlreadyVerified ? 'approve' : 'verify',
                                    );
                                  },
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.green,
                                    padding: const EdgeInsets.symmetric(vertical: 12),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                  child: Text(
                                    (worker['is_verified'] == true || worker['is_verified'] == 1) ? 'Approve' : 'Verify',
                                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildDetailRow(String label, dynamic value) {
    final String displayVal = (value == null || value.toString().trim().isEmpty) ? 'N/A' : value.toString();
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(
              label,
              style: TextStyle(color: Colors.grey.shade600, fontWeight: FontWeight.bold, fontSize: 13),
            ),
          ),
          Expanded(
            child: Text(
              displayVal,
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileRow(String label, String? value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: TextStyle(color: Colors.grey.shade600, fontWeight: FontWeight.bold, fontSize: 13),
            ),
          ),
          Expanded(
            child: Text(
              (value == null || value.toString().isEmpty) ? 'N/A' : value.toString(),
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Colors.black87),
            ),
          ),
        ],
      ),
    );
  }

  // ================= MAIN BUILD =================

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          _currentIndex == 0
              ? 'Dashboard'
              : _currentIndex == 1
                  ? 'Onboard Hub'
                  : _currentIndex == 2
                      ? 'Beneficiary Console'
                      : _currentIndex == 3
                          ? 'Manage Users'
                          : _currentIndex == 4
                              ? 'Manage Schemes'
                              : 'My Profile',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF0F172A),
        foregroundColor: Colors.white,
        actions: [
          if (_currentIndex == 0)
            IconButton(
              icon: const Icon(Icons.refresh_rounded),
              onPressed: () {
                _fetchStats();
                _workersRejected.clear(); // Refresh rejected list
                _fetchWorkers();
              },
            ),
          if (_currentIndex == 3)
            IconButton(
              icon: const Icon(Icons.person_add_alt_1_rounded),
              onPressed: _showOnboardUserDialog,
            ),
          if (_currentIndex == 4)
            IconButton(
              icon: const Icon(Icons.add_task_rounded),
              onPressed: _showAddSchemeDialog,
            ),
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            onPressed: _handleLogout,
          ),
        ],
      ),
      drawer: Drawer(
        child: Column(
          children: [
            UserAccountsDrawerHeader(
              decoration: const BoxDecoration(
                color: Color(0xFF0F172A),
              ),
              currentAccountPicture: const CircleAvatar(
                backgroundColor: Colors.white,
                child: Icon(Icons.business_center_rounded, size: 36, color: Color(0xFF0F172A)),
              ),
              accountName: Text(
                _adminName,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              accountEmail: Text(_adminEmail),
            ),
            ListTile(
              leading: const Icon(Icons.dashboard_rounded),
              title: const Text('Dashboard'),
              selected: _currentIndex == 0,
              onTap: () {
                Navigator.of(context).pop();
                setState(() {
                  _currentIndex = 0;
                });
              },
            ),
            ListTile(
              leading: const Icon(Icons.hub_rounded),
              title: const Text('Onboard Hub'),
              selected: _currentIndex == 1,
              onTap: () {
                Navigator.of(context).pop();
                setState(() {
                  _currentIndex = 1;
                });
              },
            ),
            ListTile(
              leading: const Icon(Icons.person_search_rounded),
              title: const Text('Beneficiary Console'),
              selected: _currentIndex == 2,
              onTap: () {
                Navigator.of(context).pop();
                setState(() {
                  _currentIndex = 2;
                  _selectedSubTab = 0;
                });
              },
            ),
            ListTile(
              leading: const Icon(Icons.people_alt_rounded),
              title: const Text('Manage Users'),
              selected: _currentIndex == 3,
              onTap: () {
                Navigator.of(context).pop();
                setState(() {
                  _currentIndex = 3;
                });
              },
            ),
            ListTile(
              leading: const Icon(Icons.assignment_rounded),
              title: const Text('Manage Schemes'),
              selected: _currentIndex == 4,
              onTap: () {
                Navigator.of(context).pop();
                setState(() {
                  _currentIndex = 4;
                });
              },
            ),
            ListTile(
              leading: const Icon(Icons.account_balance_rounded),
              title: const Text('My Profile'),
              selected: _currentIndex == 5,
              onTap: () {
                Navigator.of(context).pop();
                setState(() {
                  _currentIndex = 5;
                });
              },
            ),
            const Spacer(),
            const Divider(),
            ListTile(
              leading: const Icon(Icons.logout_rounded, color: Colors.red),
              title: const Text('Logout', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
              onTap: () {
                Navigator.of(context).pop();
                _handleLogout();
              },
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: [
          _buildDashboardTab(),
          _buildOnboardHubTab(),
          _buildBeneficiariesTab(),
          _buildManageUsersTab(),
          _buildManageSchemesTab(),
          _buildProfileTab(),
        ],
      ),
      floatingActionButton: _currentIndex == 1
          ? FloatingActionButton(
              onPressed: _showOnboardHubRegisterDialog,
              backgroundColor: const Color(0xFF0F172A),
              foregroundColor: Colors.white,
              child: const Icon(Icons.add_business_rounded),
            )
          : _currentIndex == 2
              ? FloatingActionButton(
                  onPressed: _showOnboardBeneficiaryDialog,
                  backgroundColor: const Color(0xFF0F172A),
                  foregroundColor: Colors.white,
                  child: const Icon(Icons.person_add_rounded),
                )
              : _currentIndex == 3
                  ? FloatingActionButton(
                      onPressed: _showOnboardUserDialog,
                      backgroundColor: const Color(0xFF0F172A),
                      foregroundColor: Colors.white,
                      child: const Icon(Icons.person_add_rounded),
                    )
                  : _currentIndex == 4
                      ? FloatingActionButton(
                          onPressed: _showAddSchemeDialog,
                          backgroundColor: const Color(0xFF0F172A),
                          foregroundColor: Colors.white,
                          child: const Icon(Icons.add_task_rounded),
                        )
                      : null,
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        selectedItemColor: const Color(0xFF0F172A),
        unselectedItemColor: Colors.grey.shade500,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard_rounded),
            label: 'Dashboard',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.hub_rounded),
            label: 'Onboard Hub',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person_search_rounded),
            label: 'Beneficiary',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.people_alt_rounded),
            label: 'Users',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.assignment_rounded),
            label: 'Schemes',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.account_balance_rounded),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}
