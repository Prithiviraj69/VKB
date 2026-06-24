import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/models.dart';
import '../services/api_service.dart';

class SchemesScreen extends StatefulWidget {
  const SchemesScreen({super.key});

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

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      if (_tabController.index == 1) {
        _fetchMyApplications();
      } else {
        _fetchSchemes();
      }
    });
    _fetchSchemes();
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
              const SizedBox(height: 20),
              _buildSectionTitle('Eligibility Criteria'),
              Text(
                scheme.eligibilityCriteria,
                style: const TextStyle(fontSize: 14, height: 1.5, color: Color(0xFF374151)),
              ),
              const SizedBox(height: 20),
              _buildSectionTitle('Scheme Benefits'),
              Text(
                scheme.benefits,
                style: const TextStyle(fontSize: 14, height: 1.5, color: Color(0xFF374151)),
              ),
              const SizedBox(height: 32),
              ElevatedButton(
                onPressed: () {
                  Navigator.of(ctx).pop();
                  _showApplyForm(scheme);
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFE65100),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: const Text('Apply Now', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      ),
    );
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

  void _showApplyForm(Scheme scheme) {
    final formKey = GlobalKey<FormState>();
    final annualIncomeController = TextEditingController();
    final remarkController = TextEditingController();
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
          child: Container(
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            padding: const EdgeInsets.all(24),
            child: Form(
              key: formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '${scheme.name} - Application Form',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFFE65100)),
                  ),
                  const SizedBox(height: 20),
                  const Text('Annual Income (in Rs.) *', style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: annualIncomeController,
                    keyboardType: TextInputType.number,
                    validator: (v) => (v == null || v.isEmpty) ? 'Please enter income' : null,
                    decoration: InputDecoration(
                      hintText: 'e.g. 120000',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text('Remarks (Optional)', style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: remarkController,
                    maxLines: 3,
                    decoration: InputDecoration(
                      hintText: 'Enter details about your occupation or need...',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                  ),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      onPressed: isSubmitting
                          ? null
                          : () async {
                              if (formKey.currentState!.validate()) {
                                setModalState(() => isSubmitting = true);
                                final applicationData = {
                                  'annual_income': annualIncomeController.text,
                                  'remarks': remarkController.text,
                                };
                                final res = await _apiService.applyForScheme(scheme.id, applicationData);
                                setModalState(() => isSubmitting = false);
                                
                                if (ctx.mounted) {
                                  Navigator.of(ctx).pop();
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(res['message'] ?? (res['success'] == true ? 'Application submitted successfully!' : 'An error occurred.')),
                                      backgroundColor: res['success'] == true ? Colors.green : Colors.red,
                                    ),
                                  );
                                  _tabController.animateTo(1);
                                  _fetchMyApplications();
                                }
                              }
                            },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFE65100),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      child: isSubmitting
                          ? const CircularProgressIndicator(color: Colors.white)
                          : const Text('Submit', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
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

  @override
  Widget build(BuildContext context) {
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
                        : ListView.builder(
                            itemCount: _schemes.length,
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            itemBuilder: (ctx, index) {
                              final scheme = _schemes[index];
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
                                        ],
                                      ),
                                    ],
                                  ),
                                  trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16, color: Color(0xFFE65100)),
                                  onTap: () => _showSchemeDetails(scheme),
                                ),
                              );
                            },
                          ),
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
                        if (app.status == 'approved') {
                          statusColor = Colors.green;
                          statusText = 'Approved';
                        } else if (app.status == 'rejected') {
                          statusColor = Colors.red;
                          statusText = 'Rejected';
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

