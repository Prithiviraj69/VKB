import 'dart:io';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:file_picker/file_picker.dart';
import '../services/api_service.dart';
import 'login_screen.dart';

class InstitutionDashboardScreen extends StatefulWidget {
  const InstitutionDashboardScreen({super.key});

  @override
  State<InstitutionDashboardScreen> createState() => _InstitutionDashboardScreenState();
}

class _InstitutionDashboardScreenState extends State<InstitutionDashboardScreen> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  String _institutionName = '';
  String _institutionEmail = '';
  String _institutionRole = '';
  List<dynamic> _registeredUsers = [];
  List<dynamic> _filteredUsers = [];

  final TextEditingController _searchController = TextEditingController();
  String _selectedFilterRole = 'all';

  @override
  void initState() {
    super.initState();
    _loadInstitutionDetails();
    _fetchUsers();
    _searchController.addListener(_applyFilters);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadInstitutionDetails() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _institutionName = prefs.getString('user_name') ?? 'Institution';
      _institutionEmail = prefs.getString('user_email') ?? '';
      _institutionRole = prefs.getString('user_role') ?? '';
    });
  }

  Future<void> _fetchUsers() async {
    setState(() {
      _isLoading = true;
    });

    final res = await _apiService.getRegisteredUsers();

    if (res['success'] == true) {
      setState(() {
        _registeredUsers = res['data'] ?? [];
        _filteredUsers = List.from(_registeredUsers);
        _isLoading = false;
      });
    } else {
      setState(() {
        _isLoading = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Failed to load registered users'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  void _applyFilters() {
    final query = _searchController.text.toLowerCase().trim();
    setState(() {
      _filteredUsers = _registeredUsers.where((user) {
        final matchesSearch = (user['name']?.toString().toLowerCase().contains(query) ?? false) ||
            (user['email']?.toString().toLowerCase().contains(query) ?? false) ||
            (user['mobile']?.toString().toLowerCase().contains(query) ?? false) ||
            (user['worker_details']?['full_name']?.toString().toLowerCase().contains(query) ?? false);

        final matchesRole = _selectedFilterRole == 'all' || user['role'] == _selectedFilterRole;

        return matchesSearch && matchesRole;
      }).toList();
    });
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
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('user_role');
      await prefs.remove('user_name');
      await prefs.remove('user_email');
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    }
  }

  List<String> _getAllowedRoles() {
    final role = _institutionRole;
    if (role == 'super-admin') {
      return ['verifier', 'approver', 'ngo', 'employer', 'contractor', 'beneficiary'];
    } else if (role == 'approver' || role == 'verifier') {
      return ['ngo', 'employer', 'contractor', 'beneficiary'];
    } else if (role == 'ngo') {
      return ['employer', 'contractor', 'beneficiary'];
    } else if (role == 'employer') {
      return ['contractor', 'beneficiary'];
    } else if (role == 'contractor') {
      return ['beneficiary'];
    }
    return [];
  }

  void _showAddUserDialog() {
    final allowedRoles = _getAllowedRoles();
    if (allowedRoles.isEmpty) return;

    String selectedRole = allowedRoles.first;

    // Institution Fields
    final nameController = TextEditingController();
    final emailController = TextEditingController();
    final passwordController = TextEditingController();

    // Worker Fields
    final fullNameController = TextEditingController();
    final mobileController = TextEditingController();
    final dobController = TextEditingController();
    final genderController = TextEditingController();
    final addressController = TextEditingController();

    String workerGender = 'male';

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        bool isSubmitting = false;
        String? errorMessage;
        return StatefulBuilder(
          builder: (context, setModalState) {
            Future<void> selectDate() async {
              final picked = await showDatePicker(
                context: context,
                initialDate: DateTime.now().subtract(const Duration(days: 365 * 18)),
                firstDate: DateTime(1900),
                lastDate: DateTime.now(),
              );
              if (picked != null) {
                setModalState(() {
                  dobController.text = "${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}";
                });
              }
            }

            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: Text(
                'Add New User',
                style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue.shade800),
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (errorMessage != null) ...[
                      Text(
                        errorMessage!,
                        style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                      const SizedBox(height: 12),
                    ],
                    const Text('Select Role *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: selectedRole,
                      items: allowedRoles
                          .map((r) => DropdownMenuItem(
                                value: r,
                                child: Text(_getRoleDisplayName(r)),
                              ))
                          .toList(),
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
                    const SizedBox(height: 16),
                    if (selectedRole == 'beneficiary') ...[
                      // Worker inputs
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
                      const Text('Mobile Number *', style: TextStyle(fontWeight: FontWeight.w600)),
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
                      const Text('Date of Birth *', style: TextStyle(fontWeight: FontWeight.w600)),
                      const SizedBox(height: 6),
                      TextField(
                        controller: dobController,
                        readOnly: true,
                        onTap: selectDate,
                        decoration: InputDecoration(
                          hintText: 'YYYY-MM-DD',
                          suffixIcon: const Icon(Icons.calendar_today),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                      ),
                      const SizedBox(height: 12),
                      const Text('Gender *', style: TextStyle(fontWeight: FontWeight.w600)),
                      const SizedBox(height: 6),
                      DropdownButtonFormField<String>(
                        value: workerGender,
                        items: const [
                          DropdownMenuItem(value: 'male', child: Text('Male')),
                          DropdownMenuItem(value: 'female', child: Text('Female')),
                          DropdownMenuItem(value: 'other', child: Text('Other')),
                        ],
                        onChanged: (val) {
                          if (val != null) {
                            setModalState(() {
                              workerGender = val;
                            });
                          }
                        },
                        decoration: InputDecoration(
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        ),
                      ),
                      const SizedBox(height: 12),
                      const Text('Aadhaar Address *', style: TextStyle(fontWeight: FontWeight.w600)),
                      const SizedBox(height: 6),
                      TextField(
                        controller: addressController,
                        maxLines: 2,
                        decoration: InputDecoration(
                          hintText: 'Enter complete address',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                      ),
                    ] else ...[
                      // Institution inputs
                      const Text('Name *', style: TextStyle(fontWeight: FontWeight.w600)),
                      const SizedBox(height: 6),
                      TextField(
                        controller: nameController,
                        decoration: InputDecoration(
                          hintText: 'Enter name',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                      ),
                      const SizedBox(height: 12),
                      const Text('Email *', style: TextStyle(fontWeight: FontWeight.w600)),
                      const SizedBox(height: 6),
                      TextField(
                        controller: emailController,
                        keyboardType: TextInputType.emailAddress,
                        decoration: InputDecoration(
                          hintText: 'Enter email address',
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
                    ],
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
                          final Map<String, dynamic> payload = {'role': selectedRole};

                          if (selectedRole == 'beneficiary') {
                            final name = fullNameController.text.trim();
                            final mobile = mobileController.text.trim();
                            final dob = dobController.text.trim();
                            final address = addressController.text.trim();

                            if (name.isEmpty || mobile.isEmpty || dob.isEmpty || address.isEmpty) {
                              setModalState(() {
                                errorMessage = 'All fields are required.';
                              });
                              return;
                            }
                            if (mobile.length != 10 || int.tryParse(mobile) == null) {
                              setModalState(() {
                                errorMessage = 'Mobile must be 10 digits.';
                              });
                              return;
                            }

                            payload['full_name'] = name;
                            payload['mobile'] = mobile;
                            payload['dob'] = dob;
                            payload['gender'] = workerGender;
                            payload['aadhaar_address'] = address;
                          } else {
                            final name = nameController.text.trim();
                            final email = emailController.text.trim();
                            final password = passwordController.text.trim();

                            if (name.isEmpty || email.isEmpty || password.isEmpty) {
                              setModalState(() {
                                errorMessage = 'All fields are required.';
                              });
                              return;
                            }
                            if (password.length < 6) {
                              setModalState(() {
                                errorMessage = 'Password must be at least 6 characters.';
                              });
                              return;
                            }

                            payload['name'] = name;
                            payload['email'] = email;
                            payload['password'] = password;
                          }

                          setModalState(() {
                            isSubmitting = true;
                            errorMessage = null;
                          });

                          final res = await _apiService.registerUser(payload);

                          setModalState(() {
                            isSubmitting = false;
                          });

                          if (res['success'] == true) {
                            Navigator.of(ctx).pop();
                            _fetchUsers(); // Refresh list
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(res['message'] ?? 'User registered successfully'),
                                  backgroundColor: Colors.green,
                                ),
                              );
                            }
                          } else {
                            setModalState(() {
                              errorMessage = res['message'] ?? 'Registration failed.';
                            });
                          }
                        },
                  child: isSubmitting
                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                      : const Text('Add User'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _showBulkUploadDialog() {
    final allowedRoles = _getAllowedRoles();
    if (allowedRoles.isEmpty) return;

    String selectedRole = allowedRoles.first;
    FilePickerResult? pickedFile;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        bool isUploading = false;
        String? dialogError;
        Map<String, dynamic>? summaryResults;
        return StatefulBuilder(
          builder: (context, setModalState) {
            Future<void> pickCSV() async {
              final result = await FilePicker.platform.pickFiles(
                type: FileType.custom,
                allowedExtensions: ['csv'],
              );
              if (result != null) {
                setModalState(() {
                  pickedFile = result;
                  dialogError = null;
                });
              }
            }

            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: Text(
                'Bulk Upload via CSV',
                style: TextStyle(fontWeight: FontWeight.bold, color: Colors.orange.shade800),
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (dialogError != null) ...[
                      Text(
                        dialogError!,
                        style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                      const SizedBox(height: 12),
                    ],
                    if (summaryResults != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.green.shade50,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.green.shade200),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              summaryResults!['message'] ?? 'Bulk registration complete',
                              style: TextStyle(color: Colors.green.shade900, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 6),
                            Text('Success Count: ${summaryResults!['success_count']}'),
                            Text('Failed Count: ${summaryResults!['failed_count']}'),
                          ],
                        ),
                      ),
                      if (summaryResults!['errors'] != null && (summaryResults!['errors'] as List).isNotEmpty) ...[
                        const SizedBox(height: 12),
                        const Text(
                          'Errors List:',
                          style: TextStyle(fontWeight: FontWeight.bold, color: Colors.red),
                        ),
                        const SizedBox(height: 6),
                        Container(
                          constraints: const BoxConstraints(maxHeight: 120),
                          width: double.maxFinite,
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey.shade300),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: ListView.builder(
                            shrinkWrap: true,
                            itemCount: (summaryResults!['errors'] as List).length,
                            itemBuilder: (context, i) {
                              return Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 4.0),
                                child: Text(
                                  summaryResults!['errors'][i].toString(),
                                  style: const TextStyle(color: Colors.red, fontSize: 12),
                                ),
                              );
                            },
                          ),
                        ),
                      ],
                      const SizedBox(height: 20),
                    ],
                    const Text('Select Role for CSV Users *', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: selectedRole,
                      items: allowedRoles
                          .map((r) => DropdownMenuItem(
                                value: r,
                                child: Text(_getRoleDisplayName(r)),
                              ))
                          .toList(),
                      onChanged: isUploading || summaryResults != null
                          ? null
                          : (val) {
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
                    const SizedBox(height: 20),
                    const Text(
                      'Expected CSV Format Headers:',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      selectedRole == 'beneficiary'
                          ? 'full_name, mobile, dob, gender, aadhaar_address'
                          : 'name, email, password',
                      style: TextStyle(
                        fontFamily: 'monospace',
                        fontSize: 12,
                        color: Colors.grey.shade700,
                        backgroundColor: Colors.grey.shade100,
                      ),
                    ),
                    const SizedBox(height: 20),
                    Center(
                      child: OutlinedButton.icon(
                        onPressed: isUploading || summaryResults != null ? null : pickCSV,
                        icon: const Icon(Icons.file_present),
                        label: Text(pickedFile == null ? 'Select CSV File' : pickedFile!.files.first.name),
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: isUploading
                      ? null
                      : () {
                          Navigator.of(ctx).pop();
                          if (summaryResults != null) {
                            _fetchUsers(); // Refresh list if upload succeeded
                          }
                        },
                  child: Text(summaryResults != null ? 'Close' : 'Cancel', style: const TextStyle(color: Colors.grey)),
                ),
                if (summaryResults == null)
                  ElevatedButton(
                    onPressed: isUploading || pickedFile == null
                        ? null
                        : () async {
                            setModalState(() {
                              isUploading = true;
                              dialogError = null;
                            });

                            try {
                              final fileObj = pickedFile!.files.first;
                              List<int> bytes;
                              if (kIsWeb) {
                                bytes = fileObj.bytes!;
                              } else {
                                bytes = await File(fileObj.path!).readAsBytes();
                              }

                              final res = await _apiService.bulkRegisterUsers(
                                bytes,
                                fileObj.name,
                                selectedRole,
                              );

                              setModalState(() {
                                isUploading = false;
                              });

                              if (res['success'] == true) {
                                setModalState(() {
                                  summaryResults = res;
                                });
                              } else {
                                setModalState(() {
                                  dialogError = res['message'] ?? 'Upload failed.';
                                });
                              }
                            } catch (e) {
                              setModalState(() {
                                isUploading = false;
                                dialogError = 'Error reading file: $e';
                              });
                            }
                          },
                    child: isUploading
                        ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                        : const Text('Upload & Import'),
                  ),
              ],
            );
          },
        );
      },
    );
  }

  Color _getRoleColor(String? role) {
    switch (role) {
      case 'super-admin':
        return Colors.red.shade700;
      case 'approver':
      case 'verifier':
        return Colors.blue.shade700;
      case 'ngo':
        return Colors.teal.shade700;
      case 'employer':
        return Colors.purple.shade700;
      case 'contractor':
        return Colors.orange.shade700;
      case 'beneficiary':
        return Colors.green.shade700;
      default:
        return Colors.grey.shade700;
    }
  }

  String _getRoleDisplayName(String? role) {
    if (role == 'beneficiary') return 'Beneficiary';
    if (role == 'verifier') return 'Govt Dept (Verifier)';
    if (role == 'approver') return 'Govt Dept (Approver)';
    return role?.toUpperCase() ?? 'N/A';
  }

  int _getCountByRole(String role) {
    if (role == 'govt') {
      return _registeredUsers.where((u) => u['role'] == 'verifier' || u['role'] == 'approver').length;
    }
    return _registeredUsers.where((u) => u['role'] == role).length;
  }

  Widget _buildSuperAdminStats() {
    if (_institutionRole != 'super-admin') return const SizedBox.shrink();

    final total = _registeredUsers.length;
    final workers = _getCountByRole('beneficiary');
    final ngos = _getCountByRole('ngo');
    final employers = _getCountByRole('employer');
    final contractors = _getCountByRole('contractor');
    final govt = _getCountByRole('govt');

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'System Overview',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.black87),
          ),
          const SizedBox(height: 12),
          GridView.count(
            crossAxisCount: 3,
            crossAxisSpacing: 8,
            mainAxisSpacing: 8,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            childAspectRatio: 1.25,
            children: [
              _buildStatCard('Total Users', total.toString(), Icons.people, Colors.blue),
              _buildStatCard('Beneficiaries', workers.toString(), Icons.person, Colors.green),
              _buildStatCard('Govt Depts', govt.toString(), Icons.account_balance, Colors.red),
              _buildStatCard('NGOs', ngos.toString(), Icons.group, Colors.teal),
              _buildStatCard('Employers', employers.toString(), Icons.business, Colors.purple),
              _buildStatCard('Contractors', contractors.toString(), Icons.engineering, Colors.orange),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withOpacity(0.2)),
        boxShadow: [
          BoxShadow(
            color: color.withOpacity(0.03),
            blurRadius: 6,
            offset: const Offset(0, 3),
          )
        ],
      ),
      padding: const EdgeInsets.all(8),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: color),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.grey),
          ),
        ],
      ),
    );
  }

  void _showProfileSheet(Map<String, dynamic> user) {
    final isWorker = user['role'] == 'beneficiary';
    final workerDetails = user['worker_details'];
    bool isActive = user['is_active'] ?? true;
    bool isToggling = false;

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
            maxHeight: MediaQuery.of(context).size.height * 0.85,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user['name'] ?? workerDetails?['full_name'] ?? 'User Details',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _getRoleDisplayName(user['role']),
                          style: TextStyle(
                            color: _getRoleColor(user['role']),
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
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
              
              // Scrollable Details
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // User Status Management
                      Container(
                        padding: const EdgeInsets.all(12),
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
                                  'Account Status',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                ),
                                Text(
                                  isActive ? 'Active (Can login)' : 'Inactive (Blocked)',
                                  style: TextStyle(
                                    color: isActive ? Colors.green : Colors.red,
                                    fontSize: 12,
                                  ),
                                ),
                              ],
                            ),
                            isToggling
                                ? const CircularProgressIndicator()
                                : Switch(
                                    value: isActive,
                                    activeColor: Colors.green,
                                    onChanged: (val) async {
                                      setModalState(() {
                                        isToggling = true;
                                      });
                                      final res = await _apiService.toggleUserStatus(user['id']);
                                      setModalState(() {
                                        isToggling = false;
                                      });
                                      if (res['success'] == true) {
                                        setModalState(() {
                                          isActive = res['is_active'] == true;
                                        });
                                        _fetchUsers(); // refresh parent dashboard list
                                      } else {
                                        if (mounted) {
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(content: Text(res['message'] ?? 'Failed to update status')),
                                          );
                                        }
                                      }
                                    },
                                  ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),

                      if (isWorker) ...[
                        if (workerDetails == null) ...[
                          const Padding(
                            padding: EdgeInsets.symmetric(vertical: 20),
                            child: Center(
                              child: Text(
                                'Worker has not filled their profile details yet.',
                                style: TextStyle(color: Colors.grey, fontStyle: FontStyle.italic),
                              ),
                            ),
                          ),
                        ] else ...[
                          // Profile sections
                          _buildSectionTitle('Personal Details'),
                          _buildInfoRow('Father\'s Name', workerDetails['father_name']),
                          _buildInfoRow('DOB', workerDetails['dob']?.toString().split('T').first),
                          _buildInfoRow('Gender', workerDetails['gender']?.toString().toUpperCase()),
                          _buildInfoRow('Marital Status', workerDetails['marital_status']),
                          _buildInfoRow('Category', workerDetails['category']),
                          _buildInfoRow('Alternate Mobile', workerDetails['mobile_alt']),
                          
                          _buildSectionTitle('Addresses'),
                          _buildInfoRow('Aadhaar Address', workerDetails['aadhaar_address']),
                          _buildInfoRow('Local Address', workerDetails['local_address']),

                          _buildSectionTitle('Education & Employment'),
                          _buildInfoRow('Education Qualification', workerDetails['education']),
                          _buildInfoRow('Education Status', workerDetails['education_status']),
                          _buildInfoRow('Employment Status', workerDetails['employment_status']),
                          if (workerDetails['employment_status'] == 'Self-Employed') ...[
                            _buildInfoRow('Business Name', workerDetails['business_name']),
                            _buildInfoRow('Business Address', workerDetails['business_address']),
                            _buildInfoRow('Business Category', workerDetails['business_category']),
                          ] else if (workerDetails['employment_status'] == 'Employed') ...[
                            _buildInfoRow('Job Profile', workerDetails['job_profile']),
                          ],

                          _buildSectionTitle('Bank Account'),
                          _buildInfoRow('Account Number', workerDetails['bank_account_number']),
                          _buildInfoRow('IFSC Code', workerDetails['bank_ifsc']),

                          if (workerDetails['is_bocw_registered'] == true) ...[
                            _buildSectionTitle('BOCW Registration'),
                            _buildInfoRow('Registration No.', workerDetails['bocw_reg_number']),
                            _buildInfoRow('Reg Date', workerDetails['bocw_reg_date']?.toString().split('T').first),
                            _buildInfoRow('Valid Upto', workerDetails['bocw_valid_upto']?.toString().split('T').first),
                          ],

                          // Photo / Signature
                          if (workerDetails['photo_url'] != null || workerDetails['signature_url'] != null) ...[
                            _buildSectionTitle('Documents & Uploads'),
                            const SizedBox(height: 8),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceAround,
                              children: [
                                if (workerDetails['photo_url'] != null)
                                  Column(
                                    children: [
                                      const Text('Photo', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                                      const SizedBox(height: 8),
                                      Container(
                                        width: 100,
                                        height: 100,
                                        decoration: BoxDecoration(
                                          border: Border.all(color: Colors.grey.shade300),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: ClipRRect(
                                          borderRadius: BorderRadius.circular(8),
                                          child: Image.network(
                                            _apiService.resolveImageUrl(workerDetails['photo_url']),
                                            fit: BoxFit.cover,
                                            errorBuilder: (_, __, ___) => const Icon(Icons.person, size: 50),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                if (workerDetails['signature_url'] != null)
                                  Column(
                                    children: [
                                      const Text('Signature', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                                      const SizedBox(height: 8),
                                      Container(
                                        width: 100,
                                        height: 100,
                                        decoration: BoxDecoration(
                                          border: Border.all(color: Colors.grey.shade300),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: ClipRRect(
                                          borderRadius: BorderRadius.circular(8),
                                          child: Image.network(
                                            _apiService.resolveImageUrl(workerDetails['signature_url']),
                                            fit: BoxFit.contain,
                                            errorBuilder: (_, __, ___) => const Icon(Icons.gesture, size: 50),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                              ],
                            ),
                          ],
                        ],
                      ] else ...[
                        // Institution Specific
                        _buildSectionTitle('Institution Details'),
                        _buildInfoRow('Email Address', user['email']),
                        _buildInfoRow('Registered on', user['created_at']?.toString().split('T').first),
                        _buildInfoRow('Created by UUID', user['registered_by']),
                      ],
                      const SizedBox(height: 20),
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

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(top: 16.0, bottom: 8.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFFE65100)),
          ),
          const SizedBox(height: 4),
          const Divider(thickness: 1),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String label, dynamic value) {
    final String valStr = (value == null || value.toString().isEmpty) ? 'N/A' : value.toString();
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(color: Colors.grey.shade600, fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(
            child: Text(
              valStr,
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Institution Dashboard'),
        backgroundColor: const Color(0xFFE65100),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchUsers,
          ),
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            onPressed: _handleLogout,
          ),
        ],
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [const Color(0xFFFFF3E0), Colors.white],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: Column(
          children: [
            // Header Info Card
            Container(
              margin: const EdgeInsets.all(16),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  )
                ],
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 30,
                    backgroundColor: const Color(0xFFFFE0B2),
                    child: Icon(Icons.business, size: 36, color: const Color(0xFFE65100)),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _institutionName,
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _institutionEmail.isNotEmpty ? _institutionEmail : 'Logged in',
                          style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                        ),
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFFE0B2),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            _getRoleDisplayName(_institutionRole),
                            style: const TextStyle(
                              color: Color(0xFFE65100),
                              fontWeight: FontWeight.bold,
                              fontSize: 11,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            _buildSuperAdminStats(),

            // Register Options Row
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: _showAddUserDialog,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.blue.shade700,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      icon: const Icon(Icons.person_add),
                      label: const Text('Register User', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: _showBulkUploadDialog,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.orange.shade800,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      icon: const Icon(Icons.upload_file),
                      label: const Text('Bulk Import', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Search and Filters Bar
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _searchController,
                      decoration: InputDecoration(
                        hintText: 'Search registered users...',
                        prefixIcon: const Icon(Icons.search),
                        filled: true,
                        fillColor: Colors.white,
                        contentPadding: const EdgeInsets.symmetric(vertical: 8),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: Colors.grey.shade300),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: _selectedFilterRole,
                        items: const [
                          DropdownMenuItem(value: 'all', child: Text('All Roles')),
                          DropdownMenuItem(value: 'verifier', child: Text('Verifier')),
                          DropdownMenuItem(value: 'approver', child: Text('Approver')),
                          DropdownMenuItem(value: 'ngo', child: Text('NGO')),
                          DropdownMenuItem(value: 'employer', child: Text('Employer')),
                          DropdownMenuItem(value: 'contractor', child: Text('Contractor')),
                          DropdownMenuItem(value: 'beneficiary', child: Text('Beneficiary')),
                        ],
                        onChanged: (val) {
                          if (val != null) {
                            setState(() {
                              _selectedFilterRole = val;
                              _applyFilters();
                            });
                          }
                        },
                      ),
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 12),

            // Registered List header
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Registered Users (${_filteredUsers.length})',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                ],
              ),
            ),

            // Users List View
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _filteredUsers.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.people_outline_rounded, size: 64, color: Colors.grey.shade400),
                              const SizedBox(height: 12),
                              Text(
                                'No registered users found.',
                                style: TextStyle(color: Colors.grey.shade600, fontSize: 16),
                              ),
                            ],
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _filteredUsers.length,
                          itemBuilder: (context, i) {
                            final user = _filteredUsers[i];
                            final isWorker = user['role'] == 'beneficiary';
                            final String name = user['name'] ?? user['worker_details']?['full_name'] ?? (isWorker ? 'Beneficiary' : 'N/A');
                            final String identifier = isWorker
                                ? (user['mobile'] ?? 'N/A')
                                : (user['email'] ?? 'N/A');

                            return Card(
                              elevation: 2,
                              margin: const EdgeInsets.only(bottom: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              child: ExpansionTile(
                                leading: CircleAvatar(
                                  backgroundColor: _getRoleColor(user['role']).withOpacity(0.1),
                                  child: Icon(
                                    isWorker ? Icons.person : Icons.business,
                                    color: _getRoleColor(user['role']),
                                  ),
                                ),
                                title: Text(
                                  name,
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                subtitle: Text(
                                  identifier,
                                  style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                                ),
                                trailing: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: _getRoleColor(user['role']).withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Text(
                                    _getRoleDisplayName(user['role']),
                                    style: TextStyle(
                                      color: _getRoleColor(user['role']),
                                      fontSize: 11,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                                 children: [
                                  Padding(
                                    padding: const EdgeInsets.all(16.0),
                                    child: Column(
                                      children: [
                                        AnimatedCrossFade(
                                          crossFadeState: isWorker ? CrossFadeState.showFirst : CrossFadeState.showSecond,
                                          duration: const Duration(milliseconds: 100),
                                          firstChild: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              _buildDetailRow('DOB:', user['worker_details']?['dob']?.toString().split('T').first ?? 'N/A'),
                                              _buildDetailRow('Gender:', user['worker_details']?['gender']?.toString().toUpperCase() ?? 'N/A'),
                                              _buildDetailRow('Address:', user['worker_details']?['aadhaar_address'] ?? 'N/A'),
                                              _buildDetailRow('Status:', user['is_active'] == true ? 'Active' : 'Inactive'),
                                            ],
                                          ),
                                          secondChild: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              _buildDetailRow('Email:', user['email'] ?? 'N/A'),
                                              _buildDetailRow('Status:', user['is_active'] == true ? 'Active' : 'Inactive'),
                                              _buildDetailRow('Created:', user['created_at']?.toString().split('T').first ?? 'N/A'),
                                            ],
                                          ),
                                        ),
                                        const SizedBox(height: 12),
                                        const Divider(height: 1),
                                        const SizedBox(height: 8),
                                        Row(
                                          mainAxisAlignment: MainAxisAlignment.end,
                                          children: [
                                            TextButton.icon(
                                              onPressed: () => _showProfileSheet(user),
                                              icon: const Icon(Icons.visibility),
                                              label: const Text('View Profile'),
                                              style: TextButton.styleFrom(
                                                foregroundColor: const Color(0xFFE65100),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ],
                                    ),
                                  )
                                ],
                              ),
                            );
                          },
                        ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 80,
            child: Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.grey),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}
