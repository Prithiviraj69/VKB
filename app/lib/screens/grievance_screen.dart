import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/models.dart';
import '../services/api_service.dart';

class GrievanceScreen extends StatefulWidget {
  const GrievanceScreen({super.key});

  @override
  State<GrievanceScreen> createState() => _GrievanceScreenState();
}

class _GrievanceScreenState extends State<GrievanceScreen> {
  final _apiService = ApiService();
  List<Grievance> _grievances = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchGrievances();
  }

  Future<void> _fetchGrievances() async {
    setState(() => _isLoading = true);
    final list = await _apiService.getGrievances();
    setState(() {
      _grievances = list;
      _isLoading = false;
    });
  }

  void _showFileGrievanceForm() {
    final formKey = GlobalKey<FormState>();
    final subjectController = TextEditingController();
    final descriptionController = TextEditingController();
    String category = 'Scheme Related';
    bool isSubmitting = false;

    final categories = [
      'Scheme Related',
      'Payment Delay',
      'Document Verification',
      'Portal Issues',
      'Other Grievances'
    ];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: StatefulBuilder(
          builder: (context, setModalState) => Container(
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            padding: const EdgeInsets.all(24),
            child: Form(
              key: formKey,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'File New Grievance',
                      style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFFE65100)),
                    ),
                    const SizedBox(height: 20),
                    const Text('Subject *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: subjectController,
                      validator: (v) => (v == null || v.isEmpty) ? 'Please enter subject' : null,
                      decoration: InputDecoration(
                        hintText: 'e.g. Did not receive scheme benefit',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text('Category *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      value: category,
                      onChanged: (val) {
                        if (val != null) {
                          setModalState(() => category = val);
                        }
                      },
                      items: categories
                          .map((c) => DropdownMenuItem(value: c, child: Text(c)))
                          .toList(),
                      decoration: InputDecoration(
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text('Description *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: descriptionController,
                      maxLines: 4,
                      validator: (v) => (v == null || v.isEmpty) ? 'Please enter description' : null,
                      decoration: InputDecoration(
                        hintText: 'Write full details of your complaint here...',
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
                                  final res = await _apiService.fileGrievance(
                                    subjectController.text,
                                    category,
                                    descriptionController.text,
                                  );
                                  setModalState(() => isSubmitting = false);

                                  if (ctx.mounted) {
                                    Navigator.of(ctx).pop();
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(
                                        content: Text(res['message'] ?? (res['success'] == true ? 'Grievance submitted successfully!' : 'An error occurred.')),
                                        backgroundColor: res['success'] == true ? Colors.green : Colors.red,
                                      ),
                                    );
                                    _fetchGrievances();
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
                            : const Text('Submit Grievance', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showGrievanceDetails(Grievance g) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text(g.ticketNumber, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE65100))),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('Category: ${g.category}', style: const TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Text('Subject: ${g.subject}', style: const TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 12),
              const Text('Description:', style: TextStyle(color: Colors.grey, fontSize: 12)),
              Text(g.description),
              const SizedBox(height: 16),
              const Divider(),
              const SizedBox(height: 8),
              Text(
                'Status: ${g.status.toUpperCase()}',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: g.status == 'resolved' || g.status == 'closed' ? Colors.green : Colors.orange,
                ),
              ),
              if (g.resolution != null && g.resolution!.isNotEmpty) ...[
                const SizedBox(height: 12),
                const Text('Resolution:', style: TextStyle(color: Colors.grey, fontSize: 12)),
                Text(g.resolution!, style: const TextStyle(color: Colors.green, fontWeight: FontWeight.w500)),
              ],
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('OK', style: TextStyle(color: Color(0xFFE65100), fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Grievance Redressal', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFE65100),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)))
          : _grievances.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.feedback_outlined, size: 80, color: Colors.grey.shade400),
                        const SizedBox(height: 16),
                        const Text(
                          'No grievances filed yet.',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'If you have an issue with any scheme or service, you can file a grievance.',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: Colors.grey),
                        ),
                      ],
                    ),
                  ),
                )
              : ListView.builder(
                  itemCount: _grievances.length,
                  padding: const EdgeInsets.all(12),
                  itemBuilder: (ctx, index) {
                    final g = _grievances[index];
                    final date = DateFormat('dd MMM yyyy').format(DateTime.parse(g.createdAt));

                    Color statusColor = Colors.orange;
                    if (g.status == 'resolved' || g.status == 'closed') {
                      statusColor = Colors.green;
                    }

                    return Card(
                      elevation: 2,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      margin: const EdgeInsets.symmetric(vertical: 6),
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        title: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              g.ticketNumber,
                              style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE65100)),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: statusColor.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                g.status.toUpperCase(),
                                style: TextStyle(color: statusColor, fontSize: 11, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 8),
                            Text(
                              g.subject,
                              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15, color: Color(0xFF374151)),
                            ),
                            const SizedBox(height: 4),
                            Text('Date: $date', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                          ],
                        ),
                        trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16, color: Color(0xFFE65100)),
                        onTap: () => _showGrievanceDetails(g),
                      ),
                    );
                  },
                ),
      floatingActionButton: FloatingActionButton(
        onPressed: _showFileGrievanceForm,
        backgroundColor: const Color(0xFFE65100),
        child: const Icon(Icons.add_comment_rounded, color: Colors.white),
      ),
    );
  }
}
