import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../services/api_service.dart';
import 'super_admin_login.dart';

class ApproverDashboardScreen extends StatefulWidget {
  const ApproverDashboardScreen({super.key});

  @override
  State<ApproverDashboardScreen> createState() => _ApproverDashboardScreenState();
}

class _ApproverDashboardScreenState extends State<ApproverDashboardScreen> {
  final _apiService = ApiService();
  String _activeTab = 'pending'; // 'pending' or 'history'
  String? _approverName = 'Approver';
  String? _approverEmail = '';
  String? _token;

  // --- Pending list state ---
  List<dynamic> _verifiedList = [];
  bool _isLoadingPending = false;
  int _currentPage = 1;
  int _totalPages = 1;
  int _totalRecords = 0;
  String _searchName = '';
  final _searchController = TextEditingController();

  // --- Selected Worker for Detailed Review ---
  dynamic _selectedWorker;
  final _remarksController = TextEditingController();
  bool _isActionInProgress = false;

  @override
  void initState() {
    super.initState();
    _loadApproverData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _remarksController.dispose();
    super.dispose();
  }

  Future<void> _loadApproverData() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _approverName = prefs.getString('admin_name') ?? 'Approver User';
      _approverEmail = prefs.getString('admin_email') ?? '';
      _token = prefs.getString('auth_token');
    });

    if (_token == null) {
      _redirectToLogin();
    } else {
      _fetchVerifiedWorkers();
    }
  }

  void _redirectToLogin() {
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const SuperAdminLoginScreen()),
    );
  }

  Future<void> _handleLogout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('admin_name');
    await prefs.remove('admin_email');
    await prefs.remove('admin_role');
    _redirectToLogin();
  }

  Future<void> _fetchVerifiedWorkers({int page = 1}) async {
    if (_token == null) return;
    setState(() => _isLoadingPending = true);

    try {
      String url = '${_apiService.baseUrl}/approver/verified-workers?page=$page';
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
          _verifiedList = paginatedData['data'] ?? [];
          _currentPage = paginatedData['current_page'] ?? 1;
          _totalPages = paginatedData['last_page'] ?? 1;
          _totalRecords = paginatedData['total'] ?? 0;
        });
      }
    } catch (_) {} finally {
      setState(() => _isLoadingPending = false);
    }
  }

  Future<void> _processApproval(bool approve) async {
    if (_selectedWorker == null || _token == null) return;

    final workerId = _selectedWorker['id'] ?? _selectedWorker['_id'] ?? '';
    final remarks = _remarksController.text.trim();

    if (!approve && remarks.length < 3) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please enter remarks explaining the rejection.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _isActionInProgress = true);

    try {
      final action = approve ? 'approve' : 'reject';
      final response = await http.post(
        Uri.parse('${_apiService.baseUrl}/approver/worker/$workerId/$action'),
        headers: {
          'Authorization': 'Bearer $_token',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({'remarks': remarks.isNotEmpty ? remarks : (approve ? 'Approved and registered.' : '')}),
      );

      if (!mounted) return;

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        final successMsg = approve 
            ? 'Approved successfully. Registration Issued: ${data['registration_no']}'
            : 'Sent back / Rejected successfully.';

        showDialog(
          context: context,
          builder: (ctx) => AlertDialog(
            title: Row(
              children: [
                Icon(approve ? Icons.check_circle_rounded : Icons.info_rounded, color: approve ? Colors.green : Colors.orange),
                const SizedBox(width: 8),
                Text(approve ? 'Registration Issued' : 'Profile Rejected'),
              ],
            ),
            content: Text(successMsg),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.of(ctx).pop();
                  setState(() {
                    _selectedWorker = null;
                    _remarksController.clear();
                  });
                  _fetchVerifiedWorkers(page: _currentPage);
                },
                child: const Text('OK'),
              )
            ],
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(data['message'] ?? 'Action failed.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Connection error.'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() => _isActionInProgress = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Row(
        children: [
          // Sidebar
          _buildSidebar(),

          // Main Section
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildHeader(),
                Expanded(
                  child: Container(
                    color: const Color(0xFFF3F4F6),
                    padding: const EdgeInsets.all(24.0),
                    child: _selectedWorker == null 
                      ? _buildVerifiedWorkersTab() 
                      : _buildWorkerReviewPanel(),
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
    return Material(
      color: const Color(0xFF0F172A),
      child: SizedBox(
        width: 260,
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(24.0),
              alignment: Alignment.center,
              decoration: const BoxDecoration(
                border: Border(bottom: BorderSide(color: Color(0xFF1E293B), width: 1)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.gavel_rounded, color: Color(0xFF1565C0), size: 28),
                  const SizedBox(width: 12),
                  const Text(
                    'Approver Portal',
                    style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12.0),
              child: ListTile(
                onTap: () {
                  setState(() {
                    _selectedWorker = null;
                    _activeTab = 'pending';
                  });
                  _fetchVerifiedWorkers();
                },
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                tileColor: _activeTab == 'pending' ? const Color(0xFF1565C0) : Colors.transparent,
                leading: Icon(Icons.assignment_turned_in_rounded, color: _activeTab == 'pending' ? Colors.white : Colors.grey),
                title: Text(
                  'Pending Approvals',
                  style: TextStyle(
                    color: _activeTab == 'pending' ? Colors.white : Colors.grey.shade300,
                    fontWeight: _activeTab == 'pending' ? FontWeight.bold : FontWeight.normal,
                    fontSize: 14,
                  ),
                ),
              ),
            ),
            const Spacer(),
            Container(
              padding: const EdgeInsets.all(16.0),
              color: const Color(0xFF020617),
              child: Row(
                children: [
                  const CircleAvatar(
                    backgroundColor: Color(0xFF1565C0),
                    child: Icon(Icons.person, color: Colors.white),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          _approverName ?? 'Approver',
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text(
                          _approverEmail ?? '',
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
            _selectedWorker == null ? 'Verified applications waiting for Approval' : 'Final Approval Review',
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
          ),
          if (_selectedWorker != null)
            TextButton.icon(
              onPressed: () {
                setState(() {
                  _selectedWorker = null;
                });
              },
              icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF1565C0)),
              label: const Text('Back to Verified Applications', style: TextStyle(color: Color(0xFF1565C0), fontWeight: FontWeight.bold)),
            ),
        ],
      ),
    );
  }

  Widget _buildVerifiedWorkersTab() {
    return Container(
      padding: const EdgeInsets.all(24.0),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF1565C0)),
                    hintText: 'Search verified workers by name...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  onChanged: (val) {
                    setState(() {
                      _searchName = val.trim();
                    });
                    _fetchVerifiedWorkers(page: 1);
                  },
                ),
              ),
              const SizedBox(width: 16),
              IconButton(
                icon: const Icon(Icons.refresh_rounded, color: Color(0xFF1565C0)),
                onPressed: () => _fetchVerifiedWorkers(page: _currentPage),
                tooltip: 'Refresh list',
              ),
            ],
          ),
          const SizedBox(height: 24),
          Expanded(
            child: _isLoadingPending
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF1565C0)))
                : _verifiedList.isEmpty
                    ? const Center(child: Text('No verified applications pending final approval.'))
                    : Column(
                        children: [
                          Expanded(
                            child: SingleChildScrollView(
                              scrollDirection: Axis.vertical,
                              child: Table(
                                columnWidths: const {
                                  0: FlexColumnWidth(2),
                                  1: FlexColumnWidth(1.2),
                                  2: FlexColumnWidth(1.2),
                                  3: FlexColumnWidth(3),
                                  4: FlexColumnWidth(1.5),
                                  5: FlexColumnWidth(1.2),
                                },
                                border: TableBorder(
                                  horizontalInside: BorderSide(color: Colors.grey.shade200, width: 1),
                                ),
                                children: [
                                  TableRow(
                                    decoration: BoxDecoration(color: Colors.grey.shade50),
                                    children: const [
                                      Padding(padding: EdgeInsets.all(12), child: Text('Name', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Trade / Job', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Verified Date', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Verifier Remarks', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Status', style: TextStyle(fontWeight: FontWeight.bold))),
                                      Padding(padding: EdgeInsets.all(12), child: Text('Action', style: TextStyle(fontWeight: FontWeight.bold))),
                                    ],
                                  ),
                                  ..._verifiedList.map((worker) {
                                    return TableRow(
                                      children: [
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(worker['name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.w500)),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(worker['job_profile'] ?? 'N/A'),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(worker['verified_at'] ?? 'N/A'),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Text(worker['verifier_remarks'] ?? 'N/A', maxLines: 1, overflow: TextOverflow.ellipsis),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Chip(
                                            label: const Text('VERIFIED', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.blue)),
                                            backgroundColor: Colors.blue.shade50,
                                            padding: EdgeInsets.zero,
                                          ),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.symmetric(vertical: 4.0),
                                          child: ElevatedButton(
                                            onPressed: () {
                                              setState(() {
                                                _selectedWorker = worker;
                                              });
                                            },
                                            style: ElevatedButton.styleFrom(
                                              backgroundColor: const Color(0xFF1565C0),
                                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                            ),
                                            child: const Text('Review', style: TextStyle(fontSize: 12, color: Colors.white)),
                                          ),
                                        ),
                                      ],
                                    );
                                  }),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('Total Verified Applications: $_totalRecords', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                              Row(
                                children: [
                                  IconButton(
                                    icon: const Icon(Icons.chevron_left_rounded),
                                    onPressed: _currentPage > 1 ? () => _fetchVerifiedWorkers(page: _currentPage - 1) : null,
                                  ),
                                  Text('Page $_currentPage of $_totalPages', style: const TextStyle(fontWeight: FontWeight.bold)),
                                  IconButton(
                                    icon: const Icon(Icons.chevron_right_rounded),
                                    onPressed: _currentPage < _totalPages ? () => _fetchVerifiedWorkers(page: _currentPage + 1) : null,
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

  Widget _buildWorkerReviewPanel() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Detailed View
          Expanded(
            flex: 3,
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Photo & Title Summary
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 40,
                        backgroundColor: Colors.grey.shade200,
                        backgroundImage: _selectedWorker['photo_url'] != null && _selectedWorker['photo_url'].isNotEmpty
                            ? NetworkImage(_apiService.resolveImageUrl(_selectedWorker['photo_url']))
                            : null,
                        child: _selectedWorker['photo_url'] == null || _selectedWorker['photo_url'].isEmpty
                            ? const Icon(Icons.person, size: 40, color: Colors.grey)
                            : null,
                      ),
                      const SizedBox(width: 24),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _selectedWorker['name'] ?? 'N/A',
                              style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Trade: ${_selectedWorker['job_profile'] ?? 'N/A'}',
                              style: const TextStyle(fontSize: 15, color: Colors.grey, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const Divider(height: 48),

                  _buildReviewSectionTitle('Verifier Verification Details'),
                  const SizedBox(height: 12),
                  _buildReviewRow('Verifier Remarks', _selectedWorker['verifier_remarks']),
                  _buildReviewRow('Verification Date', _selectedWorker['verified_at']),
                  const SizedBox(height: 24),

                  _buildReviewSectionTitle('Personal & Identity Details'),
                  const SizedBox(height: 12),
                  _buildReviewRow('Aadhaar Number', _selectedWorker['aadhaar_number']),
                  _buildReviewRow('Date of Birth', _selectedWorker['dob']),
                  _buildReviewRow('Gender', _selectedWorker['gender']),
                  _buildReviewRow('Marital Status', _selectedWorker['marital_status']),
                  _buildReviewRow('Father Name', _selectedWorker['father_name']),
                  _buildReviewRow('Permanent Address', _selectedWorker['address']),
                  const SizedBox(height: 24),

                  _buildReviewSectionTitle('Bank Account details'),
                  const SizedBox(height: 12),
                  _buildReviewRow('Account Number', _selectedWorker['bank_account_number']),
                  _buildReviewRow('Bank Name & Branch', _selectedWorker['bank_name_branch']),
                  _buildReviewRow('IFSC Code', _selectedWorker['bank_ifsc']),
                  const SizedBox(height: 32),

                  if (_selectedWorker['signature_url'] != null && _selectedWorker['signature_url'].isNotEmpty) ...[
                    _buildReviewSectionTitle('Signature Upload'),
                    const SizedBox(height: 12),
                    Image.network(
                      _apiService.resolveImageUrl(_selectedWorker['signature_url']),
                      height: 100,
                      fit: BoxFit.contain,
                      errorBuilder: (_, __, ___) => const Text('Failed to load signature image'),
                    ),
                    const SizedBox(height: 32),
                  ],
                ],
              ),
            ),
          ),
          const VerticalDivider(width: 1),

          // Action Panel (Approve/Reject)
          Expanded(
            flex: 2,
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Administrative Authorization',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Provide authorization remarks. Approving will issue a permanent registration number.',
                    style: TextStyle(fontSize: 13, color: Colors.grey),
                  ),
                  const Divider(height: 32),

                  const Text('Approver Remarks', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _remarksController,
                    maxLines: 5,
                    decoration: InputDecoration(
                      hintText: 'e.g. Documentation verified. Approved for registration benefits.',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      contentPadding: const EdgeInsets.all(12),
                    ),
                  ),
                  const SizedBox(height: 32),

                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _isActionInProgress ? null : () => _processApproval(false),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.red,
                            side: const BorderSide(color: Colors.red),
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          child: const Text('Reject Profile', style: TextStyle(fontWeight: FontWeight.bold)),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: _isActionInProgress ? null : () => _processApproval(true),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF1565C0),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          child: _isActionInProgress 
                              ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                              : const Text('Approve & Register', style: TextStyle(fontWeight: FontWeight.bold)),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReviewSectionTitle(String title) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF1565C0)),
        ),
        const SizedBox(height: 6),
        const Divider(),
      ],
    );
  }

  Widget _buildReviewRow(String label, String? value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 160,
            child: Text(
              label,
              style: TextStyle(fontWeight: FontWeight.w600, color: Colors.grey.shade600, fontSize: 13),
            ),
          ),
          Expanded(
            child: Text(
              (value == null || value.isEmpty) ? 'N/A' : value,
              style: const TextStyle(fontSize: 13, color: Color(0xFF374151), fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}
