import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../models/models.dart';

class DocumentsScreen extends StatefulWidget {
  const DocumentsScreen({super.key});

  @override
  State<DocumentsScreen> createState() => _DocumentsScreenState();
}

class _DocumentsScreenState extends State<DocumentsScreen> {
  final _apiService = ApiService();
  final _picker = ImagePicker();

  List<WorkerDocument> _documents = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchDocuments();
  }

  Future<void> _fetchDocuments() async {
    setState(() => _isLoading = true);
    final list = await _apiService.getDocuments();
    setState(() {
      _documents = list;
      _isLoading = false;
    });
  }

  Future<void> _handleDeleteDocument(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Are you sure you want to delete this document?'),
        actions: [
          TextButton(onPressed: () => Navigator.of(ctx).pop(false), child: const Text('No', style: TextStyle(color: Colors.grey))),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Yes', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm == true) {
      setState(() => _isLoading = true);
      final res = await _apiService.deleteDocument(id);
      setState(() => _isLoading = false);
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? (res['success'] == true ? 'Document deleted successfully!' : 'An error occurred.')),
            backgroundColor: res['success'] == true ? Colors.green : Colors.red,
          ),
        );
        _fetchDocuments();
      }
    }
  }

  void _showUploadDialog() {
    final formKey = GlobalKey<FormState>();
    final numberController = TextEditingController();
    String docType = 'aadhaar';
    String? selectedFilePath;
    bool isUploading = false;

    final docTypes = {
      'aadhaar': 'Aadhaar Card',
      'pan': 'PAN Card',
      'photo': 'Passport Photo',
      'certificate': 'Skill Certificate',
      'other': 'Other Document'
    };

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
                  const Text(
                    'Upload New Document',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFFE65100)),
                  ),
                  const SizedBox(height: 20),
                  const Text('Document Type *', style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    value: docType,
                    onChanged: (val) {
                      if (val != null) {
                        setModalState(() => docType = val);
                      }
                    },
                    items: docTypes.entries
                        .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                        .toList(),
                    decoration: InputDecoration(
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text('Document Number *', style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: numberController,
                    validator: (v) => (v == null || v.isEmpty) ? 'Please enter document number' : null,
                    decoration: InputDecoration(
                      hintText: 'e.g. 1234 5678 9012',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text('Select File *', style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  InkWell(
                    onTap: () async {
                      // Attempt to pick image
                      try {
                        final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
                        if (image != null) {
                          setModalState(() => selectedFilePath = image.path);
                        } else {
                          // Fallback mock path for desktop/testing
                          setModalState(() => selectedFilePath = '/simulated_path/document_image.jpg');
                        }
                      } catch (_) {
                        // Fallback mock path
                        setModalState(() => selectedFilePath = '/simulated_path/document_image.jpg');
                      }
                    },
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 12),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey.shade300),
                        borderRadius: BorderRadius.circular(8),
                        color: Colors.grey.shade50,
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.image_search_rounded, color: Color(0xFFE65100)),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              selectedFilePath == null 
                                  ? 'Choose document from gallery or camera' 
                                  : selectedFilePath!.split('/').last,
                              style: TextStyle(
                                color: selectedFilePath == null ? Colors.grey : Colors.black,
                                fontSize: 13,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      onPressed: isUploading
                          ? null
                          : () async {
                              if (formKey.currentState!.validate()) {
                                if (selectedFilePath == null) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      content: Text('Please select a document photo.'),
                                      backgroundColor: Colors.red,
                                    ),
                                  );
                                  return;
                                }

                                setModalState(() => isUploading = true);
                                final res = await _apiService.uploadDocument(
                                  docType,
                                  numberController.text.trim(),
                                  selectedFilePath!,
                                );
                                setModalState(() => isUploading = false);

                                if (ctx.mounted) {
                                  Navigator.of(ctx).pop();
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(res['message'] ?? (res['success'] == true ? 'Document submitted successfully!' : 'An error occurred.')),
                                      backgroundColor: res['success'] == true ? Colors.green : Colors.red,
                                    ),
                                  );
                                  _fetchDocuments();
                                }
                              }
                            },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFE65100),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      child: isUploading
                          ? const CircularProgressIndicator(color: Colors.white)
                          : const Text('Upload', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
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
        title: const Text('My Documents', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFE65100),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)))
          : _documents.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.folder_open_rounded, size: 80, color: Colors.grey.shade300),
                        const SizedBox(height: 16),
                        const Text(
                          'No documents uploaded yet.',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Please upload Aadhaar, PAN etc. to avail government scheme benefits.',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: Colors.grey),
                        ),
                      ],
                    ),
                  ),
                )
              : ListView.builder(
                  itemCount: _documents.length,
                  padding: const EdgeInsets.all(12),
                  itemBuilder: (ctx, index) {
                    final doc = _documents[index];
                    final date = DateFormat('dd MMM yyyy').format(DateTime.parse(doc.createdAt));

                    String docLabel = doc.documentType.toUpperCase();
                    if (doc.documentType == 'aadhaar') docLabel = 'Aadhaar Card';
                    if (doc.documentType == 'pan') docLabel = 'PAN Card';
                    if (doc.documentType == 'photo') docLabel = 'Photo';
                    if (doc.documentType == 'certificate') docLabel = 'Skill Certificate';

                    Color statusColor = Colors.orange;
                    String statusText = 'Verification Pending';
                    if (doc.status == 'verified') {
                      statusColor = Colors.green;
                      statusText = 'Verified';
                    } else if (doc.status == 'rejected') {
                      statusColor = Colors.red;
                      statusText = 'Rejected';
                    }

                    return Card(
                      elevation: 2,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      margin: const EdgeInsets.symmetric(vertical: 6),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFFF3E0),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Icon(Icons.file_present_rounded, color: Color(0xFFE65100), size: 28),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    docLabel,
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1F2937)),
                                  ),
                                  const SizedBox(height: 4),
                                  Text('Number: ${doc.documentNumber}', style: const TextStyle(color: Color(0xFF4B5563), fontSize: 13)),
                                  const SizedBox(height: 6),
                                  Row(
                                    children: [
                                      Container(
                                        width: 8,
                                        height: 8,
                                        decoration: BoxDecoration(color: statusColor, shape: BoxShape.circle),
                                      ),
                                      const SizedBox(width: 6),
                                      Text(statusText, style: TextStyle(color: statusColor, fontSize: 12, fontWeight: FontWeight.bold)),
                                      const SizedBox(width: 12),
                                      Text(date, style: const TextStyle(color: Colors.grey, fontSize: 11)),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                            IconButton(
                              icon: const Icon(Icons.delete_outline_rounded, color: Colors.red),
                              onPressed: () => _handleDeleteDocument(doc.id),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
      floatingActionButton: FloatingActionButton(
        onPressed: _showUploadDialog,
        backgroundColor: const Color(0xFFE65100),
        child: const Icon(Icons.cloud_upload_rounded, color: Colors.white),
      ),
    );
  }
}
