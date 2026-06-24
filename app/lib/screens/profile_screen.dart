import 'package:flutter/material.dart';
import 'dart:typed_data';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../services/api_service.dart';
import '../models/models.dart';
import 'dashboard_screen.dart';
import 'login_screen.dart';

class ProfileScreen extends StatefulWidget {
  final Map<String, dynamic>? digiLockerData;
  const ProfileScreen({super.key, this.digiLockerData});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _apiService = ApiService();
  final _personalFormKey = GlobalKey<FormState>();
  final _educationFormKey = GlobalKey<FormState>();
  final _employmentFormKey = GlobalKey<FormState>();
  final _bocwFormKey = GlobalKey<FormState>();
  final _picker = ImagePicker();

  bool _isLoading = true;
  bool _hasProfile = false;
  String? _savingSection;

  // ── Collapsible section state ──
  bool _secPersonal     = false;   // closed by default
  bool _secEducation    = false;
  bool _secEmployment   = false;
  bool _secBank         = false;
  bool _secBocw         = false;
  bool _secFamily       = false;
  bool _secSignature    = false;

  // ── Section completion status ──
  bool _secPersonalCompleted    = false;
  bool _secEducationCompleted   = false;
  bool _secEmploymentCompleted  = false;
  bool _secBankCompleted        = false;
  bool _secBocwCompleted        = false;
  bool _secFamilyCompleted      = false;
  bool _secSignatureCompleted   = false;

  // ── Personal Info Controllers ──
  final _nameController = TextEditingController();
  final _dobController = TextEditingController();
  final _aadhaarController = TextEditingController();
  final _mobileAltController = TextEditingController();
  final _emailController = TextEditingController();
  final _fatherNameController = TextEditingController();
  final _aadhaarAddressController = TextEditingController();
  final _localAddressController = TextEditingController();
  final _identificationMarkController = TextEditingController();
  String? _photoLocalPath;
  XFile?  _photoXFile;      // for web upload
  String? _photoUrl;
  Uint8List? _photoBytes;

  bool _localSameAsPermanent = false;

  // ── Employment Controllers ──
  final _jobProfileController = TextEditingController();
  final _employerNameController = TextEditingController();
  final _employmentSiteController = TextEditingController();
  String _employmentStatus = 'Employed';

  // ── Bank Details ──
  String _bankAccountNumber = '';
  String _bankNameBranch = '';
  String _bankIfsc = '';
  bool _hasBankDetails = false;

  // ── BOCW ──
  bool _isBocwRegistered = false;
  final _bocwRegNoController = TextEditingController();
  final _bocwStateBoardController = TextEditingController();
  String? _bocwRegDate;
  String? _bocwValidUpto;

  String? _signaturePath;
  XFile?  _signatureXFile;  // for web upload
  String? _signatureUrl;
  Uint8List? _signatureBytes;

  String _gender = 'male';
  String? _category = 'OBC';
  String? _education = '12th';
  String? _maritalStatus = 'Married';

  List<WorkerFamilyMember> _familyMembers = [];

  final List<String> _genderOptions = ['male', 'female', 'other'];
  final List<String> _categoryOptions = ['General', 'OBC', 'SC', 'ST', 'EWS'];
  final List<String> _educationOptions = [
    'Not Educated',
    '10th',
    '12th',
    'Diploma',
    'Graduate',
    'Post Graduate',
    'Other',
  ];
  final List<String> _maritalStatusOptions = ['Single', 'Married', 'Divorced', 'Widowed'];
  final List<String> _employmentStatusOptions = ['Employed', 'Not Employed'];

  // Mock employer list for autocomplete
  final List<String> _employerList = [
    'Ramesh Construction Co.',
    'Raj Builders',
    'Sharma Infrastructure Ltd.',
    'National Highway Authority',
    'Singh & Sons Contractors',
    'Metro Rail Corporation',
    'Vikram Building Works',
    'Jain Constructions',
    'Agarwal Engineering',
    'Patel Projects Pvt. Ltd.',
  ];

