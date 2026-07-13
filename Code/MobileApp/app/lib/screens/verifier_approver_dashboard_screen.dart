import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../models/models.dart';
import 'login_screen.dart';

class VerifierApproverDashboardScreen extends StatefulWidget {
  const VerifierApproverDashboardScreen({super.key});

  @override
  State<VerifierApproverDashboardScreen> createState() => _VerifierApproverDashboardScreenState();
}

class _VerifierApproverDashboardScreenState extends State<VerifierApproverDashboardScreen> {
  final ApiService _apiService = ApiService();
  int _currentIndex = 0;
  int _selectedSubTab = 0;

  // Loading states
  bool _isLoadingStats = true;
  bool _isLoadingWorkers = true;
  bool _isLoadingSchemes = true;
  bool _isLoadingGrievances = true;
  bool _isLoadingSchemeApps = true;

  // Stats and lists
  Map<String, dynamic> _stats = {};
  List<dynamic> _workersPending = [];
  List<dynamic> _workersDone = [];
  List<dynamic> _filteredWorkersPending = [];
  List<dynamic> _filteredWorkersDone = [];

  List<Scheme> _schemesPending = [];
  List<Scheme> _schemesDone = [];
  List<Scheme> _filteredSchemesPending = [];
  List<Scheme> _filteredSchemesDone = [];

  List<Grievance> _grievances = [];
  List<Grievance> _filteredGrievances = [];
  int _selectedGrievanceSubTab = 0; // 0: Open, 1: Pending, 2: Disposed

  List<SchemeApplication> _schemeAppsPending = [];
  List<SchemeApplication> _schemeAppsDone = [];
  List<SchemeApplication> _filteredSchemeAppsPending = [];
  List<SchemeApplication> _filteredSchemeAppsDone = [];

