import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import 'login_screen.dart';

class DeptOwnerDashboardScreen extends StatefulWidget {
  const DeptOwnerDashboardScreen({super.key});

  @override
  State<DeptOwnerDashboardScreen> createState() => _DeptOwnerDashboardScreenState();
}

class _DeptOwnerDashboardScreenState extends State<DeptOwnerDashboardScreen> {
  final ApiService _apiService = ApiService();
  int _currentIndex = 0;
  int _selectedSubTab = 0; // 0: Pending, 1: Verified, 2: Approved, 3: Rejected

  bool _isLoadingStats = true;
  bool _isLoadingWorkers = true;
  bool _isLoadingProfile = true;

  Map<String, dynamic> _stats = {
    'pending_workers': 0,
    'verified_workers': 0,
    'approved_workers': 0,
    'rejected_workers': 0,
  };

  List<dynamic> _workers = [];
  List<dynamic> _filteredWorkers = [];
  Map<String, dynamic>? _department;

  final TextEditingController _searchController = TextEditingController();

  // Logged-in info
  String _ownerName = '';
  String _ownerEmail = '';

  @override
  void initState() {
    super.initState();
    _initData();
  }

  Future<void> _initData() async {
    await _loadOwnerInfo();
    _fetchStats();
    _fetchWorkers();
    _fetchDepartmentDetails();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadOwnerInfo() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _ownerName = prefs.getString('user_name') ?? 'Dept Owner';
      _ownerEmail = prefs.getString('user_email') ?? '';
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
          'pending_workers': 0,
          'verified_workers': 0,
          'approved_workers': 0,
          'rejected_workers': 0,
        };
        _isLoadingStats = false;
      });
    }
  }

  Future<void> _fetchWorkers() async {
    setState(() {
      _isLoadingWorkers = true;
    });

    String type = 'pending';
    if (_selectedSubTab == 1) {
      type = 'verified';
    } else if (_selectedSubTab == 2) {
      type = 'approved';
    } else if (_selectedSubTab == 3) {
      type = 'rejected';
    }

    final res = await _apiService.getWorkers(type); // getWorkers will scope by dept-owner (source=self) internally now!
    if (res['success'] == true) {
      setState(() {
        _workers = res['data'] ?? [];
        _filteredWorkers = List.from(_workers);
        _isLoadingWorkers = false;
        _triggerSearch();
      });
    } else {
      setState(() {
        _workers = [];
        _filteredWorkers = [];
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
      _filteredWorkers = _workers.where((w) {
        final name = w['full_name']?.toString().toLowerCase() ?? '';
        final mobile = w['user_details']?['mobile']?.toString() ?? w['mobile_alt']?.toString() ?? '';
        final code = w['worker_code']?.toString().toLowerCase() ?? '';
        return query.isEmpty || name.contains(query) || mobile.contains(query) || code.contains(query);
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

  // Action flow (Verify / Approve / Reject)
  void _showActionDialog(Map<String, dynamic> worker, bool isApproveFlow) {
    final remarksCtrl = TextEditingController();
    final bool isVerifyFlow = !isApproveFlow;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text(
          isVerifyFlow ? 'Verify Registration' : 'Approve Registration',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Worker: ${worker['full_name'] ?? 'N/A'}',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: remarksCtrl,
              maxLines: 3,
              decoration: const InputDecoration(
                hintText: 'Enter remarks...',
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
              if (remarks.isEmpty) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Please enter remarks.')),
                );
                return;
              }

              Navigator.pop(ctx);
              setState(() {
                _isLoadingWorkers = true;
              });

              final res = isVerifyFlow
                  ? await _apiService.verifyWorker(worker['id']?.toString() ?? '', remarks)
                  : await _apiService.approveWorker(worker['id']?.toString() ?? '', remarks);

              if (res['success'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(isVerifyFlow ? 'Worker verified.' : 'Worker approved.')),
                );
                _fetchWorkers();
                _fetchStats();
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Failed to update.')),
                );
                setState(() {
                  _isLoadingWorkers = false;
                });
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            child: Text(isVerifyFlow ? 'Verify' : 'Approve', style: const TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _showRejectDialog(Map<String, dynamic> worker) {
    final remarksCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Reject Registration', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.red)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Worker: ${worker['full_name'] ?? 'N/A'}', style: const TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            TextField(
              controller: remarksCtrl,
              maxLines: 3,
              decoration: const InputDecoration(
                hintText: 'Enter rejection reason...',
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
              if (remarks.isEmpty) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Please enter rejection remarks.')),
                );
                return;
              }

              Navigator.pop(ctx);
              setState(() {
                _isLoadingWorkers = true;
              });

              final res = await _apiService.rejectWorker(worker['id']?.toString() ?? '', remarks);

              if (res['success'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Registration rejected and sent back to worker.')),
                );
                _fetchWorkers();
                _fetchStats();
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Failed to reject.')),
                );
                setState(() {
                  _isLoadingWorkers = false;
                });
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Reject', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  // TABS BUILDERS
  Widget _buildDashboardTab() {
    if (_isLoadingStats) {
      return const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)));
    }

    final int pendingCount = _stats['pending_workers'] ?? 0;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Welcome banner
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFE65100), Color(0xFFFF9800)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFFE65100).withOpacity(0.25),
                  blurRadius: 12,
                  offset: const Offset(0, 6),
                )
              ],
            ),
            child: Row(
              children: [
                const CircleAvatar(
                  radius: 30,
                  backgroundColor: Colors.white24,
                  child: Icon(Icons.shield_rounded, color: Colors.white, size: 36),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _department?['department_name'] ?? 'Labour Department',
                        style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Department Owner Portal (Self-Registrations)',
                        style: TextStyle(color: Colors.white.withOpacity(0.85), fontSize: 12),
                      ),
                    ],
                  ),
                )
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Pending review notification banner (matches WebApp)
          if (pendingCount > 0) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.amber.shade50,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.amber.shade200),
              ),
              child: Row(
                children: [
                  Icon(Icons.notifications_active_rounded, color: Colors.amber.shade800, size: 24),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '$pendingCount self-registration(s) need review',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.amber.shade900),
                        ),
                        const Text(
                          'Click to review and take action now',
                          style: TextStyle(fontSize: 11, color: Colors.amber),
                        ),
                      ],
                    ),
                  ),
                  ElevatedButton(
                    onPressed: () {
                      setState(() {
                        _currentIndex = 1;
                        _selectedSubTab = 0;
                      });
                      _fetchWorkers();
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.amber.shade600,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    ),
                    child: const Text('Review', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  )
                ],
              ),
            ),
            const SizedBox(height: 20),
          ],

          const Text(
            'Verification Statistics',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87),
          ),
          const SizedBox(height: 12),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 1.35,
            children: [
              _buildStatBox(
                'Pending Review',
                _stats['pending_workers'] ?? 0,
                Colors.amber,
                Icons.hourglass_empty_rounded,
                onTap: () {
                  setState(() {
                    _currentIndex = 1;
                    _selectedSubTab = 0;
                  });
                  _fetchWorkers();
                },
              ),
              _buildStatBox(
                'Verified',
                _stats['verified_workers'] ?? 0,
                Colors.blue,
                Icons.verified_user_rounded,
                onTap: () {
                  setState(() {
                    _currentIndex = 1;
                    _selectedSubTab = 1;
                  });
                  _fetchWorkers();
                },
              ),
              _buildStatBox(
                'Approved',
                _stats['approved_workers'] ?? 0,
                Colors.green,
                Icons.gavel_rounded,
                onTap: () {
                  setState(() {
                    _currentIndex = 1;
                    _selectedSubTab = 2;
                  });
                  _fetchWorkers();
                },
              ),
              _buildStatBox(
                'Rejected',
                _stats['rejected_workers'] ?? 0,
                Colors.red,
                Icons.cancel_outlined,
                onTap: () {
                  setState(() {
                    _currentIndex = 1;
                    _selectedSubTab = 3;
                  });
                  _fetchWorkers();
                },
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Info banner / Special Authority (matches WebApp)
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.yellow.shade50,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.yellow.shade200),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  backgroundColor: Colors.yellow.shade100,
                  radius: 20,
                  child: Icon(Icons.security_rounded, color: Colors.yellow.shade800, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Your Special Authority',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.yellow.shade900),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'As Department Owner, only you can approve and verify workers who have self-registered on the portal. Other members handle workers registered via official channels.',
                        style: TextStyle(fontSize: 11, color: Colors.yellow.shade800, height: 1.4),
                      ),
                    ],
                  ),
                )
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatBox(String title, int count, Color color, IconData icon, {VoidCallback? onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.grey.shade100),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.02),
              blurRadius: 8,
              offset: const Offset(0, 4),
            )
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                CircleAvatar(
                  backgroundColor: color.withOpacity(0.1),
                  radius: 18,
                  child: Icon(icon, color: color, size: 20),
                ),
                Text(
                  count.toString(),
                  style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: color),
                ),
              ],
            ),
            Text(
              title,
              style: TextStyle(color: Colors.grey.shade600, fontSize: 12, fontWeight: FontWeight.bold),
            )
          ],
        ),
      ),
    );
  }

  Widget _buildWorkersTab() {
    return Column(
      children: [
        // Sub-tabs
        Container(
          color: Colors.white,
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _buildSubTabButton(0, 'Pending Review', Colors.amber),
                _buildSubTabButton(1, 'Verified', Colors.blue),
                _buildSubTabButton(2, 'Approved', Colors.green),
                _buildSubTabButton(3, 'Rejected', Colors.red),
              ],
            ),
          ),
        ),
        // Search bar
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          color: Colors.white,
          child: TextField(
            controller: _searchController,
            onChanged: (_) => _triggerSearch(),
            decoration: InputDecoration(
              hintText: 'Search by name, mobile, code...',
              prefixIcon: const Icon(Icons.search),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
              filled: true,
              fillColor: Colors.grey.shade100,
              contentPadding: EdgeInsets.zero,
            ),
          ),
        ),
        Expanded(
          child: _isLoadingWorkers
              ? const Center(child: CircularProgressIndicator())
              : _filteredWorkers.isEmpty
                  ? const Center(child: Text('No registrations found.'))
                  : ListView.builder(
                      padding: const EdgeInsets.all(12),
                      itemCount: _filteredWorkers.length,
                      itemBuilder: (ctx, idx) {
                        final w = _filteredWorkers[idx];
                        final Color tagColor = _selectedSubTab == 0
                            ? Colors.amber
                            : _selectedSubTab == 1
                                ? Colors.blue
                                : _selectedSubTab == 2
                                    ? Colors.green
                                    : Colors.red;

                        return GestureDetector(
                          onTap: () => _showDetailsDialog(w),
                          child: Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
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
                                      Text(
                                        w['full_name'] ?? 'N/A',
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                      ),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: tagColor.withOpacity(0.1),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          _selectedSubTab == 0
                                              ? 'Pending'
                                              : _selectedSubTab == 1
                                                  ? 'Verified'
                                                  : _selectedSubTab == 2
                                                      ? 'Approved'
                                                      : 'Rejected',
                                          style: TextStyle(color: tagColor, fontSize: 11, fontWeight: FontWeight.bold),
                                        ),
                                      )
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  Text('Father: ${w['father_name'] ?? 'N/A'}', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                                  Text('Contact: ${w['user_details']?['mobile'] ?? w['mobile_alt'] ?? 'N/A'}', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                                  Text('Trade: ${w['job_profile'] ?? 'N/A'}', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                                  if (w['ration_card_number'] != null)
                                    Text('Ration Card: ${w['ration_card_number']}', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                                  const Divider(height: 20),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.end,
                                    children: [
                                      if (_selectedSubTab == 0) ...[
                                        ElevatedButton.icon(
                                          onPressed: () => _showRejectDialog(w),
                                          icon: const Icon(Icons.cancel_outlined, size: 16, color: Colors.white),
                                          label: const Text('Reject', style: TextStyle(color: Colors.white)),
                                          style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                                        ),
                                        const SizedBox(width: 8),
                                        ElevatedButton.icon(
                                          onPressed: () => _showActionDialog(w, false),
                                          icon: const Icon(Icons.verified_user_rounded, size: 16, color: Colors.white),
                                          label: const Text('Verify', style: TextStyle(color: Colors.white)),
                                          style: ElevatedButton.styleFrom(backgroundColor: Colors.blue),
                                        ),
                                      ] else if (_selectedSubTab == 1) ...[
                                        ElevatedButton.icon(
                                          onPressed: () => _showRejectDialog(w),
                                          icon: const Icon(Icons.cancel_outlined, size: 16, color: Colors.white),
                                          label: const Text('Reject', style: TextStyle(color: Colors.white)),
                                          style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                                        ),
                                        const SizedBox(width: 8),
                                        ElevatedButton.icon(
                                          onPressed: () => _showActionDialog(w, true),
                                          icon: const Icon(Icons.gavel_rounded, size: 16, color: Colors.white),
                                          label: const Text('Approve', style: TextStyle(color: Colors.white)),
                                          style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                                        ),
                                      ] else ...[
                                        const Text('No further actions', style: TextStyle(color: Colors.grey, fontSize: 12, fontStyle: FontStyle.italic)),
                                      ]
                                    ],
                                  )
                                ],
                              ),
                            ),
                          ),
                        );
                      },
                    ),
        )
      ],
    );
  }

  Widget _buildSubTabButton(int index, String label, Color color) {
    final bool isSelected = _selectedSubTab == index;
    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedSubTab = index;
        });
        _fetchWorkers();
      },
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 6, vertical: 12),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? color : Colors.grey.shade100,
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

  void _showDetailsDialog(Map<String, dynamic> w) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Review Self-Registration', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                ],
              ),
              const SizedBox(height: 16),
              _buildDetailSection('Personal Information', [
                _buildDetailRow('Full Name', w['full_name'] ?? 'N/A'),
                _buildDetailRow('Father Name', w['father_name'] ?? 'N/A'),
                _buildDetailRow('Date of Birth', w['dob'] ?? 'N/A'),
                _buildDetailRow('Gender', w['gender'] ?? 'N/A'),
                _buildDetailRow('Marital Status', w['marital_status'] ?? 'N/A'),
                _buildDetailRow('Contact', w['user_details']?['mobile'] ?? w['mobile_alt'] ?? 'N/A'),
              ]),
              const Divider(height: 32),
              _buildDetailSection('Welfare Information', [
                _buildDetailRow('Aadhaar Number', w['aadhaar_number'] ?? 'N/A'),
                _buildDetailRow('Job Profile/Trade', w['job_profile'] ?? 'N/A'),
                _buildDetailRow('Ration Card Number', w['ration_card_number'] ?? 'N/A'),
                _buildDetailRow('Address', w['aadhaar_address'] ?? 'N/A'),
              ]),
              const Divider(height: 32),
              _buildDetailSection('Bank Details', [
                _buildDetailRow('Account Number', w['bank_account_number'] ?? 'N/A'),
                _buildDetailRow('IFSC Code', w['bank_ifsc'] ?? 'N/A'),
                _buildDetailRow('Bank & Branch', w['bank_name_branch'] ?? 'N/A'),
              ]),
              if (w['verifier_remarks'] != null || w['approver_remarks'] != null) ...[
                const Divider(height: 32),
                _buildDetailSection('Officer Remarks', [
                  if (w['verifier_remarks'] != null) _buildDetailRow('Verifier Remarks', w['verifier_remarks']),
                  if (w['approver_remarks'] != null) _buildDetailRow('Approver Remarks', w['approver_remarks']),
                ]),
              ],
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailSection(String title, List<Widget> children) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFFE65100))),
        const SizedBox(height: 12),
        ...children,
      ],
    );
  }

  Widget _buildDetailRow(String label, String val) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(color: Colors.grey.shade600, fontSize: 13, fontWeight: FontWeight.w500)),
          const SizedBox(width: 20),
          Expanded(child: Text(val, textAlign: TextAlign.end, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
        ],
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
                    child: Icon(Icons.shield_rounded, color: Color(0xFFE65100), size: 48),
                  ),
                  const SizedBox(height: 16),
                  Text(_ownerName, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  Text(_ownerEmail, style: TextStyle(color: Colors.grey.shade600, fontSize: 14)),
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
                  _buildProfileRow('Name', _department?['department_name'] ?? 'Labour Department'),
                  const Divider(),
                  _buildProfileRow('Code', _department?['department_code']?.toString() ?? 'N/A'),
                  const Divider(),
                  _buildProfileRow('Scope', 'Department Owner (Special Approver)'),
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
        title: const Text('Dept Owner Dashboard', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
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
          _buildWorkersTab(),
          _buildProfileTab(),
        ],
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
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.dashboard_rounded), label: 'Dashboard'),
          BottomNavigationBarItem(icon: Icon(Icons.assignment_ind_rounded), label: 'Registrations'),
          BottomNavigationBarItem(icon: Icon(Icons.person_rounded), label: 'Profile'),
        ],
      ),
    );
  }
}