  // Mock job profile list
  final List<String> _jobProfileList = [
    'Mason',
    'Carpenter',
    'Welder',
    'Electrician',
    'Plumber',
    'Painter',
    'Ironworker',
    'Tile Layer',
    'Crane Operator',
    'Helper/Unskilled Worker',
    'Site Supervisor',
    'Demolition Worker',
  ];

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _dobController.dispose();
    _aadhaarController.dispose();
    _mobileAltController.dispose();
    _emailController.dispose();
    _fatherNameController.dispose();
    _aadhaarAddressController.dispose();
    _localAddressController.dispose();
    _identificationMarkController.dispose();
    _jobProfileController.dispose();
    _employerNameController.dispose();
    _employmentSiteController.dispose();
    _bocwRegNoController.dispose();
    _bocwStateBoardController.dispose();
    super.dispose();
  }

  DateTime? _parseDisplayDate(String val) {
    if (val.isEmpty) return null;
    final parts = val.split('/');
    if (parts.length != 3) return null;
    final day = int.tryParse(parts[0]);
    final month = int.tryParse(parts[1]);
    final year = int.tryParse(parts[2]);
    if (day == null || month == null || year == null) return null;
    try {
      return DateTime(year, month, day);
    } catch (_) {
      return null;
    }
  }

  String _formatDisplayDate(DateTime dt) {
    final day = dt.day.toString().padLeft(2, '0');
    final month = dt.month.toString().padLeft(2, '0');
    final year = dt.year.toString();
    return "$day/$month/$year";
  }

  String? _toBackendDate(String val) {
    if (val.isEmpty) return null;
    final dt = _parseDisplayDate(val) ?? DateTime.tryParse(val.split(' ').first.split('T').first);
    if (dt == null) return null;
    final day = dt.day.toString().padLeft(2, '0');
    final month = dt.month.toString().padLeft(2, '0');
    final year = dt.year.toString();
    return "$year-$month-$day";
  }

  String? _toDisplayDate(String? val) {
    if (val == null || val.isEmpty) return null;
    final cleanVal = val.split(' ').first.split('T').first;
    final dt = _parseDisplayDate(cleanVal) ?? DateTime.tryParse(cleanVal);
    if (dt == null) return null;
    return _formatDisplayDate(dt);
  }

  Future<void> _selectDate(BuildContext context, TextEditingController ctrl) async {
    final initial = ctrl.text.isNotEmpty
        ? (_parseDisplayDate(ctrl.text) ?? DateTime.now().subtract(const Duration(days: 365 * 18)))
        : DateTime.now().subtract(const Duration(days: 365 * 18));
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(1940),
      lastDate: DateTime.now(),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: const ColorScheme.light(
            primary: Color(0xFFE65100),
            onPrimary: Colors.white,
            onSurface: Colors.black,
          ),
          textButtonTheme: TextButtonThemeData(
            style: TextButton.styleFrom(foregroundColor: const Color(0xFFE65100)),
          ),
        ),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() {
        ctrl.text = _formatDisplayDate(picked);
      });
    }
  }

  Future<void> _loadProfile() async {
    setState(() => _isLoading = true);
    final res = await _apiService.getProfile();
    if (res['success'] == false && res['message'] == 'Unauthenticated.') {
      await _apiService.clearToken();
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
      return;
    }
    setState(() {
      _isLoading = false;
      _photoXFile = null;
      _photoLocalPath = null;
      _photoBytes = null;
      _signatureXFile = null;
      _signaturePath = null;
      _signatureBytes = null;
      if (res['success'] == true && res['has_profile'] == true) {
        _hasProfile = true;
        final worker = Worker.fromJson(res['data']);
        _nameController.text = worker.fullName;
        _dobController.text = _toDisplayDate(worker.dob) ?? '';
        _aadhaarController.text = worker.aadhaarNumber ?? '';
        _mobileAltController.text = worker.mobileAlt ?? '';
        _emailController.text = worker.email ?? '';
        _fatherNameController.text = worker.fatherName ?? '';
        _aadhaarAddressController.text = worker.aadhaarAddress ?? '';
        _localAddressController.text = worker.localAddress ?? '';
        _identificationMarkController.text = worker.identificationMark ?? '';
        _jobProfileController.text = worker.jobProfile ?? '';
        _employerNameController.text = worker.employerName ?? '';
        _employmentSiteController.text = worker.employmentSite ?? '';
        _photoUrl = worker.photoUrl;
        _signatureUrl = worker.signatureUrl;

        _bankAccountNumber = worker.bankAccountNumber ?? '';
        _bankNameBranch = worker.bankNameBranch ?? '';
        _bankIfsc = worker.bankIfsc ?? '';
        _hasBankDetails = _bankAccountNumber.isNotEmpty;

          _employmentStatus = worker.employmentStatus ?? 'Employed';
        _isBocwRegistered = worker.isBocwRegistered;
        _bocwRegNoController.text = worker.bocwRegNo ?? '';
        _bocwStateBoardController.text = worker.bocwStateBoard ?? '';
        _bocwRegDate = _toDisplayDate(worker.bocwRegDate);
        _bocwValidUpto = _toDisplayDate(worker.bocwValidUpto);

        _familyMembers = List.from(worker.familyMembers);

        if (_genderOptions.contains(worker.gender.toLowerCase())) _gender = worker.gender.toLowerCase();
        if (_categoryOptions.contains(worker.category)) _category = worker.category;
        if (_educationOptions.contains(worker.education)) {
          _education = worker.education;
        } else {
          _education = '12th';
        }
        if (_maritalStatusOptions.contains(worker.maritalStatus)) {
          _maritalStatus = worker.maritalStatus;
        } else {
          _maritalStatus = 'Married';
        }

        // Set completion flags
        _secPersonalCompleted = worker.fullName.isNotEmpty && worker.dob != null;
        _secEducationCompleted = true;
        _secEmploymentCompleted = worker.jobProfile != null && worker.jobProfile!.isNotEmpty;
        _secBankCompleted = _hasBankDetails;
        _secBocwCompleted = true;
        _secFamilyCompleted = true;
        _secSignatureCompleted = worker.signatureUrl != null && worker.signatureUrl!.isNotEmpty;
      } else {
        _hasProfile = false;
        _familyMembers = [];
        _hasBankDetails = false;

        // Reset completion flags
        _secPersonalCompleted = false;
        _secEducationCompleted = false;
        _secEmploymentCompleted = false;
        _secBankCompleted = false;
        _secBocwCompleted = false;
        _secFamilyCompleted = false;
        _secSignatureCompleted = false;

        if (widget.digiLockerData != null) {
          final data = widget.digiLockerData!;
          _nameController.text = data['full_name'] ?? '';
          _dobController.text = _toDisplayDate(data['dob']) ?? data['dob'] ?? '';
          _aadhaarController.text = data['aadhaar_number'] ?? '';
          _aadhaarAddressController.text = data['aadhaar_address'] ?? '';
          if (data['gender'] != null && _genderOptions.contains(data['gender'].toString().toLowerCase())) {
            _gender = data['gender'].toString().toLowerCase();
          }
        }
      }
    });
  }

  Future<void> _pickPhoto() async {
    try {
      final XFile? image = await _picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
      if (image != null) {
        final bytes = await image.readAsBytes();
        setState(() {
          _photoXFile    = image;
          _photoLocalPath = image.path;
          _photoBytes     = bytes;
        });
      }
    } catch (_) {
      setState(() {
        _photoLocalPath = 'simulated_photo.jpg';
        _photoBytes     = null;
      });
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Photo selected!')));
    }
  }

  Future<void> _chooseSignature() async {
    try {
      final XFile? image = await _picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
      if (image != null) {
        final bytes = await image.readAsBytes();
        setState(() {
          _signatureXFile = image;
          _signaturePath  = image.path;
          _signatureBytes = bytes;
        });
      }
    } catch (_) {
      setState(() {
        _signaturePath = 'simulated_signature.png';
        _signatureBytes = null;
      });
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Signature selected!')));
    }
  }

  Widget _buildSectionSaveButton({
    required VoidCallback onPressed,
    required String label,
    required String sectionType,
  }) {
    final isCurrentSaving = _savingSection == sectionType;
    return SizedBox(
      width: double.infinity,
      height: 48,
      child: ElevatedButton.icon(
        onPressed: _savingSection != null ? null : onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFFE65100),
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          elevation: 2,
        ),
        icon: isCurrentSaving
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
              )
            : const Icon(Icons.check_circle_outline_rounded, size: 18),
        label: Text(
          isCurrentSaving ? 'Saving...' : label,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }

  Future<void> _saveSection(String sectionType) async {
    if (!_hasProfile && sectionType != 'personal') {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Please save Personal Details first to create your profile.'),
        backgroundColor: Colors.orange,
      ));
      return;
    }

    if (sectionType == 'personal') {
      if (!_personalFormKey.currentState!.validate()) return;
    } else if (sectionType == 'education') {
      if (!_educationFormKey.currentState!.validate()) return;
    } else if (sectionType == 'employment') {
      if (!_employmentFormKey.currentState!.validate()) return;
    } else if (sectionType == 'bocw') {
      if (!_bocwFormKey.currentState!.validate()) return;
    } else if (sectionType == 'bank') {
      if (!_hasBankDetails) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('Please add bank details first.'),
          backgroundColor: Colors.red,
        ));
        return;
      }
    } else if (sectionType == 'signature') {
      if (_signatureBytes == null && _signaturePath == null && (_signatureUrl == null || _signatureUrl!.isEmpty)) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('Please choose a signature image first.'),
          backgroundColor: Colors.red,
        ));
        return;
      }
    }

    setState(() => _savingSection = sectionType);

    final Map<String, String> fields = {
      'full_name': _nameController.text.trim(),
      'dob': _toBackendDate(_dobController.text.trim()) ?? _dobController.text.trim(),
      'gender': _gender,
      'aadhaar_number': _aadhaarController.text.trim(),
      'mobile_alt': _mobileAltController.text.trim(),
      'email': _emailController.text.trim(),
      'father_name': _fatherNameController.text.trim(),
      'aadhaar_address': _aadhaarAddressController.text.trim(),
      'local_address': _localSameAsPermanent ? _aadhaarAddressController.text.trim() : _localAddressController.text.trim(),
      'identification_mark': _identificationMarkController.text.trim(),
      'job_profile': _jobProfileController.text.trim(),
      'employer_name': _employerNameController.text.trim(),
      'employment_site': _employmentSiteController.text.trim(),
      'employment_status': _employmentStatus,
      'bank_account_number': _bankAccountNumber,
      'bank_name_branch': _bankNameBranch,
      'bank_ifsc': _bankIfsc,
      'photo_url': _photoUrl ?? '',
      'is_bocw_registered': _isBocwRegistered ? '1' : '0',
      'bocw_reg_no': _bocwRegNoController.text.trim(),
      'bocw_state_board': _bocwStateBoardController.text.trim(),
      'bocw_reg_date': _toBackendDate(_bocwRegDate ?? '') ?? '',
      'bocw_valid_upto': _toBackendDate(_bocwValidUpto ?? '') ?? '',
    };

    if (_category != null) fields['category'] = _category!;
    if (_education != null) fields['education'] = _education!;
    if (_maritalStatus != null) fields['marital_status'] = _maritalStatus!;

    final res = await _apiService.submitProfile(
      fields: fields,
      family: _familyMembers,
      signaturePath: _signaturePath,
      signatureXFile: _signatureXFile,
      photoPath: _photoLocalPath,
      photoXFile: _photoXFile,
      isUpdate: _hasProfile,
    );

    setState(() => _savingSection = null);

    if (res['success'] == false && res['message'] == 'Unauthenticated.') {
      await _apiService.clearToken();
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
      return;
    }

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(res['message'] ?? (res['success'] == true ? 'Section saved successfully!' : 'Error occurred.')),
        backgroundColor: res['success'] == true ? Colors.green : Colors.red,
      ));
      if (res['success'] == true) {
        setState(() {
          if (sectionType == 'personal') _secPersonalCompleted = true;
          if (sectionType == 'education') _secEducationCompleted = true;
          if (sectionType == 'employment') _secEmploymentCompleted = true;
          if (sectionType == 'bank') _secBankCompleted = true;
          if (sectionType == 'bocw') _secBocwCompleted = true;
          if (sectionType == 'family') _secFamilyCompleted = true;
          if (sectionType == 'signature') _secSignatureCompleted = true;
        });
        if (!_hasProfile) {
          setState(() {
            _hasProfile = true;
          });
          _loadProfile();
        } else {
          _loadProfile();
        }
      }
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // BANK DETAILS DIALOG
  // ─────────────────────────────────────────────────────────────────────────
  void _showBankDetailsDialog() {
    final ifscCtrl = TextEditingController(text: _bankIfsc);
    final bankNameCtrl = TextEditingController(text: _bankNameBranch);
    final accountCtrl = TextEditingController(text: _bankAccountNumber);
    final confirmCtrl = TextEditingController();
    bool isFetchingIfsc = false;
    bool ifscFetched = false;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: const Color(0xFFE65100).withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                child: const Icon(Icons.account_balance_rounded, color: Color(0xFFE65100), size: 22),
              ),
              const SizedBox(width: 12),
              const Text('Add Bank Details', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE65100), fontSize: 16)),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('IFSC Code *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: ifscCtrl,
                        textCapitalization: TextCapitalization.characters,
                        maxLength: 11,
                        decoration: InputDecoration(
                          hintText: 'e.g. SBIN0001234',
                          counterText: '',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
                        ),
                        onChanged: (_) => setModalState(() => ifscFetched = false),
                      ),
                    ),
                    const SizedBox(width: 8),
                    SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: isFetchingIfsc ? null : () async {
                          final ifsc = ifscCtrl.text.trim().toUpperCase();
                          if (ifsc.length != 11) {
                            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Enter a valid 11-character IFSC code.')));
                            return;
                          }
                          setModalState(() => isFetchingIfsc = true);
                          await Future.delayed(const Duration(seconds: 1));
                          final prefix = ifsc.substring(0, 4).toUpperCase();
                          String bankInfo = 'National Bank, Main Branch';
                          if (prefix == 'SBIN') bankInfo = 'State Bank of India, Main Branch';
                          else if (prefix == 'HDFC') bankInfo = 'HDFC Bank, City Branch';
                          else if (prefix == 'ICIC') bankInfo = 'ICICI Bank, Central Branch';
                          else if (prefix == 'PUNB') bankInfo = 'Punjab National Bank, Main Branch';
                          else if (prefix == 'BARB') bankInfo = 'Bank of Baroda, Main Branch';
                          else if (prefix == 'UTIB') bankInfo = 'Axis Bank, Main Branch';
                          else if (prefix == 'UBIN') bankInfo = 'Union Bank of India, Main Branch';
                          setModalState(() { isFetchingIfsc = false; ifscFetched = true; bankNameCtrl.text = bankInfo; });
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: ifscFetched ? Colors.green : const Color(0xFFE65100),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                        child: isFetchingIfsc
                            ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : Icon(ifscFetched ? Icons.check_rounded : Icons.search_rounded, color: Colors.white),
                      ),
                    ),
                  ],
                ),
                if (ifscFetched) ...[
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.green.shade200)),
                    child: Row(
                      children: [
                        const Icon(Icons.check_circle_rounded, color: Colors.green, size: 16),
                        const SizedBox(width: 6),
                        Expanded(child: Text(bankNameCtrl.text, style: const TextStyle(fontSize: 12, color: Colors.green, fontWeight: FontWeight.w600))),
                      ],
                    ),
                  ),
                ],
                const SizedBox(height: 16),
                const Text('Bank Name & Branch *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(
                  controller: bankNameCtrl,
                  readOnly: !ifscFetched,
                  decoration: InputDecoration(
                    hintText: 'Auto-filled after IFSC lookup',
                    filled: !ifscFetched,
                    fillColor: Colors.grey.shade100,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
                  ),
                ),
                const SizedBox(height: 16),
                const Text('Account Number *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(
                  controller: accountCtrl,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    hintText: 'Enter bank account number',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
                  ),
                ),
                const SizedBox(height: 16),
                const Text('Confirm Account Number *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(
                  controller: confirmCtrl,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    hintText: 'Re-enter account number',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.of(ctx).pop(), child: const Text('Cancel', style: TextStyle(color: Colors.grey))),
            ElevatedButton(
              onPressed: () {
                final ifsc = ifscCtrl.text.trim().toUpperCase();
                final account = accountCtrl.text.trim();
                final confirm = confirmCtrl.text.trim();
                if (ifsc.length != 11) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Enter valid IFSC.'))); return; }
                if (!ifscFetched) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Fetch bank details first.'))); return; }
                if (account.isEmpty) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Account number required.'))); return; }
                if (account != confirm) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Account numbers do not match.'), backgroundColor: Colors.red)); return; }
                setState(() { _bankIfsc = ifsc; _bankNameBranch = bankNameCtrl.text.trim(); _bankAccountNumber = account; _hasBankDetails = true; });
                Navigator.of(ctx).pop();
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Bank details saved!'), backgroundColor: Colors.green));
              },
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE65100), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
              child: const Text('Save', style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // RATION CARD PDF UPLOAD DIALOG
  // ─────────────────────────────────────────────────────────────────────────
  void _showRationCardUploadDialog() {
    bool isUploading = false;
    bool isProcessing = false;
    bool pdfSelected = false;
    String? pdfName;
    List<Map<String, dynamic>> parsedMembers = [];
    final rationNoCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: const Color(0xFFE65100).withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                child: const Icon(Icons.upload_file_rounded, color: Color(0xFFE65100), size: 22),
              ),
              const SizedBox(width: 12),
              const Expanded(child: Text('Ration Card Details', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE65100), fontSize: 15))),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Ration Card Number', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(
                  controller: rationNoCtrl,
                  decoration: InputDecoration(
                    hintText: 'Enter ration card number',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
                  ),
                ),
                const SizedBox(height: 16),
                const Text('Upload Ration Card PDF', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 4),
                const Text('Download from MyRation App and upload here. OCR will auto-fill family members.', style: TextStyle(fontSize: 11, color: Colors.grey)),
                const SizedBox(height: 8),
                GestureDetector(
                  onTap: isUploading || isProcessing ? null : () async {
                    setModalState(() { isUploading = true; pdfSelected = false; parsedMembers = []; });
                    await Future.delayed(const Duration(milliseconds: 600));
                    setModalState(() { isUploading = false; pdfSelected = true; pdfName = 'ration_card.pdf'; });
                  },
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: pdfSelected ? Colors.green.shade50 : Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: pdfSelected ? Colors.green.shade300 : Colors.grey.shade300, width: 1.5),
                    ),
                    child: isUploading
                        ? const Column(children: [CircularProgressIndicator(color: Color(0xFFE65100)), SizedBox(height: 8), Text('Selecting...', style: TextStyle(color: Colors.grey, fontSize: 12))])
                        : Column(
                            children: [
                              Icon(pdfSelected ? Icons.picture_as_pdf_rounded : Icons.upload_file_rounded, size: 36, color: pdfSelected ? Colors.green : Colors.grey.shade400),
                              const SizedBox(height: 6),
                              Text(pdfSelected ? (pdfName ?? 'ration_card.pdf') : 'Tap to select PDF', style: TextStyle(color: pdfSelected ? Colors.green.shade700 : Colors.grey.shade500, fontWeight: pdfSelected ? FontWeight.bold : FontWeight.normal, fontSize: 13)),
                              if (pdfSelected) const Text('File selected ✓', style: TextStyle(color: Colors.green, fontSize: 11)),
                            ],
                          ),
                  ),
                ),
                if (pdfSelected && parsedMembers.isEmpty && !isProcessing) ...[
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () async {
                        setModalState(() => isProcessing = true);
                        await Future.delayed(const Duration(seconds: 2));
                        setModalState(() {
                          isProcessing = false;
                          parsedMembers = [
                            {'name': 'Sunita Devi', 'relation': 'Spouse', 'gender': 'female', 'age': 34},
                            {'name': 'Aman Kumar', 'relation': 'Son', 'gender': 'male', 'age': 12},
                            {'name': 'Priya Kumari', 'relation': 'Daughter', 'gender': 'female', 'age': 9},
                          ];
                        });
                      },
                      style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1565C0), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
                      icon: const Icon(Icons.auto_awesome_rounded, color: Colors.white, size: 18),
                      label: const Text('Extract Data from PDF (OCR)', style: TextStyle(color: Colors.white)),
                    ),
                  ),
                ],
                if (isProcessing) const Padding(padding: EdgeInsets.only(top: 16), child: Center(child: Column(children: [CircularProgressIndicator(color: Color(0xFFE65100)), SizedBox(height: 8), Text('Reading PDF via OCR...', style: TextStyle(color: Colors.grey, fontSize: 12))]))),
                if (parsedMembers.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.green.shade200)),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(children: [const Icon(Icons.check_circle_rounded, color: Colors.green, size: 16), const SizedBox(width: 6), Text('${parsedMembers.length} members found', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.green, fontSize: 13))]),
                        const SizedBox(height: 8),
                        ...parsedMembers.map((m) => Padding(
                          padding: const EdgeInsets.symmetric(vertical: 3),
                          child: Row(children: [
                            Icon((m['gender'] as String).toLowerCase() == 'female' ? Icons.female_rounded : Icons.male_rounded, size: 14, color: Colors.grey.shade600),
                            const SizedBox(width: 4),
                            Text('${m['name']} (${m['relation']}, ${m['age']} yrs)', style: const TextStyle(fontSize: 12)),
                          ]),
                        )),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.of(ctx).pop(), child: const Text('Cancel', style: TextStyle(color: Colors.grey))),
            ElevatedButton(
              onPressed: parsedMembers.isEmpty ? null : () {
                setState(() {
                  _familyMembers = parsedMembers.map((m) => WorkerFamilyMember(name: m['name'] as String, relation: m['relation'] as String, gender: m['gender'] as String, age: m['age'] as int)).toList();
                });
                Navigator.of(ctx).pop();
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Family members added from Ration Card!'), backgroundColor: Colors.green));
              },
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE65100), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
              child: const Text('Save Members', style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      ),
    );
  }

  void _showAddFamilyDialog() {
    final nameCtrl = TextEditingController();
    final ageCtrl = TextEditingController();
    String rel = 'Spouse';
    String gen = 'male';
    final relations = ['Spouse', 'Son', 'Daughter', 'Father', 'Mother', 'Brother', 'Sister'];

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('Add Family Member', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE65100))),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Name *', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 6),
                TextField(controller: nameCtrl, decoration: InputDecoration(hintText: 'Enter full name', border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)), focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)))),
                const SizedBox(height: 16),
                const Text('Relation *', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(value: rel, items: relations.map((r) => DropdownMenuItem(value: r, child: Text(r))).toList(), onChanged: (v) { if (v != null) setModalState(() => rel = v); }, decoration: InputDecoration(border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)))),
                const SizedBox(height: 16),
                const Text('Gender *', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: gen,
                  items: const [DropdownMenuItem(value: 'male', child: Text('Male')), DropdownMenuItem(value: 'female', child: Text('Female')), DropdownMenuItem(value: 'other', child: Text('Other'))],
                  onChanged: (v) { if (v != null) setModalState(() => gen = v); },
                  decoration: InputDecoration(border: OutlineInputBorder(borderRadius: BorderRadius.circular(8))),
                ),
                const SizedBox(height: 16),
                const Text('Age *', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 6),
                TextField(controller: ageCtrl, keyboardType: TextInputType.number, decoration: InputDecoration(hintText: 'Enter age', border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)), focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)))),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.of(ctx).pop(), child: const Text('Cancel', style: TextStyle(color: Colors.grey))),
            ElevatedButton(
              onPressed: () {
                final name = nameCtrl.text.trim();
                final age = int.tryParse(ageCtrl.text.trim());
                if (name.isEmpty || age == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enter valid Name and Age.'))); return; }
                setState(() => _familyMembers.add(WorkerFamilyMember(name: name, relation: rel, gender: gen, age: age)));
                Navigator.of(ctx).pop();
              },
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE65100)),
              child: const Text('Add', style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      ),
    );
  }

  // BUILD
  // ─────────────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: Text(_hasProfile ? 'My Profile' : 'Profile Registration', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFE65100),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)))
          : SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ── Section 1: Personal Details ──
                  _buildCollapsibleSection(
                    title: 'Personal Details',
                    icon: Icons.person_rounded,
                    isOpen: _secPersonal,
                    isCompleted: _secPersonalCompleted,
                    onToggle: () => setState(() => _secPersonal = !_secPersonal),
                    child: _buildPersonalDetailsCard(),
                  ),

                  // ── Section 2: Education Details ──
                  _buildCollapsibleSection(
                    title: 'Education Details',
                    icon: Icons.school_rounded,
                    isOpen: _secEducation,
                    isCompleted: _secEducationCompleted,
                    onToggle: () => setState(() => _secEducation = !_secEducation),
                    child: _buildEducationCard(),
                  ),

                  // ── Section 3: Employment Details ──
                  _buildCollapsibleSection(
                    title: 'Employment Details',
                    icon: Icons.work_rounded,
                    isOpen: _secEmployment,
                    isCompleted: _secEmploymentCompleted,
                    onToggle: () => setState(() => _secEmployment = !_secEmployment),
                    child: _buildEmploymentCard(),
                  ),

                  // ── Section 4: Bank Details ──
                  _buildCollapsibleSection(
                    title: 'Bank Details',
                    icon: Icons.account_balance_rounded,
                    isOpen: _secBank,
                    isCompleted: _secBankCompleted,
                    onToggle: () => setState(() => _secBank = !_secBank),
                    child: _buildBankDetailsCard(),
                  ),

                  // ── Section 5: BOCW Registration ──
                  _buildCollapsibleSection(
                    title: 'BOCW Registration',
                    icon: Icons.verified_rounded,
                    isOpen: _secBocw,
                    isCompleted: _secBocwCompleted,
                    onToggle: () => setState(() => _secBocw = !_secBocw),
                    child: _buildBocwCard(),
                  ),

                  // ── Section 6: PDS Data (Family Members) ──
                  _buildCollapsibleSection(
                    title: 'PDS Data (Family Members)',
                    icon: Icons.people_rounded,
                    isOpen: _secFamily,
                    isCompleted: _secFamilyCompleted,
                    onToggle: () => setState(() => _secFamily = !_secFamily),
                    child: _buildFamilyMembersSection(),
                  ),

                  // ── Section 7: Signature Upload ──
                  _buildCollapsibleSection(
                    title: 'Signature Upload',
                    icon: Icons.draw_rounded,
                    isOpen: _secSignature,
                    isCompleted: _secSignatureCompleted,
                    onToggle: () => setState(() => _secSignature = !_secSignature),
                    child: _buildSignatureCard(),
                  ),

                  const SizedBox(height: 24),
                ],
              ),
            ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // COLLAPSIBLE SECTION
  // ─────────────────────────────────────────────────────────────────────────
  Widget _buildCollapsibleSection({
    required String title,
    required IconData icon,
    required bool isOpen,
    required bool isCompleted,
    required VoidCallback onToggle,
    required Widget child,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Card(
        elevation: isOpen ? 3 : 1,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: BorderSide(
            color: isOpen ? const Color(0xFFE65100).withOpacity(0.4) : Colors.transparent,
            width: 1.2,
          ),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          children: [
            // ── Header (tap to toggle) ──
            InkWell(
              onTap: onToggle,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(7),
                      decoration: BoxDecoration(
                        color: isOpen
                            ? const Color(0xFFE65100).withOpacity(0.15)
                            : Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(icon,
                          color: isOpen ? const Color(0xFFE65100) : Colors.grey.shade500,
                          size: 18),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Row(
                        children: [
                          Text(
                            title,
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              color: isOpen ? const Color(0xFF1F2937) : Colors.grey.shade600,
                            ),
                          ),
                          if (isCompleted) ...[
                            const SizedBox(width: 8),
                            const Icon(
                              Icons.check_circle_rounded,
                              color: Colors.green,
                              size: 16,
                            ),
                          ],
                        ],
                      ),
                    ),
                    AnimatedRotation(
                      turns: isOpen ? 0.5 : 0,
                      duration: const Duration(milliseconds: 250),
                      child: Icon(
                        Icons.keyboard_arrow_down_rounded,
                        color: isOpen ? const Color(0xFFE65100) : Colors.grey.shade400,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            // ── Animated body ──
            AnimatedCrossFade(
              firstChild: const SizedBox.shrink(),
              secondChild: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Divider(
                    height: 1,
                    thickness: 1,
                    color: const Color(0xFFE65100).withOpacity(0.12),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(0, 0, 0, 4),
                    child: child,
                  ),
                ],
              ),
              crossFadeState:
                  isOpen ? CrossFadeState.showSecond : CrossFadeState.showFirst,
              duration: const Duration(milliseconds: 280),
            ),
          ],
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // PERSONAL DETAILS CARD
  // ─────────────────────────────────────────────────────────────────────────
  Widget _buildPersonalDetailsCard() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      child: Form(
        key: _personalFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Photo
            Center(
              child: GestureDetector(
                onTap: _pickPhoto,
                child: Stack(
                  alignment: Alignment.bottomRight,
                  children: [
                    CircleAvatar(
                      radius: 50,
                      backgroundColor: const Color(0xFFFFE0B2),
                      backgroundImage: _photoBytes != null
                          ? MemoryImage(_photoBytes!) as ImageProvider
                          : (_photoLocalPath != null && _photoLocalPath!.contains('simulated')
                              ? const NetworkImage('https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&fit=crop')
                              : (_photoUrl != null && _photoUrl!.isNotEmpty
                                  ? CachedNetworkImageProvider(_apiService.resolveImageUrl(_photoUrl))
                                  : null)),
                      child: (_photoBytes == null && _photoLocalPath == null && (_photoUrl == null || _photoUrl!.isEmpty))
                          ? const Icon(Icons.person_rounded, size: 54, color: Color(0xFFE65100))
                          : null,
                    ),
                    Container(
                      padding: const EdgeInsets.all(5),
                      decoration: const BoxDecoration(color: Color(0xFFE65100), shape: BoxShape.circle),
                      child: const Icon(Icons.camera_alt_rounded, color: Colors.white, size: 16),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 4),
            const Center(child: Text('Tap to upload photo', style: TextStyle(fontSize: 11, color: Colors.grey))),
            const SizedBox(height: 16),

            _buildLabel('Full Name *'),
            _buildTextField(controller: _nameController, hint: 'Enter full name', validator: (v) => (v == null || v.isEmpty) ? 'Full Name is required' : null),

            _buildLabel("Father's Name / Husband's Name *"),
            _buildTextField(controller: _fatherNameController, hint: "Enter father's or husband's name", validator: (v) => (v == null || v.isEmpty) ? 'Required' : null),

            _buildLabel('Date of Birth *'),
            GestureDetector(
              onTap: () => _selectDate(context, _dobController),
              child: AbsorbPointer(child: _buildTextField(controller: _dobController, hint: 'DD/MM/YYYY', validator: (v) {
                if (v == null || v.isEmpty) return 'Date of birth required';
                if (_parseDisplayDate(v) == null) return 'Enter valid date (DD/MM/YYYY)';
                return null;
              })),
            ),

            _buildLabel('Gender *'),
            _buildDropdown<String>(value: _gender, items: _genderOptions, onChanged: (val) { if (val != null) setState(() => _gender = val); }),

            _buildLabel('Permanent Address (as per Aadhaar) *'),
            _buildTextField(controller: _aadhaarAddressController, hint: 'Enter address as in Aadhaar', maxLines: 2, validator: (v) => (v == null || v.isEmpty) ? 'Address required' : null),

            // Same as permanent checkbox
            Row(
              children: [
                Checkbox(
                  value: _localSameAsPermanent,
                  activeColor: const Color(0xFFE65100),
                  onChanged: (val) {
                    setState(() {
                      _localSameAsPermanent = val ?? false;
                      if (_localSameAsPermanent) {
                        _localAddressController.text = _aadhaarAddressController.text;
                      }
                    });
                  },
                ),
                const Text('Local address same as permanent address', style: TextStyle(fontSize: 13, color: Color(0xFF374151))),
              ],
            ),

            if (!_localSameAsPermanent) ...[
              _buildLabel('Local / Current Address *'),
              _buildTextField(controller: _localAddressController, hint: 'Enter current residential address', maxLines: 2, validator: (v) => (!_localSameAsPermanent && (v == null || v.isEmpty)) ? 'Local address required' : null),
            ],

            _buildLabel('Identification Mark *'),
            _buildTextField(controller: _identificationMarkController, hint: 'e.g. mole on right cheek', validator: (v) => (v == null || v.isEmpty) ? 'Required' : null),

            _buildLabel('Mobile Number *'),
            _buildTextField(controller: _mobileAltController, hint: 'Primary contact number', maxLength: 10, keyboardType: TextInputType.phone, validator: (v) => (v == null || v.length != 10) ? 'Enter valid 10-digit number' : null),

            _buildLabel('Email Address'),
            _buildTextField(controller: _emailController, hint: 'email@domain.com', keyboardType: TextInputType.emailAddress),
            const SizedBox(height: 20),
            _buildSectionSaveButton(
              onPressed: () => _saveSection('personal'),
              label: 'Save Personal Details',
              sectionType: 'personal',
            ),
          ],
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // EDUCATION CARD
  // ─────────────────────────────────────────────────────────────────────────
  Widget _buildEducationCard() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      child: Form(
        key: _educationFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildLabel('Education Status'),
            _buildDropdown<String>(value: _education, items: _educationOptions, onChanged: (val) => setState(() => _education = val)),
            _buildLabel('Social Category'),
            _buildDropdown<String>(value: _category, items: _categoryOptions, onChanged: (val) => setState(() => _category = val)),
            _buildLabel('Marital Status'),
            _buildDropdown<String>(value: _maritalStatus, items: _maritalStatusOptions, onChanged: (val) => setState(() => _maritalStatus = val)),
            const SizedBox(height: 20),
            _buildSectionSaveButton(
              onPressed: () => _saveSection('education'),
              label: 'Save Education Details',
              sectionType: 'education',
            ),
          ],
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // EMPLOYMENT CARD
  // ─────────────────────────────────────────────────────────────────────────
  Widget _buildEmploymentCard() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      child: Form(
        key: _employmentFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildLabel('Current Employment Status *'),
            _buildDropdown<String>(value: _employmentStatus, items: _employmentStatusOptions, onChanged: (val) { if (val != null) setState(() => _employmentStatus = val); }),

            if (_employmentStatus == 'Employed') ...[
              _buildLabel('Name of Employer'),
              Autocomplete<String>(
                initialValue: TextEditingValue(text: _employerNameController.text),
                optionsBuilder: (TextEditingValue textEditingValue) {
                  if (textEditingValue.text.isEmpty) return const Iterable<String>.empty();
                  return _employerList.where((e) => e.toLowerCase().contains(textEditingValue.text.toLowerCase()));
                },
                onSelected: (String selected) {
                  setState(() {
                    _employerNameController.text = selected;
                    // Auto-fill site for known employers (mock)
                    if (selected.contains('National Highway')) _employmentSiteController.text = 'NH-48, Jaipur-Delhi Highway';
                    else if (selected.contains('Metro Rail')) _employmentSiteController.text = 'Metro Station, Phase-2, Jaipur';
                    else _employmentSiteController.clear();
                  });
                },
                fieldViewBuilder: (context, ctrl, focusNode, onSubmitted) {
                  ctrl.text = _employerNameController.text;
                  return TextField(
                    controller: ctrl,
                    focusNode: focusNode,
                    onChanged: (v) => _employerNameController.text = v,
                    decoration: InputDecoration(
                      hintText: 'Type to search employer...',
                      contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
                      suffixIcon: const Icon(Icons.search_rounded, color: Colors.grey),
                    ),
                  );
                },
                optionsViewBuilder: (context, onSelected, options) {
                  return Align(
                    alignment: Alignment.topLeft,
                    child: Material(
                      elevation: 4,
                      borderRadius: BorderRadius.circular(8),
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(maxHeight: 200),
                        child: ListView.builder(
                          shrinkWrap: true,
                          itemCount: options.length,
                          itemBuilder: (ctx, i) {
                            final opt = options.elementAt(i);
                            return ListTile(
                              leading: const Icon(Icons.business_rounded, color: Color(0xFFE65100), size: 18),
                              title: Text(opt, style: const TextStyle(fontSize: 13)),
                              onTap: () => onSelected(opt),
                            );
                          },
                        ),
                      ),
                    ),
                  );
                },
              ),

              _buildLabel('Employment Site Address'),
              _buildTextField(controller: _employmentSiteController, hint: 'Job site location (auto-filled if employer selected)'),

              _buildLabel('Job Profile (Trade/Occupation) *'),
              Autocomplete<String>(
                initialValue: TextEditingValue(text: _jobProfileController.text),
                optionsBuilder: (TextEditingValue textEditingValue) {
                  if (textEditingValue.text.isEmpty) return _jobProfileList;
                  return _jobProfileList.where((e) => e.toLowerCase().contains(textEditingValue.text.toLowerCase()));
                },
                onSelected: (String selected) => setState(() => _jobProfileController.text = selected),
                fieldViewBuilder: (context, ctrl, focusNode, onSubmitted) {
                  ctrl.text = _jobProfileController.text;
                  return TextField(
                    controller: ctrl,
                    focusNode: focusNode,
                    onChanged: (v) => _jobProfileController.text = v,
                    decoration: InputDecoration(
                      hintText: 'Select or type trade/occupation...',
                      contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
                      suffixIcon: const Icon(Icons.arrow_drop_down_rounded, color: Colors.grey),
                    ),
                  );
                },
                optionsViewBuilder: (context, onSelected, options) {
                  return Align(
                    alignment: Alignment.topLeft,
                    child: Material(
                      elevation: 4,
                      borderRadius: BorderRadius.circular(8),
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(maxHeight: 200),
                        child: ListView.builder(
                          shrinkWrap: true,
                          itemCount: options.length,
                          itemBuilder: (ctx, i) {
                            final opt = options.elementAt(i);
                            return ListTile(
                              leading: const Icon(Icons.construction_rounded, color: Color(0xFFE65100), size: 18),
                              title: Text(opt, style: const TextStyle(fontSize: 13)),
                              onTap: () => onSelected(opt),
                            );
                          },
                        ),
                      ),
                    ),
                  );
                },
              ),
            ],
            const SizedBox(height: 20),
            _buildSectionSaveButton(
              onPressed: () => _saveSection('employment'),
              label: 'Save Employment Details',
              sectionType: 'employment',
            ),
          ],
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // BANK DETAILS CARD
  // ─────────────────────────────────────────────────────────────────────────
  Widget _buildBankDetailsCard() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
            if (!_hasBankDetails)
              Center(
                child: Column(children: [
                  Icon(Icons.account_balance_outlined, size: 44, color: Colors.grey.shade300),
                  const SizedBox(height: 8),
                  Text('No bank details added yet.', style: TextStyle(color: Colors.grey.shade500)),
                  const SizedBox(height: 12),
                ]),
              )
            else ...[
              _buildInfoRow('IFSC Code', _bankIfsc),
              _buildInfoRow('Bank Name & Branch', _bankNameBranch),
              _buildInfoRow('Account Number', '*' * (_bankAccountNumber.length > 4 ? _bankAccountNumber.length - 4 : 0) + _bankAccountNumber.substring(_bankAccountNumber.length > 4 ? _bankAccountNumber.length - 4 : 0)),
              const SizedBox(height: 8),
            ],
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: _showBankDetailsDialog,
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: Color(0xFFE65100)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  padding: const EdgeInsets.symmetric(vertical: 13),
                ),
                icon: Icon(_hasBankDetails ? Icons.edit_rounded : Icons.add_rounded, color: const Color(0xFFE65100)),
                label: Text(_hasBankDetails ? 'Edit Bank Details' : 'Add Bank Details', style: const TextStyle(color: Color(0xFFE65100), fontWeight: FontWeight.bold)),
              ),
            ),
            if (_hasBankDetails) ...[
              const SizedBox(height: 16),
              _buildSectionSaveButton(
                onPressed: () => _saveSection('bank'),
                label: 'Save Bank Details',
                sectionType: 'bank',
              ),
            ],
          ],
        ),
      );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // BOCW CARD
  // ─────────────────────────────────────────────────────────────────────────
  Widget _buildBocwCard() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      child: Form(
        key: _bocwFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Checkbox(
                  value: _isBocwRegistered,
                  activeColor: const Color(0xFFE65100),
                  onChanged: (val) => setState(() => _isBocwRegistered = val ?? false),
                ),
                const Expanded(
                  child: Text(
                    'Are you registered under BOCW (Building & Other Construction Workers)?',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w500, color: Color(0xFF374151)),
                  ),
                ),
              ],
            ),
            if (_isBocwRegistered) ...[
              const Divider(),
              const SizedBox(height: 8),
              _buildLabel('BOCW Registration Number *'),
              _buildTextField(controller: _bocwRegNoController, hint: 'Enter BOCW registration number', validator: (v) => (_isBocwRegistered && (v == null || v.isEmpty)) ? 'BOCW Reg. No. is required' : null),

              _buildLabel('State Board Name *'),
              _buildTextField(controller: _bocwStateBoardController, hint: 'e.g. Rajasthan Building & Other Construction Workers', validator: (v) => (_isBocwRegistered && (v == null || v.isEmpty)) ? 'State Board name is required' : null),

              _buildLabel('Registration Date'),
              GestureDetector(
                onTap: () async {
                  final initial = _bocwRegDate != null ? (_parseDisplayDate(_bocwRegDate!) ?? DateTime.now()) : DateTime.now();
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: initial,
                    firstDate: DateTime(2000),
                    lastDate: DateTime.now(),
                    builder: (ctx, child) => Theme(data: Theme.of(ctx).copyWith(colorScheme: const ColorScheme.light(primary: Color(0xFFE65100), onPrimary: Colors.white)), child: child!),
                  );
                  if (picked != null) setState(() => _bocwRegDate = _formatDisplayDate(picked));
                },
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                  decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade300), borderRadius: BorderRadius.circular(8)),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today_rounded, size: 16, color: Colors.grey),
                      const SizedBox(width: 8),
                      Text(_bocwRegDate ?? 'Select date', style: TextStyle(color: _bocwRegDate != null ? Colors.black87 : Colors.grey, fontSize: 14)),
                    ],
                  ),
                ),
              ),

              _buildLabel('Valid Upto'),
              GestureDetector(
                onTap: () async {
                  final initial = _bocwValidUpto != null ? (_parseDisplayDate(_bocwValidUpto!) ?? DateTime.now().add(const Duration(days: 365))) : DateTime.now().add(const Duration(days: 365));
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: initial,
                    firstDate: DateTime.now().subtract(const Duration(days: 365)),
                    lastDate: DateTime(2040),
                    builder: (ctx, child) => Theme(data: Theme.of(ctx).copyWith(colorScheme: const ColorScheme.light(primary: Color(0xFFE65100), onPrimary: Colors.white)), child: child!),
                  );
                  if (picked != null) setState(() => _bocwValidUpto = _formatDisplayDate(picked));
                },
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                  decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade300), borderRadius: BorderRadius.circular(8)),
                  child: Row(
                    children: [
                      const Icon(Icons.event_rounded, size: 16, color: Colors.grey),
                      const SizedBox(width: 8),
                      Text(_bocwValidUpto ?? 'Select date', style: TextStyle(color: _bocwValidUpto != null ? Colors.black87 : Colors.grey, fontSize: 14)),
                    ],
                  ),
                ),
              ),
            ],
            const SizedBox(height: 20),
            _buildSectionSaveButton(
              onPressed: () => _saveSection('bocw'),
              label: 'Save BOCW Details',
              sectionType: 'bocw',
            ),
          ],
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // FAMILY MEMBERS SECTION
  // ─────────────────────────────────────────────────────────────────────────
  Widget _buildFamilyMembersSection() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      child: Column(
        children: [
            if (_familyMembers.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 16),
                child: Center(child: Column(children: [Icon(Icons.family_restroom_rounded, size: 44, color: Colors.grey.shade300), const SizedBox(height: 8), Text('No family members added yet.', style: TextStyle(color: Colors.grey.shade500))])),
              )
            else
              ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: _familyMembers.length,
                separatorBuilder: (_, __) => const Divider(),
                itemBuilder: (context, index) {
                  final member = _familyMembers[index];
                  return ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: CircleAvatar(
                      backgroundColor: const Color(0xFFFFE0B2),
                      child: Icon(member.gender.toLowerCase() == 'female' ? Icons.female_rounded : Icons.male_rounded, color: const Color(0xFFE65100)),
                    ),
                    title: Text(member.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Text('${member.relation} | ${member.gender.toUpperCase()} | Age: ${member.age} yrs'),
                    trailing: IconButton(
                      icon: const Icon(Icons.remove_circle_outline_rounded, color: Colors.red),
                      onPressed: () => setState(() => _familyMembers.removeAt(index)),
                    ),
                  );
                },
              ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _showRationCardUploadDialog,
                    style: OutlinedButton.styleFrom(side: const BorderSide(color: Color(0xFF1565C0)), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)), padding: const EdgeInsets.symmetric(vertical: 12)),
                    icon: const Icon(Icons.upload_file_rounded, color: Color(0xFF1565C0), size: 18),
                    label: const Text('Upload Ration Card', style: TextStyle(color: Color(0xFF1565C0), fontSize: 12)),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _showAddFamilyDialog,
                    style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE65100), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)), padding: const EdgeInsets.symmetric(vertical: 12)),
                    icon: const Icon(Icons.person_add_rounded, size: 18, color: Colors.white),
                    label: const Text('Add New Member', style: TextStyle(color: Colors.white, fontSize: 12)),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _buildSectionSaveButton(
              onPressed: () => _saveSection('family'),
              label: 'Save Family Details',
              sectionType: 'family',
            ),
          ],
        ),
      );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // SIGNATURE CARD
  // ─────────────────────────────────────────────────────────────────────────
  Widget _buildSignatureCard() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      child: Column(
        children: [
            Container(
              height: 110,
              width: double.infinity,
              decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade300), borderRadius: BorderRadius.circular(8), color: Colors.grey.shade50),
              child: _signatureBytes != null
                  ? Image.memory(_signatureBytes!, fit: BoxFit.contain)
                  : (_signaturePath != null && _signaturePath!.contains('simulated'))
                      ? Image.network('https://upload.wikimedia.org/wikipedia/commons/3/3a/Jon_Foreman_Signature.png', fit: BoxFit.contain)
                      : (_signatureUrl != null && _signatureUrl!.isNotEmpty)
                          ? CachedNetworkImage(imageUrl: _apiService.resolveImageUrl(_signatureUrl!), fit: BoxFit.contain, placeholder: (c, u) => const Center(child: CircularProgressIndicator()), errorWidget: (c, u, e) => const Center(child: Icon(Icons.broken_image_rounded)))
                          : Center(child: Text('No signature uploaded', style: TextStyle(color: Colors.grey.shade500))),
            ),
            const SizedBox(height: 12),
            ElevatedButton.icon(
              onPressed: _chooseSignature,
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE65100), foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
              icon: const Icon(Icons.cloud_upload_rounded),
              label: const Text('Choose Signature Image'),
            ),
            const SizedBox(height: 16),
            _buildSectionSaveButton(
              onPressed: () => _saveSection('signature'),
              label: 'Save Signature',
              sectionType: 'signature',
            ),
          ],
        ),
      );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 140, child: Text(label, style: TextStyle(fontWeight: FontWeight.w600, color: Colors.grey.shade600, fontSize: 13))),
          Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w500, color: Color(0xFF1F2937), fontSize: 13))),
        ],
      ),
    );
  }

  Widget _buildLabel(String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8, top: 14),
      child: Text(label, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.grey.shade800)),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String hint,
    int? maxLength,
    int? maxLines,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      maxLength: maxLength,
      maxLines: maxLines ?? 1,
      keyboardType: keyboardType,
      validator: validator,
      style: const TextStyle(fontSize: 14),
      decoration: InputDecoration(
        hintText: hint,
        counterText: '',
        contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
        errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Colors.red)),
        focusedErrorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Colors.red, width: 1.5)),
      ),
    );
  }

  Widget _buildDropdown<T>({
    required T? value,
    required List<T> items,
    required ValueChanged<T?>? onChanged,
  }) {
    return DropdownButtonFormField<T>(
      value: value,
      onChanged: onChanged,
      style: const TextStyle(fontSize: 14, color: Colors.black87),
      items: items.map((i) {
        String display = i.toString();
        if (display == 'male') display = 'Male';
        if (display == 'female') display = 'Female';
        if (display == 'other') display = 'Other';
        return DropdownMenuItem(value: i, child: Text(display));
      }).toList(),
      decoration: InputDecoration(
        contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
      ),
    );
  }
}