  // Logged-in user info
  String _userName = '';
  String _userEmail = '';
  String _userRole = '';
  String _userDesignation = '';
  String _userMobile = '';

  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _initData();
  }

  Future<void> _initData() async {
    await _loadUserInfo();
    _fetchStats();
    _fetchWorkers();
    _fetchSchemes();
    _fetchGrievances();
    _fetchSchemeApplications();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadUserInfo() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _userName = prefs.getString('user_name') ?? 'Officer';
      _userEmail = prefs.getString('user_email') ?? '';
      _userRole = prefs.getString('user_role') ?? '';
      _userDesignation = prefs.getString('user_designation') ?? 'Department Officer';
      _userMobile = prefs.getString('user_mobile') ?? '';
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
        _stats = {};
        _isLoadingStats = false;
      });
    }
  }

  Future<void> _fetchWorkers() async {
    if (_userRole == 'ngo-verifier') {
      setState(() {
        _isLoadingWorkers = false;
      });
      return;
    }

    setState(() {
      _isLoadingWorkers = true;
    });

    if (_userRole == 'dept-verifier' || _userRole == 'verifier') {
      final resPending = await _apiService.getWorkers('pending');
      final resVerified = await _apiService.getWorkers('verified');
      final resApproved = await _apiService.getWorkers('approved');

      if (mounted) {
        setState(() {
          _workersPending = resPending['success'] == true ? (resPending['data'] ?? []) : [];
          final List<dynamic> verified = resVerified['success'] == true ? (resVerified['data'] ?? []) : [];
          final List<dynamic> approved = resApproved['success'] == true ? (resApproved['data'] ?? []) : [];
          _workersDone = [...verified, ...approved];

          final seenIds = <String>{};
          _workersDone = _workersDone.where((w) {
            final id = w['id']?.toString() ?? w['_id']?.toString() ?? '';
            if (id.isEmpty) return true;
            if (seenIds.contains(id)) return false;
            seenIds.add(id);
            return true;
          }).toList();

          _isLoadingWorkers = false;
          _triggerSearch();
        });
      }
    } else if (_userRole == 'dept-approver' || _userRole == 'approver' || _userRole == 'ngo-approver') {
      final String sourceFilter = _userRole == 'ngo-approver' ? 'ngo' : '';
      final String pendingQueryStatus = _userRole == 'ngo-approver' ? 'pending' : 'verified';
      final resPending = await _apiService.getWorkers(pendingQueryStatus);
      final resApproved = await _apiService.getWorkers('approved');

      if (mounted) {
        setState(() {
          List<dynamic> pendingList = resPending['success'] == true ? (resPending['data'] ?? []) : [];
          List<dynamic> approvedList = resApproved['success'] == true ? (resApproved['data'] ?? []) : [];

          if (_userRole == 'ngo-approver') {
            pendingList = pendingList.where((w) => w['registration_source'] == 'ngo').toList();
            approvedList = approvedList.where((w) => w['registration_source'] == 'ngo').toList();
          }

          _workersPending = pendingList;
          _workersDone = approvedList;
          _isLoadingWorkers = false;
          _triggerSearch();
        });
      }
    }
  }

  Future<void> _fetchSchemes() async {
    setState(() {
      _isLoadingSchemes = true;
    });

    final String typePending = (_userRole == 'dept-verifier' || _userRole == 'verifier' || _userRole == 'ngo-verifier') ? 'pending' : 'verified';
    final String typeDone = (_userRole == 'dept-verifier' || _userRole == 'verifier' || _userRole == 'ngo-verifier') ? 'verified' : 'approved';

    final resPending = await _apiService.getSchemes(type: typePending);
    final resDone = await _apiService.getSchemes(type: typeDone);

    if (mounted) {
      setState(() {
        _schemesPending = resPending;
        _schemesDone = resDone;
        _isLoadingSchemes = false;
        _triggerSearch();
      });
    }
  }

  Future<void> _fetchGrievances() async {
    final bool showGrievances = (_userRole == 'dept-verifier' || _userRole == 'dept-approver' || _userRole == 'verifier' || _userRole == 'approver');
    if (!showGrievances) return;

    setState(() {
      _isLoadingGrievances = true;
    });

    String status = 'open';
    if (_selectedGrievanceSubTab == 1) {
      status = 'pending';
    } else if (_selectedGrievanceSubTab == 2) {
      status = 'disposed';
    }

    final res = await _apiService.getGrievances(status: status);
    if (mounted) {
      setState(() {
        _grievances = res;
        _filteredGrievances = List.from(_grievances);
        _isLoadingGrievances = false;
        _triggerSearch();
      });
    }
  }

  Future<void> _fetchSchemeApplications() async {
    setState(() {
      _isLoadingSchemeApps = true;
    });

    final String statusPending = (_userRole == 'ngo-verifier' || _userRole == 'dept-verifier' || _userRole == 'verifier') ? 'pending' : 'verified';
    final String statusDone = (_userRole == 'ngo-verifier' || _userRole == 'dept-verifier' || _userRole == 'verifier') ? 'verified' : 'approved';

    final resPending = await _apiService.getSchemeApplications(status: statusPending);
    final resDone = await _apiService.getSchemeApplications(status: statusDone);

    if (mounted) {
      setState(() {
        _schemeAppsPending = resPending;
        _schemeAppsDone = resDone;
        _filteredSchemeAppsPending = List.from(_schemeAppsPending);
        _filteredSchemeAppsDone = List.from(_schemeAppsDone);
        _isLoadingSchemeApps = false;
        _triggerSearch();
      });
    }
  }

  void _triggerSearch() {
    final query = _searchController.text.toLowerCase().trim();
    setState(() {
      _filteredWorkersPending = _workersPending.where((w) {
        final name = w['full_name']?.toString().toLowerCase() ?? '';
        final mobile = w['user_details']?['mobile']?.toString() ?? w['mobile_alt']?.toString() ?? '';
        final code = w['worker_code']?.toString().toLowerCase() ?? '';
        return query.isEmpty || name.contains(query) || mobile.contains(query) || code.contains(query);
      }).toList();

      _filteredWorkersDone = _workersDone.where((w) {
        final name = w['full_name']?.toString().toLowerCase() ?? '';
        final mobile = w['user_details']?['mobile']?.toString() ?? w['mobile_alt']?.toString() ?? '';
        final code = w['worker_code']?.toString().toLowerCase() ?? '';
        return query.isEmpty || name.contains(query) || mobile.contains(query) || code.contains(query);
      }).toList();

      _filteredSchemesPending = _schemesPending.where((s) {
        return query.isEmpty || s.name.toLowerCase().contains(query) || s.description.toLowerCase().contains(query);
      }).toList();

      _filteredSchemesDone = _schemesDone.where((s) {
        return query.isEmpty || s.name.toLowerCase().contains(query) || s.description.toLowerCase().contains(query);
      }).toList();

      _filteredGrievances = _grievances.where((g) {
        final category = g.category.toLowerCase();
        final subject = g.subject.toLowerCase();
        final desc = g.description.toLowerCase();
        return query.isEmpty || category.contains(query) || subject.contains(query) || desc.contains(query);
      }).toList();

      _filteredSchemeAppsPending = _schemeAppsPending.where((sa) {
        final sName = sa.scheme?.name.toLowerCase() ?? '';
        final wName = sa.workerDetails?['full_name']?.toString().toLowerCase() ?? '';
        return query.isEmpty || sName.contains(query) || wName.contains(query);
      }).toList();

      _filteredSchemeAppsDone = _schemeAppsDone.where((sa) {
        final sName = sa.scheme?.name.toLowerCase() ?? '';
        final wName = sa.workerDetails?['full_name']?.toString().toLowerCase() ?? '';
        return query.isEmpty || sName.contains(query) || wName.contains(query);
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

  // TABS RENDERERS
  Widget _buildDashboardTab() {
    if (_isLoadingStats) {
      return const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)));
    }

    final bool isNgo = _userRole == 'ngo-verifier' || _userRole == 'ngo-approver';
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
                  child: Icon(Icons.badge_rounded, color: Colors.white, size: 36),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _userName,
                        style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '$_userDesignation • ${_userRole.toUpperCase()}',
                        style: TextStyle(color: Colors.white.withOpacity(0.85), fontSize: 12),
                      ),
                    ],
                  ),
                )
              ],
            ),
          ),
          const SizedBox(height: 24),
          const Text('Assigned Workflow Stats', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          if (!isNgo) ...[
            _buildDashboardStatRow('Pending Verifications', _stats['pending_verifications'] ?? _stats['pending_approvals'] ?? 0, Colors.amber),
            const SizedBox(height: 12),
          ],
          _buildDashboardStatRow('Pending Schemes', _stats['pending_schemes'] ?? 0, Colors.blue),
          const SizedBox(height: 12),
          _buildDashboardStatRow('Approved/Verified Done', _stats['verified_by_me'] ?? _stats['approved_by_me'] ?? 0, Colors.green),
        ],
      ),
    );
  }

  Widget _buildDashboardStatRow(String label, int count, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade100),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontWeight: FontWeight.w500)),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(color: color.withOpacity(0.15), borderRadius: BorderRadius.circular(20)),
            child: Text(count.toString(), style: TextStyle(color: color, fontWeight: FontWeight.bold)),
          )
        ],
      ),
    );
  }

  Widget _buildBeneficiariesTab() {
    final bool isVerifier = _userRole == 'dept-verifier' || _userRole == 'verifier';
    return DefaultTabController(
      length: 2,
      child: Column(
        children: [
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: TextField(
              controller: _searchController,
              onChanged: (_) => _triggerSearch(),
              decoration: InputDecoration(
                hintText: 'Search by worker name, code...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                filled: true,
                fillColor: Colors.grey.shade100,
                contentPadding: EdgeInsets.zero,
              ),
            ),
          ),
          TabBar(
            labelColor: const Color(0xFFE65100),
            unselectedLabelColor: Colors.grey,
            indicatorColor: const Color(0xFFE65100),
            tabs: [
              Tab(text: isVerifier ? 'Pending Verify' : 'Pending Approve'),
              Tab(text: 'Processed'),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                _buildWorkerList(_filteredWorkersPending, isPending: true),
                _buildWorkerList(_filteredWorkersDone, isPending: false),
              ],
            ),
          )
        ],
      ),
    );
  }

  Widget _buildWorkerList(List<dynamic> list, {required bool isPending}) {
    if (_isLoadingWorkers) {
      return const Center(child: CircularProgressIndicator());
    }
    if (list.isEmpty) {
      return const Center(child: Text('No workers found.'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: list.length,
      itemBuilder: (ctx, index) {
        final w = list[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          elevation: 0,
          color: Colors.white,
          child: InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: () => _showWorkerDetailSheet(w),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(w['full_name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      Text(w['worker_code'] ?? 'N/A', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                    ],
                  ),
                  const Divider(),
                  Text('Father: ${w['father_name'] ?? 'N/A'}', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                  Text('Mobile: ${w['user_details']?['mobile'] ?? w['mobile_alt'] ?? 'N/A'}', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                  Text('Trade: ${w['job_profile'] ?? 'N/A'}', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                  if (isPending) ...[
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        ElevatedButton(
                          onPressed: () => _showWorkerActionDialog(w, isApprove: false),
                          style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                          child: const Text('Reject', style: TextStyle(color: Colors.white)),
                        ),
                        const SizedBox(width: 8),
                        ElevatedButton(
                          onPressed: () => _showWorkerActionDialog(w, isApprove: true),
                          style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                          child: Text(
                            (_userRole == 'dept-verifier' || _userRole == 'verifier') ? 'Verify' : 'Approve',
                            style: const TextStyle(color: Colors.white),
                          ),
                        ),
                      ],
                    )
                  ]
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  void _showWorkerDetailSheet(Map<String, dynamic> worker) {
    final String status = worker['status'] ?? '';
    final bool isVerified = worker['is_verified'] == true || worker['is_verified'] == 1;

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

                        if (status != 'approved' && status != 'rejected') ...[
                          const SizedBox(height: 12),
                          const Divider(),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: () {
                                    Navigator.pop(context); // Close sheet
                                    _showWorkerActionDialog(worker, isApprove: false);
                                  },
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
                                    Navigator.pop(context); // Close sheet
                                    _showWorkerActionDialog(worker, isApprove: true);
                                  },
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.green,
                                    padding: const EdgeInsets.symmetric(vertical: 12),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                  child: Text(
                                    (_userRole == 'dept-verifier' || _userRole == 'verifier') ? 'Verify' : 'Approve',
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

  void _showWorkerActionDialog(Map<String, dynamic> worker, {required bool isApprove}) {
    final remarksCtrl = TextEditingController();
    final bool isVerifier = _userRole == 'dept-verifier' || _userRole == 'verifier';

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isApprove ? (isVerifier ? 'Verify Worker' : 'Approve Worker') : 'Reject Worker'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Worker: ${worker['full_name']}'),
            const SizedBox(height: 12),
            TextField(
              controller: remarksCtrl,
              maxLines: 3,
              decoration: const InputDecoration(labelText: 'Remarks', border: OutlineInputBorder()),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () async {
              final remarks = remarksCtrl.text.trim();
              if (remarks.isEmpty && !isApprove) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enter rejection remarks.')));
                return;
              }

              Navigator.pop(ctx);
              setState(() {
                _isLoadingWorkers = true;
              });

              final res = isApprove
                  ? (isVerifier
                      ? await _apiService.verifyWorker(worker['id']?.toString() ?? '', remarks)
                      : await _apiService.approveWorker(worker['id']?.toString() ?? '', remarks))
                  : await _apiService.rejectWorker(worker['id']?.toString() ?? '', remarks);

              if (res['success'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Operation successful.')));
                _fetchWorkers();
                _fetchStats();
              } else {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'] ?? 'Action failed.')));
                setState(() {
                  _isLoadingWorkers = false;
                });
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: isApprove ? Colors.green : Colors.red),
            child: const Text('Submit', style: TextStyle(color: Colors.white)),
          )
        ],
      ),
    );
  }

  Widget _buildSchemesTab() {
    return DefaultTabController(
      length: 2,
      child: Column(
        children: [
          TabBar(
            labelColor: const Color(0xFFE65100),
            unselectedLabelColor: Colors.grey,
            indicatorColor: const Color(0xFFE65100),
            tabs: const [
              Tab(text: 'Applications'),
              Tab(text: 'Board Schemes'),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                _buildSchemeApplicationsSubTab(),
                _buildBoardSchemesSubTab(),
              ],
            ),
          )
        ],
      ),
    );
  }

  Widget _buildSchemeApplicationsSubTab() {
    final bool isVerifier = _userRole == 'dept-verifier' || _userRole == 'verifier' || _userRole == 'ngo-verifier';
    return DefaultTabController(
      length: 2,
      child: Column(
        children: [
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: TextField(
              controller: _searchController,
              onChanged: (_) => _triggerSearch(),
              decoration: InputDecoration(
                hintText: 'Search applications...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                filled: true,
                fillColor: Colors.grey.shade100,
                contentPadding: EdgeInsets.zero,
              ),
            ),
          ),
          TabBar(
            labelColor: const Color(0xFFE65100),
            unselectedLabelColor: Colors.grey,
            indicatorColor: const Color(0xFFE65100),
            tabs: [
              Tab(text: isVerifier ? 'Pending Verify' : 'Pending Approve'),
              Tab(text: 'Processed'),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                _buildSchemeAppsList(_filteredSchemeAppsPending, isPending: true),
                _buildSchemeAppsList(_filteredSchemeAppsDone, isPending: false),
              ],
            ),
          )
        ],
      ),
    );
  }

  Widget _buildSchemeAppsList(List<SchemeApplication> list, {required bool isPending}) {
    if (_isLoadingSchemeApps) {
      return const Center(child: CircularProgressIndicator());
    }
    if (list.isEmpty) {
      return const Center(child: Text('No applications found.'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: list.length,
      itemBuilder: (ctx, index) {
        final sa = list[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          elevation: 0,
          color: Colors.white,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(sa.scheme?.name ?? 'Scheme App', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                const SizedBox(height: 4),
                Text('Applicant: ${sa.workerDetails?['full_name'] ?? 'N/A'}', style: TextStyle(color: Colors.grey.shade800, fontSize: 13)),
                Text('App Number: ${sa.applicationNumber}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                Text('Applied At: ${sa.appliedAt}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                if (isPending) ...[
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      ElevatedButton(
                        onPressed: () => _showSchemeAppActionDialog(sa, isApprove: false),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                        child: const Text('Reject', style: TextStyle(color: Colors.white)),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton(
                        onPressed: () => _showSchemeAppActionDialog(sa, isApprove: true),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                        child: Text(
                          (_userRole == 'ngo-verifier' || _userRole == 'dept-verifier' || _userRole == 'verifier') ? 'Verify' : 'Approve',
                          style: const TextStyle(color: Colors.white),
                        ),
                      ),
                    ],
                  )
                ]
              ],
            ),
          ),
        );
      },
    );
  }

  void _showSchemeAppActionDialog(SchemeApplication sa, {required bool isApprove}) {
    final remarksCtrl = TextEditingController();
    final bool isVerifier = _userRole == 'ngo-verifier' || _userRole == 'dept-verifier' || _userRole == 'verifier';

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isApprove ? (isVerifier ? 'Verify Scheme App' : 'Approve Scheme App') : 'Reject Scheme App'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Scheme: ${sa.scheme?.name}'),
            const SizedBox(height: 12),
            TextField(
              controller: remarksCtrl,
              maxLines: 3,
              decoration: const InputDecoration(labelText: 'Remarks', border: OutlineInputBorder()),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () async {
              final remarks = remarksCtrl.text.trim();
              if (remarks.isEmpty && !isApprove) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enter rejection remarks.')));
                return;
              }

              Navigator.pop(ctx);
              setState(() {
                _isLoadingSchemeApps = true;
              });

              final res = isApprove
                  ? (isVerifier
                      ? await _apiService.verifySchemeApplication(sa.id, remarks)
                      : await _apiService.approveSchemeApplication(sa.id, remarks))
                  : await _apiService.rejectSchemeApplication(sa.id, remarks);

              if (res['success'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Operation successful.')));
                _fetchSchemeApplications();
                _fetchStats();
              } else {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'] ?? 'Action failed.')));
                setState(() {
                  _isLoadingSchemeApps = false;
                });
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: isApprove ? Colors.green : Colors.red),
            child: const Text('Submit', style: TextStyle(color: Colors.white)),
          )
        ],
      ),
    );
  }

  Widget _buildBoardSchemesSubTab() {
    final bool isVerifier = _userRole == 'dept-verifier' || _userRole == 'verifier' || _userRole == 'ngo-verifier';
    return DefaultTabController(
      length: 2,
      child: Column(
        children: [
          TabBar(
            labelColor: const Color(0xFFE65100),
            unselectedLabelColor: Colors.grey,
            indicatorColor: const Color(0xFFE65100),
            tabs: [
              Tab(text: isVerifier ? 'Pending Verify' : 'Pending Approve'),
              Tab(text: 'Processed'),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                _buildSchemesList(_filteredSchemesPending, isPending: true),
                _buildSchemesList(_filteredSchemesDone, isPending: false),
              ],
            ),
          )
        ],
      ),
    );
  }

  Widget _buildSchemesList(List<Scheme> list, {required bool isPending}) {
    if (_isLoadingSchemes) {
      return const Center(child: CircularProgressIndicator());
    }
    if (list.isEmpty) {
      return const Center(child: Text('No schemes found.'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: list.length,
      itemBuilder: (ctx, index) {
        final s = list[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          elevation: 0,
          color: Colors.white,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(s.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                const SizedBox(height: 6),
                Text(s.description, style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                if (isPending) ...[
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      ElevatedButton(
                        onPressed: () => _showSchemeVerifyDialog(s, isApprove: false),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                        child: const Text('Reject', style: TextStyle(color: Colors.white)),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton(
                        onPressed: () => _showSchemeVerifyDialog(s, isApprove: true),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                        child: Text(
                          (_userRole == 'ngo-verifier' || _userRole == 'dept-verifier' || _userRole == 'verifier') ? 'Verify' : 'Approve',
                          style: const TextStyle(color: Colors.white),
                        ),
                      ),
                    ],
                  )
                ]
              ],
            ),
          ),
        );
      },
    );
  }

  void _showSchemeVerifyDialog(Scheme scheme, {required bool isApprove}) {
    final remarksCtrl = TextEditingController();
    final bool isVerifier = _userRole == 'ngo-verifier' || _userRole == 'dept-verifier' || _userRole == 'verifier';

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isApprove ? (isVerifier ? 'Verify Scheme' : 'Approve Scheme') : 'Reject Scheme'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Scheme: ${scheme.name}'),
            const SizedBox(height: 12),
            TextField(
              controller: remarksCtrl,
              maxLines: 3,
              decoration: const InputDecoration(labelText: 'Remarks', border: OutlineInputBorder()),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () async {
              final remarks = remarksCtrl.text.trim();
              if (remarks.isEmpty && !isApprove) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enter remarks.')));
                return;
              }

              Navigator.pop(ctx);
              setState(() {
                _isLoadingSchemes = true;
              });

              // Rejections on Scheme creation currently verified as verification update or rejected via API
              // For simplicity, we call verifyScheme/approveScheme which handles the action on backend
              final res = isApprove
                  ? (isVerifier
                      ? await _apiService.verifyScheme(scheme.id, remarks)
                      : await _apiService.approveScheme(scheme.id, remarks))
                  : await _apiService.verifyScheme(scheme.id, 'REJECTED: $remarks'); // Simple fallback to distinguish

              if (res['success'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Scheme status updated successfully.')));
                _fetchSchemes();
                _fetchStats();
              } else {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'] ?? 'Action failed.')));
                setState(() {
                  _isLoadingSchemes = false;
                });
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: isApprove ? Colors.green : Colors.red),
            child: const Text('Submit', style: TextStyle(color: Colors.white)),
          )
        ],
      ),
    );
  }

  Widget _buildGrievancesTab() {
    return Column(
      children: [
        Container(
          color: Colors.white,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _buildGrievanceSubTabButton(0, 'Open'),
              _buildGrievanceSubTabButton(1, 'Pending'),
              _buildGrievanceSubTabButton(2, 'Disposed'),
            ],
          ),
        ),
        Container(
          color: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: TextField(
            controller: _searchController,
            onChanged: (_) => _triggerSearch(),
            decoration: InputDecoration(
              hintText: 'Search grievances...',
              prefixIcon: const Icon(Icons.search),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
              filled: true,
              fillColor: Colors.grey.shade100,
              contentPadding: EdgeInsets.zero,
            ),
          ),
        ),
        Expanded(
          child: _isLoadingGrievances
              ? const Center(child: CircularProgressIndicator())
              : _filteredGrievances.isEmpty
                  ? const Center(child: Text('No grievances found.'))
                  : ListView.builder(
                      padding: const EdgeInsets.all(12),
                      itemCount: _filteredGrievances.length,
                      itemBuilder: (ctx, index) {
                        final g = _filteredGrievances[index];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 0,
                          color: Colors.white,
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(g.ticketNumber, style: const TextStyle(fontWeight: FontWeight.bold, fontFamily: 'monospace')),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: g.status == 'disposed'
                                            ? Colors.green.withOpacity(0.1)
                                            : g.status == 'pending'
                                                ? Colors.amber.withOpacity(0.1)
                                                : Colors.blue.withOpacity(0.1),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        g.status.toUpperCase(),
                                        style: TextStyle(
                                          color: g.status == 'disposed'
                                              ? Colors.green
                                              : g.status == 'pending'
                                                  ? Colors.amber
                                                  : Colors.blue,
                                          fontSize: 10,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    )
                                  ],
                                ),
                                const Divider(height: 20),
                                Text('Category: ${g.category}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                                Text('Subject: ${g.subject}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                                const SizedBox(height: 6),
                                Text(g.description, style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                                if (g.status != 'disposed') ...[
                                  const SizedBox(height: 12),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.end,
                                    children: [
                                      ElevatedButton.icon(
                                        onPressed: () => _showGrievanceActionDialog(g),
                                        icon: const Icon(Icons.edit_note_rounded, size: 18, color: Colors.white),
                                        label: const Text('Take Action', style: TextStyle(color: Colors.white)),
                                        style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE65100)),
                                      )
                                    ],
                                  )
                                ]
                              ],
                            ),
                          ),
                        );
                      },
                    ),
        )
      ],
    );
  }

  Widget _buildGrievanceSubTabButton(int idx, String label) {
    final bool isSelected = _selectedGrievanceSubTab == idx;
    return TextButton(
      onPressed: () {
        setState(() {
          _selectedGrievanceSubTab = idx;
        });
        _fetchGrievances();
      },
      child: Text(
        label,
        style: TextStyle(
          color: isSelected ? const Color(0xFFE65100) : Colors.grey,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
        ),
      ),
    );
  }

  void _showGrievanceActionDialog(Grievance g) {
    final noteCtrl = TextEditingController();
    String selectedStatus = 'pending';

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Update Grievance Status'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            DropdownButtonFormField<String>(
              value: selectedStatus,
              decoration: const InputDecoration(labelText: 'New Status', border: OutlineInputBorder()),
              items: const [
                DropdownMenuItem(value: 'pending', child: Text('Pending/Investigation')),
                DropdownMenuItem(value: 'disposed', child: Text('Disposed/Resolved')),
              ],
              onChanged: (val) {
                if (val != null) selectedStatus = val;
              },
            ),
            const SizedBox(height: 12),
            TextField(
              controller: noteCtrl,
              maxLines: 3,
              decoration: const InputDecoration(labelText: 'Resolution Note', border: OutlineInputBorder()),
            )
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () async {
              final note = noteCtrl.text.trim();
              Navigator.pop(ctx);
              setState(() {
                _isLoadingGrievances = true;
              });

              final res = await _apiService.updateGrievanceStatus(g.id.toString(), selectedStatus, note);
              if (res['success'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Grievance updated successfully.')));
                _fetchGrievances();
                _fetchStats();
              } else {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'] ?? 'Failed to update.')));
                setState(() {
                  _isLoadingGrievances = false;
                });
              }
            },
            child: const Text('Submit'),
          )
        ],
      ),
    );
  }

  Widget _buildProfileTab() {
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
                    child: Icon(Icons.person_rounded, color: Color(0xFFE65100), size: 48),
                  ),
                  const SizedBox(height: 16),
                  Text(_userName, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  Text(_userEmail, style: TextStyle(color: Colors.grey.shade600, fontSize: 14)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),
          const Text('Profile Details', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            elevation: 0,
            color: Colors.white,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  _buildProfileRow('Role', _userRole.toUpperCase()),
                  const Divider(),
                  _buildProfileRow('Designation', _userDesignation),
                  const Divider(),
                  _buildProfileRow('Mobile', _userMobile.isNotEmpty ? _userMobile : 'N/A'),
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
    // Generate dynamic tabs based on role
    final List<Map<String, dynamic>> tabs = [];
    tabs.add({'title': 'Dashboard', 'widget': _buildDashboardTab(), 'icon': Icons.dashboard_rounded});

    final bool showBeneficiary = (_userRole != 'ngo-verifier');
    if (showBeneficiary) {
      tabs.add({'title': 'Beneficiary', 'widget': _buildBeneficiariesTab(), 'icon': Icons.people_alt_rounded});
    }

    tabs.add({'title': 'Schemes', 'widget': _buildSchemesTab(), 'icon': Icons.assignment_rounded});

    final bool showGrievances = (_userRole == 'dept-verifier' || _userRole == 'dept-approver' || _userRole == 'verifier' || _userRole == 'approver');
    if (showGrievances) {
      tabs.add({'title': 'Grievances', 'widget': _buildGrievancesTab(), 'icon': Icons.feedback_rounded});
    }

    tabs.add({'title': 'Profile', 'widget': _buildProfileTab(), 'icon': Icons.person_rounded});

    final String title = tabs[_currentIndex]['title'];

    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFE65100),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () {
              _fetchStats();
              _fetchWorkers();
              _fetchSchemes();
              _fetchGrievances();
              _fetchSchemeApplications();
            },
          ),
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            onPressed: _handleLogout,
          )
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: tabs.map((t) => t['widget'] as Widget).toList(),
      ),
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
        items: tabs.map((t) {
          return BottomNavigationBarItem(
            icon: Icon(t['icon'] as IconData),
            label: t['title'] as String,
          );
        }).toList(),
      ),
    );
  }
}
