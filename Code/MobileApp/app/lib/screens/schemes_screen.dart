import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/models.dart';
import '../services/api_service.dart';

class SchemesScreen extends StatefulWidget {
  final bool? isApproved;
  const SchemesScreen({super.key, this.isApproved});

  @override
  State<SchemesScreen> createState() => _SchemesScreenState();
}

class _SchemesScreenState extends State<SchemesScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final _apiService = ApiService();
  final _searchController = TextEditingController();

  List<Scheme> _schemes = [];
  List<SchemeApplication> _myApplications = [];
  bool _isLoadingSchemes = true;
  bool _isLoadingApps = true;

  bool _isCheckingStatus = true;
  bool _isApproved = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    
    if (widget.isApproved != null) {
      _isApproved = widget.isApproved!;
      _isCheckingStatus = false;
      if (_isApproved) {
        _tabController.addListener(() {
          if (_tabController.index == 1) {
            _fetchMyApplications();
          } else {
            _fetchSchemes();
          }
        });
        _fetchSchemes();
      }
    } else {
      _checkStatus();
    }
  }

  Future<void> _checkStatus() async {
    setState(() => _isCheckingStatus = true);
    final res = await _apiService.getDashboard();
    if (mounted) {
      setState(() {
        _isCheckingStatus = false;
        if (res['success'] == true && res['data'] != null) {
          final worker = res['data']['worker'];
          _isApproved = worker != null && (worker['status'] == 'approved' || worker['is_verified'] == true || worker['is_verified'] == 1);
        } else {
          _isApproved = false;
        }
      });
      if (_isApproved) {
        _tabController.addListener(() {
          if (_tabController.index == 1) {
            _fetchMyApplications();
          } else {
            _fetchSchemes();
          }
        });
        _fetchSchemes();
      }
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _fetchSchemes() async {
    setState(() => _isLoadingSchemes = true);
    final list = await _apiService.getSchemes(search: _searchController.text.trim());
    setState(() {
      _schemes = list;
      _isLoadingSchemes = false;
    });
  }

  Future<void> _fetchMyApplications() async {
    setState(() => _isLoadingApps = true);
    final list = await _apiService.getMyApplications();
    setState(() {
      _myApplications = list;
      _isLoadingApps = false;
    });
  }

  void _showSchemeDetails(Scheme scheme) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.75,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (_, scrollController) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.all(20),
          child: ListView(
            controller: scrollController,
            children: [
              Center(
                child: Container(
                  width: 50,
                  height: 5,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                scheme.name,
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Color(0xFFE65100)),
              ),
              const SizedBox(height: 8),
              Chip(
                label: Text(scheme.ministry),
                backgroundColor: const Color(0xFFFFF3E0),
                labelStyle: const TextStyle(color: Color(0xFFE65100), fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 16),
              _buildSectionTitle('Scheme Description'),
              Text(
                scheme.description,
                style: const TextStyle(fontSize: 14, height: 1.5, color: Color(0xFF374151)),
              ),
              const SizedBox(height: 32),
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () async {
                        final url = scheme.applyUrl;
                        if (url != null && url.isNotEmpty) {
                          final uri = Uri.parse(url);
                          if (await canLaunchUrl(uri)) {
                            await launchUrl(uri, mode: LaunchMode.externalApplication);
                          }
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('No website URL registered for this scheme.')),
                          );
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFE65100),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      icon: const Icon(Icons.open_in_browser_rounded),
                      label: const Text('Go To Website', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _handleStatusClick(scheme, ctx),
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: Color(0xFFE65100), width: 1.5),
                        foregroundColor: const Color(0xFFE65100),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      icon: Icon(
                        scheme.applicationStatus == 'received_benefit'
                            ? Icons.check_circle_rounded
                            : scheme.applicationStatus == 'applied'
                                ? Icons.hourglass_top_rounded
                                : Icons.info_outline_rounded,
                      ),
                      label: Text(
                        scheme.applicationStatus == 'received_benefit'
                            ? 'Benefit Received'
                            : scheme.applicationStatus == 'applied'
                                ? 'Status: Applied'
                                : 'Status',
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _handleStatusClick(Scheme scheme, BuildContext dialogCtx) async {
    final status = scheme.applicationStatus;

    if (status == null) {
      showDialog(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Apply for Scheme'),
          content: const Text('Are you applying in the scheme?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('No', style: TextStyle(color: Colors.grey)),
            ),
            TextButton(
              onPressed: () async {
                Navigator.of(dialogContext).pop();
                Navigator.of(dialogCtx).pop();
                
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Recording application status...')),
                );

                final res = await _apiService.applyForScheme(scheme.id, {});
                if (res['success'] == true) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Application recorded! Redirecting to application portal...'),
                      backgroundColor: Colors.green,
                    ),
                  );
                  final String? redirectUrl = res['redirect_url'] ?? scheme.applyUrl;
                  if (redirectUrl != null && redirectUrl.isNotEmpty) {
                    final Uri uri = Uri.parse(redirectUrl);
                    if (await canLaunchUrl(uri)) {
                      await launchUrl(uri, mode: LaunchMode.externalApplication);
                    }
                  }
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(res['message'] ?? 'Failed to record application.'),
                      backgroundColor: Colors.red,
                    ),
                  );
                }
                _fetchSchemes();
                _fetchMyApplications();
              },
              child: const Text('Yes', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE65100))),
            ),
          ],
        ),
      );
    } else if (status == 'applied') {
      showDialog(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Received Benefit Check'),
          content: const Text('Have you received scheme benefit?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('No', style: TextStyle(color: Colors.grey)),
            ),
            TextButton(
              onPressed: () async {
                Navigator.of(dialogContext).pop();
                Navigator.of(dialogCtx).pop();
                
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Updating benefit status...')),
                );

                final res = await _apiService.receiveBenefit(scheme.id);
                if (res['success'] == true) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Status updated to Benefit Received!'),
                      backgroundColor: Colors.green,
                    ),
                  );
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(res['message'] ?? 'Failed to update benefit status.'),
                      backgroundColor: Colors.red,
                    ),
                  );
                }
                _fetchSchemes();
                _fetchMyApplications();
              },
              child: const Text('Yes', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE65100))),
            ),
          ],
        ),
      );
    } else if (status == 'received_benefit') {
      showDialog(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Benefit Received'),
          content: const Text('You have already received the benefits of this scheme.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('OK', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE65100))),
            ),
          ],
        ),
      );
    }
  }

  Widget _buildSectionTitle(String title) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1F2937)),
        ),
        const SizedBox(height: 6),
        const Divider(),
        const SizedBox(height: 6),
      ],
    );
  }



  @override
  Widget build(BuildContext context) {
    if (_isCheckingStatus) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Schemes & Applications', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          backgroundColor: const Color(0xFFE65100),
          iconTheme: const IconThemeData(color: Colors.white),
        ),
        body: const Center(
          child: CircularProgressIndicator(color: Color(0xFFE65100)),
        ),
      );
    }

    if (!_isApproved) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Schemes & Applications', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          backgroundColor: const Color(0xFFE65100),
          iconTheme: const IconThemeData(color: Colors.white),
        ),
        body: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 40.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.amber.shade50,
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.amber.shade200, width: 2),
                ),
                child: Icon(
                  Icons.lock_clock_rounded,
                  size: 80,
                  color: Colors.amber.shade800,
                ),
              ),
              const SizedBox(height: 32),
              const Text(
                'Verification In Progress',
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1E293B),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Text(
                'Your profile is currently under review by the department. You will be able to view and apply for government schemes once your profile is verified and approved.',
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                  height: 1.5,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 40),
              SizedBox(
                width: 200,
                height: 48,
                child: ElevatedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFE65100),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(24),
                    ),
                  ),
                  child: const Text(
                    'Go Back',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Schemes & Applications', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFE65100),
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: const Color(0xFFB0BEC5),
          indicatorColor: Colors.white,
          indicatorWeight: 3,
          tabs: const [
            Tab(text: 'All Schemes'),
            Tab(text: 'My Applications'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // TAB 1: Schemes List
          Column(
            children: [
              Padding(
                padding: const EdgeInsets.all(12.0),
                child: TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Search scheme name...',
                    prefixIcon: const Icon(Icons.search, color: Color(0xFFE65100)),
                    suffixIcon: IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () {
                        _searchController.clear();
                        _fetchSchemes();
                      },
                    ),
                    filled: true,
                    fillColor: Colors.grey.shade100,
                    contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(30),
                      borderSide: BorderSide.none,
                    ),
                  ),
                  onChanged: (val) => _fetchSchemes(),
                ),
              ),
              Expanded(
                child: _isLoadingSchemes
                    ? const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)))
                    : _schemes.isEmpty
                        ? const Center(child: Text('No schemes available.'))
                        : (() {
                            final Map<String, List<Scheme>> grouped = {};
                            for (var s in _schemes) {
                              final String dept = s.departmentName ?? s.creatorName ?? 'General / Other Departments';
                              if (!grouped.containsKey(dept)) {
                                grouped[dept] = [];
                              }
                              grouped[dept]!.add(s);
                            }
                            final deptNames = grouped.keys.toList();

                            return ListView.builder(
                              itemCount: deptNames.length,
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                              itemBuilder: (ctx, deptIndex) {
                                final deptName = deptNames[deptIndex];
                                final deptSchemes = grouped[deptName] ?? [];
                                
                                return Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Padding(
                                      padding: const EdgeInsets.only(top: 16, bottom: 8, left: 4),
                                      child: Row(
                                        children: [
                                          Container(
                                            width: 4,
                                            height: 18,
                                            decoration: BoxDecoration(
                                              color: const Color(0xFFE65100),
                                              borderRadius: BorderRadius.circular(2),
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                          Expanded(
                                            child: Text(
                                              deptName,
                                              style: const TextStyle(
                                                fontWeight: FontWeight.bold,
                                                fontSize: 15,
                                                color: Color(0xFF374151),
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    ...deptSchemes.map((scheme) {
                                      return Card(
                                        elevation: 2,
                                        margin: const EdgeInsets.symmetric(vertical: 6),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                        child: ListTile(
                                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                          title: Text(
                                            scheme.name,
                                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1F2937)),
                                          ),
                                          subtitle: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              const SizedBox(height: 6),
                                              Text(
                                                scheme.description,
                                                maxLines: 2,
                                                overflow: TextOverflow.ellipsis,
                                                style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                                              ),
                                              const SizedBox(height: 8),
                                              Row(
                                                children: [
                                                  Icon(Icons.account_balance_rounded, size: 14, color: Colors.grey.shade600),
                                                  const SizedBox(width: 4),
                                                  Text(
                                                    scheme.ministry,
                                                    style: TextStyle(color: Colors.grey.shade700, fontSize: 12, fontWeight: FontWeight.w500),
                                                  ),
                                                  if (scheme.creatorName != null) ...[
                                                    const SizedBox(width: 12),
                                                    Icon(Icons.person_rounded, size: 14, color: Colors.teal.shade600),
                                                    const SizedBox(width: 4),
                                                    Text(
                                                      'By: ${scheme.creatorName}',
                                                      style: TextStyle(color: Colors.teal.shade700, fontSize: 12, fontWeight: FontWeight.w500),
                                                    ),
                                                  ],
                                                ],
                                              ),
                                            ],
                                          ),
                                          trailing: scheme.applicationStatus == 'received_benefit'
                                              ? Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                                  decoration: BoxDecoration(
                                                    color: Colors.teal.shade50,
                                                    borderRadius: BorderRadius.circular(12),
                                                    border: Border.all(color: Colors.teal.shade200),
                                                  ),
                                                  child: Row(
                                                    mainAxisSize: MainAxisSize.min,
                                                    children: [
                                                      Icon(Icons.check_circle_rounded, color: Colors.teal.shade700, size: 14),
                                                      const SizedBox(width: 4),
                                                      Text('Benefit Received', style: TextStyle(color: Colors.teal.shade900, fontSize: 11, fontWeight: FontWeight.bold)),
                                                    ],
                                                  ),
                                                )
                                              : scheme.applicationStatus == 'applied'
                                                  ? Container(
                                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                                      decoration: BoxDecoration(
                                                        color: Colors.orange.shade50,
                                                        borderRadius: BorderRadius.circular(12),
                                                        border: Border.all(color: Colors.orange.shade200),
                                                      ),
                                                      child: Row(
                                                        mainAxisSize: MainAxisSize.min,
                                                        children: [
                                                          Icon(Icons.hourglass_top_rounded, color: Colors.orange.shade800, size: 14),
                                                          const SizedBox(width: 4),
                                                          Text('Applied', style: TextStyle(color: Colors.orange.shade900, fontSize: 11, fontWeight: FontWeight.bold)),
                                                        ],
                                                      ),
                                                    )
                                                  : const Icon(Icons.arrow_forward_ios_rounded, size: 16, color: Color(0xFFE65100)),
                                          onTap: () => _showSchemeDetails(scheme),
                                        ),
                                      );
                                    }).toList(),
                                  ],
                                );
                              },
                            );
                          })(),
              ),
            ],
          ),
          // TAB 2: My Applications
          _isLoadingApps
              ? const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)))
              : _myApplications.isEmpty
                  ? const Center(child: Text('You have not applied for any scheme yet.'))
                  : ListView.builder(
                      itemCount: _myApplications.length,
                      padding: const EdgeInsets.all(12),
                      itemBuilder: (ctx, index) {
                        final app = _myApplications[index];
                        final schemeName = app.scheme?.name ?? 'Scheme';
                        final appliedDate = DateFormat('dd MMM yyyy').format(DateTime.parse(app.appliedAt));

                        Color statusColor = Colors.orange;
                        String statusText = 'Pending';
                        if (app.status == 'applied') {
                          statusColor = Colors.blue.shade700;
                          statusText = 'Applied';
                        } else if (app.status == 'verified') {
                          statusColor = Colors.teal;
                          statusText = 'Verified';
                        } else if (app.status == 'approved') {
                          statusColor = Colors.green;
                          statusText = 'Approved';
                        } else if (app.status == 'rejected') {
                          statusColor = Colors.red;
                          statusText = 'Rejected';
                        } else if (app.status == 'received_benefit') {
                          statusColor = Colors.purple;
                          statusText = 'Benefit Received';
                        }

                        return Card(
                          elevation: 2,
                          margin: const EdgeInsets.symmetric(vertical: 6),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Text(
                                        schemeName,
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: statusColor.withOpacity(0.1),
                                        borderRadius: BorderRadius.circular(20),
                                      ),
                                      child: Text(
                                        statusText,
                                        style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 12),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text('Application No: ${app.applicationNumber}', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                                    Text('Date: $appliedDate', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
        ],
      ),
    );
  }
}

