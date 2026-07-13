import 'package:flutter/material.dart';
import 'dart:typed_data';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:file_picker/file_picker.dart';
import '../services/api_service.dart';
import '../models/models.dart';
import 'dashboard_screen.dart';
import 'login_screen.dart';

class ProfileScreen extends StatefulWidget {
  final Map<String, dynamic>? digiLockerData;
  final bool isFirstTime;
  final bool skipWizard; // For admin-registered beneficiaries who already have basic data
  const ProfileScreen({super.key, this.digiLockerData, this.isFirstTime = false, this.skipWizard = false});

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
  Map<String, dynamic>? _audit;  // Verification/approval audit trail

  // ── Collapsible section state ──
  bool _secPersonal     = false;   // closed by default
  bool _secEducation    = false;
  bool _secEmployment   = false;
  bool _secBank         = false;
  bool _secBocw         = false;
  bool _secFamily       = false;
  bool _secSignature    = false;

  // ── Section editing state ──
  bool _editPersonal = false;
  bool _editEducation = false;
  bool _editEmployment = false;
  bool _editBank = false;
  bool _editBocw = false;
  bool _editFamily = false;
  bool _editSignature = false;

  bool get _canEditPersonal => !_hasProfile || _editPersonal;
  bool get _canEditEducation => !_hasProfile || _editEducation;
  bool get _canEditEmployment => !_hasProfile || _editEmployment;
  bool get _canEditBank => !_hasProfile || _editBank;
  bool get _canEditBocw => !_hasProfile || _editBocw;
  bool get _canEditFamily => !_hasProfile || _editFamily;
  bool get _canEditSignature => !_hasProfile || _editSignature;

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
  final _businessNameController = TextEditingController();
  final _businessAddressController = TextEditingController();
  final _businessCategoryController = TextEditingController();
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
  String? _education = 'Post Matric (11-12)';
  String? _educationStatus = 'Completed';
  String? _maritalStatus = 'Never Married';

  List<WorkerFamilyMember> _familyMembers = [];

  final List<String> _genderOptions = ['male', 'female', 'other'];
  final List<String> _categoryOptions = ['General', 'OBC', 'SC', 'ST', 'EWS'];
  final List<String> _educationOptions = [
    'Primary(1-5)',
    'Middle (6-8)',
    'Matric (9-10)',
    'Post Matric (11-12)',
    'Diploma',
    'Graduation',
    'Post Graduation',
    'Ph.D. or Equivalant',
    'Post-Doctoral Research',
    'Professional Degree',
    'ITI Course',
    'Vocational Course',
  ];
  final List<String> _educationStatusOptions = [
    'Appearing (Final Year)',
    'Completed',
    'Pursuing',
  ];
  final List<String> _maritalStatusOptions = ['Never Married', 'Married', 'Divorced', 'Separated', 'Widowed'];
  final List<String> _employmentStatusOptions = ['Employed', 'Self-Employed', 'Not Employed', 'Other'];

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

  // ── Wizard state variables ──
  int _wizardStep = 1;
  Uint8List? _wizardPdfBytes;
  String? _wizardPdfName;
  bool _isWizardProcessing = false;

  String? _wizardExtractedRationNo;
  String? _wizardExtractedAddress;
  String? _wizardExtractedMobile;
  List<Map<String, dynamic>> _wizardExtractedMembers = [];
  int? _selectedApplicantIndex;

  final _wizardRationNoController = TextEditingController();
  final _wizardAddressController = TextEditingController();
  final _wizardMobileController = TextEditingController();
  final _wizardFormKey = GlobalKey<FormState>();

  // Trades dropdown state
  List<Trade> _availableTrades = [];
  bool _isLoadingTrades = false;
  Trade? _selectedTrade;
  bool _isOtherTradeSelected = false;
  final _customTradeController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadProfile();
    _fetchTrades();
  }

  Future<void> _fetchTrades() async {
    setState(() => _isLoadingTrades = true);
    try {
      final list = await _apiService.getTrades();
      debugPrint("Fetched Trades successfully: ${list.length} items found");
      setState(() {
        _availableTrades = list;
        _isLoadingTrades = false;
      });
    } catch (e) {
      debugPrint("Error fetching trades from API: $e");
      setState(() => _isLoadingTrades = false);
    }
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
    _businessNameController.dispose();
    _businessAddressController.dispose();
    _businessCategoryController.dispose();
    _bocwRegNoController.dispose();
    _bocwStateBoardController.dispose();
    _wizardRationNoController.dispose();
    _wizardAddressController.dispose();
    _wizardMobileController.dispose();
    _customTradeController.dispose();
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
        _audit = res['audit'] as Map<String, dynamic>?;
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
        _businessNameController.text = worker.businessName ?? '';
        _businessAddressController.text = worker.businessAddress ?? '';
        _businessCategoryController.text = worker.businessCategory ?? '';
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
          _education = 'Post Matric (11-12)';
        }
        if (_educationStatusOptions.contains(worker.educationStatus)) {
          _educationStatus = worker.educationStatus;
        } else {
          _educationStatus = 'Completed';
        }
        if (_maritalStatusOptions.contains(worker.maritalStatus)) {
          _maritalStatus = worker.maritalStatus;
        } else {
          _maritalStatus = 'Never Married';
        }

        // Set completion flags based on actual data
        _secPersonalCompleted = worker.fullName.isNotEmpty && worker.dob.isNotEmpty && (worker.aadhaarAddress?.isNotEmpty ?? false);
        _secEducationCompleted = worker.education != null && worker.education!.isNotEmpty && worker.educationStatus != null && worker.educationStatus!.isNotEmpty;
        if (_employmentStatus == 'Employed') {
          _secEmploymentCompleted = worker.jobProfile != null && worker.jobProfile!.isNotEmpty;
        } else if (_employmentStatus == 'Self-Employed') {
          _secEmploymentCompleted = worker.businessName != null && worker.businessName!.isNotEmpty;
        } else {
          _secEmploymentCompleted = _employmentStatus.isNotEmpty;
        }
        _secBankCompleted = _hasBankDetails;
        _secBocwCompleted = !worker.isBocwRegistered || (worker.bocwRegNo != null && worker.bocwRegNo!.isNotEmpty);
        _secFamilyCompleted = _familyMembers.isNotEmpty;
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

  Future<ImageSource?> _showImageSourceSheet() async {
    return await showModalBottomSheet<ImageSource>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt_rounded, color: Color(0xFFE65100)),
              title: const Text('Take a Photo (Camera)'),
              onTap: () => Navigator.of(context).pop(ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_rounded, color: Color(0xFFE65100)),
              title: const Text('Choose from Gallery'),
              onTap: () => Navigator.of(context).pop(ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickPhoto() async {
    final source = await _showImageSourceSheet();
    if (source == null) return;
    try {
      final XFile? image = await _picker.pickImage(source: source, imageQuality: 80);
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
    final source = await _showImageSourceSheet();
    if (source == null) return;
    try {
      final XFile? image = await _picker.pickImage(source: source, imageQuality: 80);
      if (image != null) {
        final bytes = await image.readAsBytes();
        // Minimum signature size: 5 KB
        if (bytes.lengthInBytes < 5 * 1024) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
              content: Text('Signature image is too small (minimum 5 KB). Please select a clearer image.'),
              backgroundColor: Colors.orange,
            ));
          }
          return;
        }
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

  Widget _buildSectionHeaderActions({
    required bool isEditing,
    required VoidCallback onEditToggle,
    required VoidCallback onCancel,
  }) {
    if (!_hasProfile) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.end,
        children: [
          if (isEditing) ...[
            OutlinedButton.icon(
              onPressed: onCancel,
              style: OutlinedButton.styleFrom(
                side: BorderSide(color: Colors.grey.shade400),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              icon: const Icon(Icons.close_rounded, size: 14, color: Colors.grey),
              label: const Text('Cancel', style: TextStyle(color: Colors.grey, fontSize: 12)),
            ),
            const SizedBox(width: 8),
          ],
          ElevatedButton.icon(
            onPressed: isEditing ? null : onEditToggle,
            style: ElevatedButton.styleFrom(
              backgroundColor: isEditing ? Colors.grey.shade300 : const Color(0xFFE65100),
              foregroundColor: isEditing ? Colors.grey.shade700 : Colors.white,
              elevation: isEditing ? 0 : 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            ),
            icon: Icon(isEditing ? Icons.check_circle_outline_rounded : Icons.edit_rounded, size: 14, color: isEditing ? Colors.grey.shade700 : Colors.white),
            label: Text(isEditing ? 'Editing Mode' : 'Edit Section', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
          ),
        ],
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
      'business_name': _businessNameController.text.trim(),
      'business_address': _businessAddressController.text.trim(),
      'business_category': _businessCategoryController.text.trim(),
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
    if (_educationStatus != null) fields['education_status'] = _educationStatus!;
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
        final wasNewProfile = !_hasProfile;
        setState(() {
          if (sectionType == 'personal') {
            _secPersonalCompleted = true;
            _editPersonal = false;
          }
          if (sectionType == 'education') {
            _secEducationCompleted = true;
            _editEducation = false;
          }
          if (sectionType == 'employment') {
            if (_employmentStatus == 'Employed') {
              _secEmploymentCompleted = _jobProfileController.text.isNotEmpty;
            } else if (_employmentStatus == 'Self-Employed') {
              _secEmploymentCompleted = _businessNameController.text.isNotEmpty;
            } else {
              _secEmploymentCompleted = _employmentStatus.isNotEmpty;
            }
            _editEmployment = false;
          }
          if (sectionType == 'bank') {
            _secBankCompleted = _hasBankDetails;
            _editBank = false;
          }
          if (sectionType == 'bocw') {
            _secBocwCompleted = !_isBocwRegistered || _bocwRegNoController.text.isNotEmpty;
            _editBocw = false;
          }
          if (sectionType == 'family') {
            _secFamilyCompleted = _familyMembers.isNotEmpty;
            _editFamily = false;
          }
          if (sectionType == 'signature') {
            _secSignatureCompleted = _signatureBytes != null || (_signatureUrl != null && _signatureUrl!.isNotEmpty);
            _editSignature = false;
          }
        });
        if (wasNewProfile) {
          setState(() => _hasProfile = true);
          // Show Dashboard button dialog for new profile
          if (mounted) {
            showDialog(
              context: context,
              barrierDismissible: false,
              builder: (ctx) => AlertDialog(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                title: const Row(children: [
                  Icon(Icons.check_circle_rounded, color: Colors.green, size: 28),
                  SizedBox(width: 10),
                  Text('Profile Created!', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
                ]),
                content: const Text(
                  'Your profile has been created successfully. You can continue filling other sections or go to the Dashboard now.',
                  style: TextStyle(fontSize: 14),
                ),
                actions: [
                  TextButton(
                    onPressed: () {
                      Navigator.of(ctx).pop();
                      _loadProfile();
                    },
                    child: const Text('Continue Filling', style: TextStyle(color: Colors.grey)),
                  ),
                  ElevatedButton.icon(
                    onPressed: () {
                      Navigator.of(ctx).pop();
                      Navigator.of(context).pushAndRemoveUntil(
                        MaterialPageRoute(builder: (_) => const DashboardScreen()),
                        (route) => false,
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFE65100),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    icon: const Icon(Icons.dashboard_rounded, color: Colors.white, size: 18),
                    label: const Text('Go to Dashboard', style: TextStyle(color: Colors.white)),
                  ),
                ],
              ),
            );
          }
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
                          try {
                            final res = await _apiService.lookupIfsc(ifsc);
                            if (res['success'] == true && res['data'] != null) {
                              final bankData = res['data'] as Map<String, dynamic>;
                              final bankName = bankData['BANK'] ?? '';
                              final branch = bankData['BRANCH'] ?? '';
                              setModalState(() {
                                isFetchingIfsc = false;
                                ifscFetched = true;
                                bankNameCtrl.text = '$bankName, $branch';
                              });
                            } else {
                              setModalState(() => isFetchingIfsc = false);
                              ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(res['message'] ?? 'IFSC code details nahi mili.')));
                            }
                          } catch (e) {
                            setModalState(() => isFetchingIfsc = false);
                            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('IFSC check error: $e')));
                          }
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
                  obscureText: true,
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
  // RATION CARD PDF UPLOAD DIALOG (Real file picker + backend OCR)
  // ─────────────────────────────────────────────────────────────────────────
  void _showRationCardUploadDialog() {
    bool isUploading = false;
    bool isProcessing = false;
    bool pdfSelected = false;
    String? pdfName;
    List<int>? pdfBytes;
    String? errorMsg;
    bool isMismatch = false;
    String? pdfExtractedRationNo;
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
                Row(
                  children: [
                    const Text('Ration Card Number', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                    const SizedBox(width: 4),
                    const Text('*', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(width: 6),
                    const Expanded(child: Text('(Verification ke liye zaroori)', style: TextStyle(fontSize: 10, color: Colors.grey))),
                  ],
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: rationNoCtrl,
                  textCapitalization: TextCapitalization.characters,
                  onChanged: (_) => setModalState(() { isMismatch = false; errorMsg = null; parsedMembers = []; }),
                  decoration: InputDecoration(
                    hintText: 'e.g. GJ-22-123-4567890',
                    prefixIcon: const Icon(Icons.credit_card_rounded, size: 18, color: Color(0xFFE65100)),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE65100), width: 1.5)),
                  ),
                ),
                const SizedBox(height: 16),
                const Text('Upload Ration Card PDF', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 4),
                const Text('Download from MyRation App and upload here. Data will be auto-filled.', style: TextStyle(fontSize: 11, color: Colors.grey)),
                const SizedBox(height: 8),
                // PDF Picker Area
                GestureDetector(
                  onTap: isUploading || isProcessing ? null : () async {
                    setModalState(() { isUploading = true; pdfSelected = false; parsedMembers = []; errorMsg = null; });
                    try {
                      final result = await FilePicker.platform.pickFiles(
                        type: FileType.custom,
                        allowedExtensions: ['pdf'],
                        withData: true,
                      );
                      if (result != null && result.files.isNotEmpty) {
                        final file = result.files.first;
                        setModalState(() {
                          isUploading = false;
                          pdfSelected = true;
                          pdfName = file.name;
                          pdfBytes = file.bytes;
                        });
                      } else {
                        setModalState(() { isUploading = false; });
                      }
                    } catch (e) {
                      setModalState(() { isUploading = false; errorMsg = 'Failed to select file: $e'; });
                    }
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
                if (errorMsg != null) ...[
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: isMismatch ? Colors.red.shade50 : Colors.orange.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: isMismatch ? Colors.red.shade300 : Colors.orange.shade300),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(children: [
                          Icon(isMismatch ? Icons.error_rounded : Icons.warning_rounded,
                              color: isMismatch ? Colors.red : Colors.orange, size: 16),
                          const SizedBox(width: 6),
                          Expanded(child: Text(
                            isMismatch ? 'Ration Card Number Mismatch!' : 'Error',
                            style: TextStyle(fontWeight: FontWeight.bold, color: isMismatch ? Colors.red : Colors.orange.shade800, fontSize: 12),
                          )),
                        ]),
                        const SizedBox(height: 4),
                        Text(errorMsg!, style: TextStyle(color: isMismatch ? Colors.red.shade700 : Colors.orange.shade800, fontSize: 11)),
                        if (isMismatch && pdfExtractedRationNo != null) ...[
                          const SizedBox(height: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(color: Colors.red.shade100, borderRadius: BorderRadius.circular(4)),
                            child: Text('Found in PDF: $pdfExtractedRationNo', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.red)),
                          ),
                          const SizedBox(height: 2),
                          const Text('Please upload the correct ration card PDF or verify the number above.', style: TextStyle(fontSize: 10, color: Colors.grey)),
                        ],
                      ],
                    ),
                  ),
                ],
                // Extract Button
                if (pdfSelected && pdfBytes != null && parsedMembers.isEmpty && !isProcessing) ...[
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () async {
                        final enteredNo = rationNoCtrl.text.trim();
                        if (enteredNo.isEmpty) {
                          setModalState(() { errorMsg = 'Please enter ration card number first.'; isMismatch = false; });
                          return;
                        }
                        setModalState(() { isProcessing = true; errorMsg = null; isMismatch = false; });
                        try {
                          final result = await _apiService.parseRationCardPdf(
                            pdfBytes!,
                            pdfName ?? 'ration_card.pdf',
                            rationCardNumber: enteredNo,
                          );
                          if (result['success'] == true) {
                            final List members = result['members'] ?? [];
                            final extractedNo = result['ration_number'] as String?;
                            if (members.isEmpty) {
                              setModalState(() {
                                isProcessing = false;
                                errorMsg = 'No family members found in PDF. Please add them manually.';
                              });
                            } else {
                              // Also auto-fill ration card number field if not already filled
                              if (extractedNo != null && rationNoCtrl.text.isEmpty) {
                                rationNoCtrl.text = extractedNo;
                              }
                              setModalState(() {
                                isProcessing = false;
                                pdfExtractedRationNo = extractedNo;
                                parsedMembers = members.map((m) => Map<String, dynamic>.from(m)).toList();
                              });
                            }
                          } else if (result['mismatch'] == true) {
                            setModalState(() {
                              isProcessing = false;
                              isMismatch = true;
                              pdfExtractedRationNo = result['pdf_ration_number'] as String?;
                              errorMsg = result['message'] ?? 'Ration card number mismatch.';
                            });
                          } else {
                            setModalState(() {
                              isProcessing = false;
                              errorMsg = result['message'] ?? 'Failed to parse PDF.';
                            });
                          }
                        } catch (e) {
                          setModalState(() { isProcessing = false; errorMsg = 'Error: $e'; });
                        }
                      },
                      style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1565C0), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
                      icon: const Icon(Icons.auto_awesome_rounded, color: Colors.white, size: 18),
                      label: const Text('Extract Family Data from PDF', style: TextStyle(color: Colors.white)),
                    ),
                  ),
                ],
                if (isProcessing) const Padding(padding: EdgeInsets.only(top: 16), child: Center(child: Column(children: [CircularProgressIndicator(color: Color(0xFFE65100)), SizedBox(height: 8), Text('Extracting data from PDF...', style: TextStyle(color: Colors.grey, fontSize: 12))]))),
                if (parsedMembers.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.green.shade200)),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(children: [const Icon(Icons.check_circle_rounded, color: Colors.green, size: 16), const SizedBox(width: 6), Text('Found ${parsedMembers.length} family members!', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.green, fontSize: 13))]),
                        const SizedBox(height: 8),
                        ...parsedMembers.map((m) => Padding(
                          padding: const EdgeInsets.symmetric(vertical: 3),
                          child: Row(children: [
                            Icon((m['gender'] as String? ?? '').toLowerCase() == 'female' ? Icons.female_rounded : Icons.male_rounded, size: 14, color: Colors.grey.shade600),
                            const SizedBox(width: 4),
                            Expanded(child: Text('${m['name']} (${m['relation']}, ${m['age']} yrs)', style: const TextStyle(fontSize: 12))),
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
                  _familyMembers = parsedMembers.map((m) => WorkerFamilyMember(
                    name: m['name'] as String? ?? '',
                    relation: m['relation'] as String? ?? '',
                    gender: m['gender'] as String? ?? 'male',
                    age: (m['age'] as num?)?.toInt() ?? 0,
                  )).toList();
                });
                Navigator.of(ctx).pop();
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Family members successfully added from ration card!'), backgroundColor: Colors.green));
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
    String rel = 'Self';
    String gen = 'male';
    final relations = [
      'Head of Family (HoF)',
      'Self',
      'Spouse',
      'Son',
      'Daughter',
      'Father',
      'Mother',
      'Brother',
      'Sister',
      'Daughter-in-law',
      'Son-in-law',
      'Grandson',
      'Granddaughter',
      'Grandfather',
      'Grandmother',
      'Father-in-law',
      'Mother-in-law',
      'Brother-in-law',
      'Sister-in-law',
      'Nephew',
      'Niece',
      'Ward / Adopted Child',
      'Other Dependent'
    ];

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

  // ── Wizard Helper Widgets ──
  Widget _buildRegistrationWizard() {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0xFFFFF3E0), Color(0xFFF5F5F5)],
          stops: [0.0, 0.4],
        ),
      ),
      child: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFE65100), Color(0xFFF57C00)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFFE65100).withOpacity(0.3),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Ration Card Onboarding',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Get started by uploading your ration card. We will automatically fill your profile and family details.',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.85),
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                _buildStepIndicator(1, 'Upload PDF', _wizardStep >= 1),
                Expanded(
                  child: Container(
                    height: 2,
                    color: _wizardStep >= 2 ? const Color(0xFFE65100) : Colors.grey.shade300,
                  ),
                ),
                _buildStepIndicator(2, 'Select Applicant', _wizardStep >= 2),
                Expanded(
                  child: Container(
                    height: 2,
                    color: _wizardStep >= 3 ? const Color(0xFFE65100) : Colors.grey.shade300,
                  ),
                ),
                _buildStepIndicator(3, 'Select Trade', _wizardStep >= 3),
              ],
            ),
            const SizedBox(height: 24),
            _wizardStep == 1
                ? _buildWizardStep1()
                : _wizardStep == 2
                    ? _buildWizardStep2()
                    : _buildWizardStep3(),
          ],
        ),
      ),
    );
  }

  Widget _buildStepIndicator(int stepNum, String title, bool isActive) {
    return Column(
      children: [
        CircleAvatar(
          radius: 16,
          backgroundColor: isActive ? const Color(0xFFE65100) : Colors.grey.shade300,
          child: Text(
            stepNum.toString(),
            style: TextStyle(
              color: isActive ? Colors.white : Colors.grey.shade600,
              fontWeight: FontWeight.bold,
              fontSize: 12,
            ),
          ),
        ),
        const SizedBox(height: 6),
        Text(
          title,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
            color: isActive ? const Color(0xFFE65100) : Colors.grey.shade500,
          ),
        ),
      ],
    );
  }

  Widget _buildWizardStep1() {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Upload Ration Card PDF',
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.bold,
                color: Color(0xFF263238),
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Please download the official ration card PDF from your state portal or MyRation app, and upload it here.',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey.shade600,
              ),
            ),
            const SizedBox(height: 20),
            GestureDetector(
              onTap: _isWizardProcessing ? null : () async {
                setState(() {
                  _wizardExtractedMembers = [];
                  _selectedApplicantIndex = null;
                });
                try {
                  final result = await FilePicker.platform.pickFiles(
                    type: FileType.custom,
                    allowedExtensions: ['pdf'],
                    withData: true,
                  );
                  if (result != null && result.files.isNotEmpty) {
                    final file = result.files.first;
                    setState(() {
                      _wizardPdfName = file.name;
                      _wizardPdfBytes = file.bytes;
                    });
                  }
                } catch (e) {
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Failed to select file: $e')),
                    );
                  }
                }
              },
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 36, horizontal: 16),
                decoration: BoxDecoration(
                  color: _wizardPdfBytes != null ? const Color(0xFFE8F5E9) : const Color(0xFFFAFAFA),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: _wizardPdfBytes != null ? const Color(0xFF81C784) : Colors.grey.shade300,
                    width: 1.5,
                    style: BorderStyle.solid,
                  ),
                ),
                child: Column(
                  children: [
                    Icon(
                      _wizardPdfBytes != null ? Icons.picture_as_pdf_rounded : Icons.cloud_upload_outlined,
                      size: 48,
                      color: _wizardPdfBytes != null ? const Color(0xFF2E7D32) : Colors.grey.shade400,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      _wizardPdfBytes != null ? (_wizardPdfName ?? 'ration_card.pdf') : 'Tap to select Ration Card PDF',
                      style: TextStyle(
                        color: _wizardPdfBytes != null ? const Color(0xFF2E7D32) : Colors.grey.shade700,
                        fontWeight: _wizardPdfBytes != null ? FontWeight.bold : FontWeight.normal,
                        fontSize: 13,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    if (_wizardPdfBytes != null) ...[
                      const SizedBox(height: 6),
                      Text(
                        'File selected successfully ✓',
                        style: TextStyle(
                          color: Colors.green.shade700,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton.icon(
                onPressed: (_wizardPdfBytes == null || _isWizardProcessing)
                    ? null
                    : () async {
                        setState(() {
                          _isWizardProcessing = true;
                        });
                        try {
                          final result = await _apiService.parseRationCardPdf(
                            _wizardPdfBytes!,
                            _wizardPdfName ?? 'ration_card.pdf',
                          );
                          if (result['success'] == true) {
                            final List membersList = result['members'] ?? [];
                            final extractedNo = result['ration_number'] as String?;
                            final extractedAddr = result['address'] as String?;
                            final extractedMob = result['mobile'] as String?;
                            
                            if (membersList.isEmpty) {
                              if (mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('No family members found in PDF. Please ensure you uploaded the correct PDF.'),
                                    backgroundColor: Colors.orange,
                                  ),
                                );
                              }
                              setState(() {
                                _isWizardProcessing = false;
                              });
                            } else {
                              setState(() {
                                _isWizardProcessing = false;
                                _wizardExtractedRationNo = extractedNo ?? '';
                                _wizardExtractedAddress = extractedAddr ?? '';
                                _wizardExtractedMobile = extractedMob ?? '';
                                _wizardExtractedMembers = membersList.map((m) => Map<String, dynamic>.from(m)).toList();
                                
                                _wizardRationNoController.text = _wizardExtractedRationNo ?? '';
                                _wizardAddressController.text = _wizardExtractedAddress ?? '';
                                _wizardMobileController.text = _wizardExtractedMobile ?? '';
                                
                                _wizardStep = 2;
                              });
                            }
                          } else {
                            setState(() {
                              _isWizardProcessing = false;
                            });
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(result['message'] ?? 'Failed to extract data from PDF.'),
                                  backgroundColor: Colors.red,
                                ),
                              );
                            }
                          }
                        } catch (e) {
                          setState(() {
                            _isWizardProcessing = false;
                          });
                          if (mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text('Error parsing PDF: $e'),
                                backgroundColor: Colors.red,
                              ),
                            );
                          }
                        }
                      },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFE65100),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  elevation: 2,
                ),
                icon: _isWizardProcessing
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Icon(Icons.auto_awesome_rounded),
                label: Text(
                  _isWizardProcessing ? 'Processing PDF...' : 'Extract Data & Next',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildWizardStep2() {
    return Form(
      key: _wizardFormKey,
      child: Card(
        elevation: 2,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Review Extracted Information',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF263238),
                ),
              ),
              const SizedBox(height: 6),
              Text(
                'Verify and edit the extracted details if needed. These fields were automatically read from your ration card.',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                ),
              ),
              const SizedBox(height: 16),
              _buildLabel('Ration Card Number *'),
              _buildTextField(
                controller: _wizardRationNoController,
                hint: 'Ration card number',
                enabled: false,
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Ration Card Number is required' : null,
              ),
              _buildLabel('Mobile Number *'),
              _buildTextField(
                controller: _wizardMobileController,
                hint: '10-digit mobile number',
                maxLength: 10,
                keyboardType: TextInputType.phone,
                validator: (v) => (v == null || v.trim().length != 10) ? 'Enter valid 10-digit mobile number' : null,
              ),
              _buildLabel('Address *'),
              _buildTextField(
                controller: _wizardAddressController,
                hint: 'Residential address',
                maxLines: 2,
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Address is required' : null,
              ),
              const SizedBox(height: 24),
              const Divider(),
              const SizedBox(height: 12),
              const Text(
                'Who is the Applicant?',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF263238),
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Select your name from the family members list extracted from your Ration Card.',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                ),
              ),
              const SizedBox(height: 12),
              ListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: _wizardExtractedMembers.length,
                itemBuilder: (context, index) {
                  final member = _wizardExtractedMembers[index];
                  final isSelected = _selectedApplicantIndex == index;
                  return Card(
                    elevation: isSelected ? 1 : 0,
                    margin: const EdgeInsets.symmetric(vertical: 4),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                      side: BorderSide(
                        color: isSelected ? const Color(0xFFE65100) : Colors.grey.shade200,
                        width: isSelected ? 1.5 : 1,
                      ),
                    ),
                    child: RadioListTile<int>(
                      value: index,
                      groupValue: _selectedApplicantIndex,
                      activeColor: const Color(0xFFE65100),
                      onChanged: (val) {
                        setState(() {
                          _selectedApplicantIndex = val;
                        });
                      },
                      title: Text(
                        member['name'] ?? '',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: isSelected ? const Color(0xFFE65100) : Colors.black87,
                          fontSize: 13,
                        ),
                      ),
                      subtitle: Text(
                        '${member['relation']} | ${(member['gender'] ?? '').toString().toUpperCase()} | Age: ${member['age']} yrs',
                        style: const TextStyle(fontSize: 12),
                      ),
                    ),
                  );
                },
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: _isWizardProcessing
                          ? null
                          : () {
                              setState(() {
                                _wizardStep = 1;
                              });
                            },
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: Colors.grey),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: const Text('Back', style: TextStyle(color: Colors.grey)),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: (_selectedApplicantIndex == null || _isWizardProcessing)
                          ? null
                          : () async {
                              if (!_wizardFormKey.currentState!.validate()) return;
                              
                              setState(() { _isWizardProcessing = true; });

                              // ── Duplicate ration card check ──
                              final rationNo = _wizardRationNoController.text.trim();
                              if (rationNo.isNotEmpty) {
                                try {
                                  final checkResult = await _apiService.checkRationCardExists(rationNo);
                                  if (checkResult['exists'] == true) {
                                    setState(() { _isWizardProcessing = false; });
                                    if (mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(
                                          content: Text(
                                            checkResult['message'] ?? 'This Ration Card Number is already registered.',
                                          ),
                                          backgroundColor: Colors.red.shade700,
                                          duration: const Duration(seconds: 5),
                                        ),
                                      );
                                    }
                                    return;
                                  }
                                } catch (_) {
                                  // network error
                                }
                              }
                              setState(() {
                                _isWizardProcessing = false;
                                _wizardStep = 3;
                              });
                            },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFE65100),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: _isWizardProcessing
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            )
                          : const Text('Next', style: TextStyle(fontWeight: FontWeight.bold)),
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

  Widget _buildWizardStep3() {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Select Your Trade / Job Profile',
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.bold,
                color: Color(0xFF263238),
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Please select the trade or job profile you are working in. This is mandatory.',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey.shade600,
              ),
            ),
            const SizedBox(height: 16),
            _buildLabel('Job Profile / Trade *'),
            _isLoadingTrades
                ? const Center(child: Padding(padding: EdgeInsets.all(12), child: CircularProgressIndicator()))
                : DropdownButtonFormField<String>(
                    decoration: InputDecoration(
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    ),
                    hint: const Text('Select your trade'),
                    value: _isOtherTradeSelected ? 'Other' : _selectedTrade?.name,
                    items: [
                      ..._availableTrades.map((t) => DropdownMenuItem<String>(
                            value: t.name,
                            child: Text(t.name),
                          )),
                      const DropdownMenuItem<String>(
                        value: 'Other',
                        child: Text('Other'),
                      ),
                    ],
                    onChanged: (val) {
                      setState(() {
                        if (val == 'Other') {
                          _isOtherTradeSelected = true;
                          _selectedTrade = null;
                        } else {
                          _isOtherTradeSelected = false;
                          _selectedTrade = _availableTrades.firstWhere((t) => t.name == val);
                        }
                      });
                    },
                  ),
            if (_isOtherTradeSelected) ...[
              const SizedBox(height: 16),
              _buildLabel('Enter Custom Job Profile *'),
              _buildTextField(
                controller: _customTradeController,
                hint: 'e.g. Tailor, Electrician Helper',
              ),
            ],
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _isWizardProcessing
                        ? null
                        : () {
                            setState(() {
                              _wizardStep = 2;
                            });
                          },
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: Colors.grey),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    child: const Text('Back', style: TextStyle(color: Colors.grey)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: (_isOtherTradeSelected && _customTradeController.text.trim().isEmpty) || (!_isOtherTradeSelected && _selectedTrade == null) || _isWizardProcessing
                        ? null
                        : () async {
                            final String tradeName = _isOtherTradeSelected
                                ? _customTradeController.text.trim()
                                : (_selectedTrade?.name ?? '');
                            if (tradeName.isEmpty) return;

                            setState(() { _isWizardProcessing = true; });

                            try {
                              final applicant = _wizardExtractedMembers[_selectedApplicantIndex!];
                              final name = applicant['name'] as String? ?? '';
                              final gender = (applicant['gender'] as String? ?? 'male').toLowerCase();
                              final age = (applicant['age'] as num?)?.toInt() ?? 30;

                              final currentYear = DateTime.now().year;
                              final birthYear = currentYear - age;
                              final calculatedDob = '$birthYear-01-01';

                              final familyList = <WorkerFamilyMember>[];
                              for (int i = 0; i < _wizardExtractedMembers.length; i++) {
                                if (i == _selectedApplicantIndex) continue;
                                final m = _wizardExtractedMembers[i];
                                final fAge = (m['age'] as num?)?.toInt() ?? 30;
                                final fBirthYear = currentYear - fAge;
                                final fDob = '$fBirthYear-01-01';

                                familyList.add(WorkerFamilyMember(
                                  name: m['name'] as String? ?? '',
                                  relation: m['relation'] as String? ?? '',
                                  gender: (m['gender'] as String? ?? 'male').toLowerCase(),
                                  age: fAge,
                                  dob: fDob,
                                ));
                              }

                              final Map<String, String> fields = {
                                'full_name': name,
                                'dob': calculatedDob,
                                'gender': gender,
                                'ration_card_number': _wizardRationNoController.text.trim(),
                                'mobile_alt': _wizardMobileController.text.trim(),
                                'aadhaar_address': _wizardAddressController.text.trim(),
                                'local_address': _wizardAddressController.text.trim(),
                                'job_profile': tradeName,
                              };

                              final res = await _apiService.submitProfile(
                                fields: fields,
                                family: familyList,
                                isUpdate: false,
                              );

                              setState(() {
                                _isWizardProcessing = false;
                              });

                              if (res['success'] == true) {
                                if (mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      content: Text('Profile successfully created!'),
                                      backgroundColor: Colors.green,
                                    ),
                                  );
                                }
                                setState(() {
                                  _wizardStep = 1;
                                  _wizardPdfBytes = null;
                                  _wizardPdfName = null;
                                  _wizardExtractedRationNo = null;
                                  _wizardExtractedAddress = null;
                                  _wizardExtractedMobile = null;
                                  _wizardExtractedMembers = [];
                                  _selectedApplicantIndex = null;
                                  _selectedTrade = null;
                                  _isOtherTradeSelected = false;
                                  _customTradeController.clear();
                                  _wizardRationNoController.clear();
                                  _wizardAddressController.clear();
                                  _wizardMobileController.clear();
                                });
                                _loadProfile();
                              } else {
                                if (mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(res['message'] ?? 'Failed to submit profile.'),
                                      backgroundColor: Colors.red,
                                    ),
                                  );
                                }
                              }
                            } catch (e) {
                              setState(() {
                                _isWizardProcessing = false;
                              });
                              if (mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text('Submission error: $e'),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                              }
                            }
                          },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFE65100),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: _isWizardProcessing
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Save Profile', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
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
        title: Row(
          children: [
            Text(_hasProfile ? 'My Profile' : 'Profile Registration', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            if (_hasProfile && _audit != null && _audit!['approved_at'] != null) ...[
              const SizedBox(width: 8),
              const Icon(
                Icons.verified_rounded,
                color: Colors.greenAccent,
                size: 20,
              ),
            ],
          ],
        ),
        backgroundColor: const Color(0xFFE65100),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          if (widget.isFirstTime)
            TextButton.icon(
              onPressed: () {
                Navigator.of(context).pushReplacement(
                  MaterialPageRoute(builder: (_) => const DashboardScreen()),
                );
              },
              icon: const Icon(Icons.dashboard_rounded, color: Colors.white, size: 18),
              label: const Text(
                'Dashboard',
                style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
              ),
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFE65100)))
          : (!_hasProfile && !widget.skipWizard)
              ? _buildRegistrationWizard()
              : SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // ── Audit Trail Card ──
                      if (_audit != null) _buildAuditTrailCard(),
                      if (_audit != null) const SizedBox(height: 16),

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
  // AUDIT TRAIL CARD
  // ─────────────────────────────────────────────────────────────────────────
  Widget _buildAuditTrailCard() {
    final audit = _audit!;

    String _fmtDate(String? iso) {
      if (iso == null || iso.isEmpty) return '—';
      try {
        final dt = DateTime.parse(iso).toLocal();
        final months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return '${dt.day.toString().padLeft(2,'0')} ${months[dt.month-1]} ${dt.year}, ${dt.hour.toString().padLeft(2,'0')}:${dt.minute.toString().padLeft(2,'0')}';
      } catch (_) { return iso; }
    }

    final bool isVerified  = audit['verified_at']  != null;
    final bool isApproved  = audit['approved_at']  != null;

    Widget _statusPill(String label, Color bg, Color fg) => Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Text(label, style: TextStyle(color: fg, fontSize: 11, fontWeight: FontWeight.w700)),
    );

    Widget _row(IconData icon, Color iconColor, String label, String value) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: iconColor),
          const SizedBox(width: 8),
          Expanded(
            child: RichText(
              text: TextSpan(
                style: const TextStyle(fontSize: 14, color: Colors.white),
                children: [
                  TextSpan(text: '$label: ', style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.white70)),
                  TextSpan(text: value),
                ],
              ),
            ),
          ),
        ],
      ),
    );

    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1A237E), Color(0xFF283593)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: const Color(0xFF1A237E).withOpacity(0.3), blurRadius: 12, offset: const Offset(0, 4))],
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header row
          Row(
            children: [
              const Icon(Icons.verified_user_rounded, color: Colors.white, size: 20),
              const SizedBox(width: 8),
              const Text('Profile Status', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
              const Spacer(),
              if (isApproved)
                _statusPill('✓ APPROVED', const Color(0xFF43A047), Colors.white)
              else if (isVerified)
                _statusPill('⏳ VERIFIED', const Color(0xFFFFA000), Colors.white)
              else
                _statusPill('PENDING', const Color(0xFF78909C), Colors.white),
            ],
          ),
          const SizedBox(height: 12),
          Container(height: 1, color: Colors.white.withOpacity(0.2)),
          const SizedBox(height: 12),

          // Registration
          _row(Icons.app_registration_rounded, const Color(0xFF80DEEA),
              'Registered On', _fmtDate(audit['registered_at'] as String?)),

          // Verification
          if (isVerified) ...[
            const SizedBox(height: 4),
            _row(Icons.check_circle_rounded, const Color(0xFF80CBC4),
                'Verified On', _fmtDate(audit['verified_at'] as String?)),
            if ((audit['verified_by_name'] as String?) != null)
              _row(Icons.person_rounded, const Color(0xFF80CBC4),
                  'Verified By',
                  '${audit['verified_by_name']}'
                  '${(audit['verified_by_dept_name'] as String?) != null ? " (${audit['verified_by_dept_name']})" : ""}'),
          ] else ...[
            const SizedBox(height: 4),
            _row(Icons.hourglass_empty_rounded, const Color(0xFFFFCC80),
                'Verification', 'Pending'),
          ],

          // Approval
          if (isApproved) ...[
            const SizedBox(height: 4),
            _row(Icons.task_alt_rounded, const Color(0xFFA5D6A7),
                'Approved On', _fmtDate(audit['approved_at'] as String?)),
            if ((audit['approved_by_name'] as String?) != null)
              _row(Icons.admin_panel_settings_rounded, const Color(0xFFA5D6A7),
                  'Approved By',
                  '${audit['approved_by_name']}'
                  '${(audit['approved_by_dept_name'] as String?) != null ? " (${audit['approved_by_dept_name']})" : ""}'),
          ] else ...[
            const SizedBox(height: 4),
            _row(Icons.pending_actions_rounded, const Color(0xFFFFCC80),
                'Approval', 'Pending'),
          ],
        ],
      ),
    );
  }

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
            _buildSectionHeaderActions(
              isEditing: _editPersonal,
              onEditToggle: () => setState(() => _editPersonal = true),
              onCancel: () => setState(() { _editPersonal = false; _loadProfile(); }),
            ),
            // Photo
            Center(
              child: GestureDetector(
                onTap: _canEditPersonal ? _pickPhoto : null,
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
                    if (_canEditPersonal)
                      Container(
                        padding: const EdgeInsets.all(5),
                        decoration: const BoxDecoration(color: Color(0xFFE65100), shape: BoxShape.circle),
                        child: const Icon(Icons.camera_alt_rounded, color: Colors.white, size: 16),
                      )
                    else if (_audit != null && _audit!['approved_at'] != null)
                      Container(
                        padding: const EdgeInsets.all(3),
                        decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                        child: const Icon(Icons.verified_rounded, color: Colors.green, size: 24),
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 4),
            if (_canEditPersonal)
              const Center(child: Text('Tap to upload photo', style: TextStyle(fontSize: 11, color: Colors.grey))),
            const SizedBox(height: 16),

            _buildLabel('Full Name *'),
            _buildTextField(controller: _nameController, hint: 'Enter full name', readOnly: !_canEditPersonal, validator: (v) => (v == null || v.isEmpty) ? 'Full Name is required' : null),

            _buildLabel("Father's Name / Husband's Name *"),
            _buildTextField(controller: _fatherNameController, hint: "Enter father's or husband's name", readOnly: !_canEditPersonal, validator: (v) => (v == null || v.isEmpty) ? 'Required' : null),

            _buildLabel('Date of Birth *'),
            GestureDetector(
              onTap: _canEditPersonal ? () => _selectDate(context, _dobController) : null,
              child: AbsorbPointer(child: _buildTextField(controller: _dobController, hint: 'DD/MM/YYYY', readOnly: !_canEditPersonal, validator: (v) {
                if (v == null || v.isEmpty) return 'Date of birth required';
                if (_parseDisplayDate(v) == null) return 'Enter valid date (DD/MM/YYYY)';
                return null;
              })),
            ),

            _buildLabel('Gender *'),
            _buildDropdown<String>(value: _gender, items: _genderOptions, onChanged: (val) { if (val != null) setState(() => _gender = val); }, enabled: _canEditPersonal),

            _buildLabel('Permanent Address (as per Aadhaar) *'),
            _buildTextField(controller: _aadhaarAddressController, hint: 'Enter address as in Aadhaar', maxLines: 2, readOnly: !_canEditPersonal, validator: (v) => (v == null || v.isEmpty) ? 'Address required' : null),

            // Same as permanent checkbox
            Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Checkbox(
                  value: _localSameAsPermanent,
                  activeColor: const Color(0xFFE65100),
                  onChanged: _canEditPersonal ? (val) {
                    setState(() {
                      _localSameAsPermanent = val ?? false;
                      if (_localSameAsPermanent) {
                        _localAddressController.text = _aadhaarAddressController.text;
                      }
                    });
                  } : null,
                ),
                const Expanded(
                  child: Text(
                    'Local address same as permanent address',
                    style: TextStyle(fontSize: 13, color: Color(0xFF374151)),
                  ),
                ),
              ],
            ),

            if (!_localSameAsPermanent) ...[
              _buildLabel('Local / Current Address *'),
              _buildTextField(controller: _localAddressController, hint: 'Enter current residential address', maxLines: 2, readOnly: !_canEditPersonal, validator: (v) => (!_localSameAsPermanent && (v == null || v.isEmpty)) ? 'Local address required' : null),
            ],

            _buildLabel('Marital Status'),
            _buildDropdown<String>(value: _maritalStatus, items: _maritalStatusOptions, onChanged: (val) => setState(() => _maritalStatus = val), enabled: _canEditPersonal),

            _buildLabel('Social Category'),
            _buildDropdown<String>(value: _category, items: _categoryOptions, onChanged: (val) => setState(() => _category = val), enabled: _canEditPersonal),

            _buildLabel('Identification Mark *'),
            _buildTextField(controller: _identificationMarkController, hint: 'e.g. mole on right cheek', readOnly: !_canEditPersonal, validator: (v) => (v == null || v.isEmpty) ? 'Required' : null),

            _buildLabel('Mobile Number *'),
            _buildTextField(controller: _mobileAltController, hint: 'Primary contact number', maxLength: 10, keyboardType: TextInputType.phone, readOnly: !_canEditPersonal, validator: (v) => (v == null || v.length != 10) ? 'Enter valid 10-digit number' : null),

            _buildLabel('Email Address'),
            _buildTextField(controller: _emailController, hint: 'email@domain.com', keyboardType: TextInputType.emailAddress, readOnly: !_canEditPersonal),
            if (_canEditPersonal) ...[
              const SizedBox(height: 20),
              _buildSectionSaveButton(
                onPressed: () => _saveSection('personal'),
                label: 'Save Personal Details',
                sectionType: 'personal',
              ),
            ],
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
            _buildSectionHeaderActions(
              isEditing: _editEducation,
              onEditToggle: () => setState(() => _editEducation = true),
              onCancel: () => setState(() { _editEducation = false; _loadProfile(); }),
            ),
            _buildLabel('Education Qualification'),
            _buildDropdown<String>(
              value: _education,
              items: _educationOptions,
              onChanged: (val) => setState(() => _education = val),
              enabled: _canEditEducation,
            ),
            const SizedBox(height: 16),
            _buildLabel('Education Status'),
            _buildDropdown<String>(
              value: _educationStatus,
              items: _educationStatusOptions,
              onChanged: (val) => setState(() => _educationStatus = val),
              enabled: _canEditEducation,
            ),
            if (_canEditEducation) ...[
              const SizedBox(height: 20),
              _buildSectionSaveButton(
                onPressed: () => _saveSection('education'),
                label: 'Save Education Details',
                sectionType: 'education',
              ),
            ],
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
            _buildSectionHeaderActions(
              isEditing: _editEmployment,
              onEditToggle: () => setState(() => _editEmployment = true),
              onCancel: () => setState(() { _editEmployment = false; _loadProfile(); }),
            ),
            _buildLabel('Current Employment Status *'),
            _buildDropdown<String>(value: _employmentStatus, items: _employmentStatusOptions, onChanged: (val) { if (val != null) setState(() => _employmentStatus = val); }, enabled: _canEditEmployment),

            if (_employmentStatus == 'Employed') ...[
              _buildLabel('Name of Employer'),
              Autocomplete<String>(
                initialValue: TextEditingValue(text: _employerNameController.text),
                optionsBuilder: _canEditEmployment ? (TextEditingValue textEditingValue) {
                  if (textEditingValue.text.isEmpty) return const Iterable<String>.empty();
                  return _employerList.where((e) => e.toLowerCase().contains(textEditingValue.text.toLowerCase()));
                } : (TextEditingValue val) => const Iterable<String>.empty(),
                onSelected: _canEditEmployment ? (String selected) {
                  setState(() {
                    _employerNameController.text = selected;
                    // Auto-fill site for known employers (mock)
                    if (selected.contains('National Highway')) _employmentSiteController.text = 'NH-48, Jaipur-Delhi Highway';
                    else if (selected.contains('Metro Rail')) _employmentSiteController.text = 'Metro Station, Phase-2, Jaipur';
                    else _employmentSiteController.clear();
                  });
                } : null,
                fieldViewBuilder: (context, ctrl, focusNode, onSubmitted) {
                  ctrl.text = _employerNameController.text;
                  return TextField(
                    controller: ctrl,
                    focusNode: focusNode,
                    onChanged: (v) => _employerNameController.text = v,
                    readOnly: !_canEditEmployment,
                    style: TextStyle(fontSize: 14, color: !_canEditEmployment ? Colors.grey.shade700 : Colors.black87),
                    decoration: InputDecoration(
                      hintText: 'Type to search employer...',
                      filled: !_canEditEmployment,
                      fillColor: !_canEditEmployment ? Colors.grey.shade100 : Colors.transparent,
                      contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: !_canEditEmployment ? Colors.grey.shade200 : Colors.grey.shade300)),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: !_canEditEmployment ? Colors.grey.shade200 : const Color(0xFFE65100), width: 1.5)),
                      suffixIcon: _canEditEmployment ? const Icon(Icons.search_rounded, color: Colors.grey) : null,
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
              _buildTextField(controller: _employmentSiteController, hint: 'Job site location (auto-filled if employer selected)', readOnly: !_canEditEmployment),

              _buildLabel('Job Profile (Trade/Occupation) *'),
              Autocomplete<String>(
                initialValue: TextEditingValue(text: _jobProfileController.text),
                optionsBuilder: _canEditEmployment ? (TextEditingValue textEditingValue) {
                  if (textEditingValue.text.isEmpty) return _jobProfileList;
                  return _jobProfileList.where((e) => e.toLowerCase().contains(textEditingValue.text.toLowerCase()));
                } : (TextEditingValue val) => const Iterable<String>.empty(),
                onSelected: _canEditEmployment ? (String selected) => setState(() => _jobProfileController.text = selected) : null,
                fieldViewBuilder: (context, ctrl, focusNode, onSubmitted) {
                  ctrl.text = _jobProfileController.text;
                  return TextFormField(
                    controller: ctrl,
                    focusNode: focusNode,
                    onChanged: (v) => _jobProfileController.text = v,
                    readOnly: !_canEditEmployment,
                    style: TextStyle(fontSize: 14, color: !_canEditEmployment ? Colors.grey.shade700 : Colors.black87),
                    validator: (v) => (_employmentStatus == 'Employed' && (v == null || v.isEmpty)) ? 'Job Profile required' : null,
                    decoration: InputDecoration(
                      hintText: 'Select or type trade/occupation...',
                      filled: !_canEditEmployment,
                      fillColor: !_canEditEmployment ? Colors.grey.shade100 : Colors.transparent,
                      contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: !_canEditEmployment ? Colors.grey.shade200 : Colors.grey.shade300)),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: !_canEditEmployment ? Colors.grey.shade200 : const Color(0xFFE65100), width: 1.5)),
                      suffixIcon: _canEditEmployment ? const Icon(Icons.arrow_drop_down_rounded, color: Colors.grey) : null,
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

            if (_employmentStatus == 'Self-Employed') ...[
              _buildLabel('Business Name *'),
              _buildTextField(
                controller: _businessNameController,
                hint: 'Enter your business / shop name',
                readOnly: !_canEditEmployment,
                validator: (v) => (_employmentStatus == 'Self-Employed' && (v == null || v.isEmpty)) ? 'Business Name required' : null,
              ),

              _buildLabel('Business Address *'),
              _buildTextField(
                controller: _businessAddressController,
                hint: 'Enter business location address',
                readOnly: !_canEditEmployment,
                validator: (v) => (_employmentStatus == 'Self-Employed' && (v == null || v.isEmpty)) ? 'Business Address required' : null,
              ),

              _buildLabel('Business Category (i.e. Plumber/Electrician/etc.) *'),
              Autocomplete<String>(
                initialValue: TextEditingValue(text: _businessCategoryController.text),
                optionsBuilder: _canEditEmployment ? (TextEditingValue textEditingValue) {
                  if (textEditingValue.text.isEmpty) return _jobProfileList;
                  return _jobProfileList.where((e) => e.toLowerCase().contains(textEditingValue.text.toLowerCase()));
                } : (TextEditingValue val) => const Iterable<String>.empty(),
                onSelected: _canEditEmployment ? (String selected) => setState(() => _businessCategoryController.text = selected) : null,
                fieldViewBuilder: (context, ctrl, focusNode, onSubmitted) {
                  ctrl.text = _businessCategoryController.text;
                  return TextFormField(
                    controller: ctrl,
                    focusNode: focusNode,
                    onChanged: (v) => _businessCategoryController.text = v,
                    readOnly: !_canEditEmployment,
                    style: TextStyle(fontSize: 14, color: !_canEditEmployment ? Colors.grey.shade700 : Colors.black87),
                    validator: (v) => (_employmentStatus == 'Self-Employed' && (v == null || v.isEmpty)) ? 'Business Category required' : null,
                    decoration: InputDecoration(
                      hintText: 'Select or type business category...',
                      filled: !_canEditEmployment,
                      fillColor: !_canEditEmployment ? Colors.grey.shade100 : Colors.transparent,
                      contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: !_canEditEmployment ? Colors.grey.shade200 : Colors.grey.shade300)),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: !_canEditEmployment ? Colors.grey.shade200 : const Color(0xFFE65100), width: 1.5)),
                      suffixIcon: _canEditEmployment ? const Icon(Icons.arrow_drop_down_rounded, color: Colors.grey) : null,
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
            if (_canEditEmployment) ...[
              const SizedBox(height: 20),
              _buildSectionSaveButton(
                onPressed: () => _saveSection('employment'),
                label: 'Save Employment Details',
                sectionType: 'employment',
              ),
            ],
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
          _buildSectionHeaderActions(
            isEditing: _editBank,
            onEditToggle: () => setState(() => _editBank = true),
            onCancel: () => setState(() { _editBank = false; _loadProfile(); }),
          ),
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
          if (_canEditBank) ...[
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
            _buildSectionHeaderActions(
              isEditing: _editBocw,
              onEditToggle: () => setState(() => _editBocw = true),
              onCancel: () => setState(() { _editBocw = false; _loadProfile(); }),
            ),
            Row(
              children: [
                Checkbox(
                  value: _isBocwRegistered,
                  activeColor: const Color(0xFFE65100),
                  onChanged: _canEditBocw ? (val) => setState(() => _isBocwRegistered = val ?? false) : null,
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
              _buildTextField(controller: _bocwRegNoController, hint: 'Enter BOCW registration number', readOnly: !_canEditBocw, validator: (v) => (_isBocwRegistered && (v == null || v.isEmpty)) ? 'BOCW Reg. No. is required' : null),

              _buildLabel('State Board Name *'),
              _buildTextField(controller: _bocwStateBoardController, hint: 'e.g. Rajasthan Building & Other Construction Workers', readOnly: !_canEditBocw, validator: (v) => (_isBocwRegistered && (v == null || v.isEmpty)) ? 'State Board name is required' : null),

              _buildLabel('Registration Date'),
              GestureDetector(
                onTap: _canEditBocw ? () async {
                  final initial = _bocwRegDate != null ? (_parseDisplayDate(_bocwRegDate!) ?? DateTime.now()) : DateTime.now();
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: initial,
                    firstDate: DateTime(2000),
                    lastDate: DateTime.now(),
                    builder: (ctx, child) => Theme(data: Theme.of(ctx).copyWith(colorScheme: const ColorScheme.light(primary: Color(0xFFE65100), onPrimary: Colors.white)), child: child!),
                  );
                  if (picked != null) setState(() => _bocwRegDate = _formatDisplayDate(picked));
                } : null,
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                  decoration: BoxDecoration(
                    color: _canEditBocw ? Colors.transparent : Colors.grey.shade100,
                    border: Border.all(color: _canEditBocw ? Colors.grey.shade300 : Colors.grey.shade200),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today_rounded, size: 16, color: Colors.grey),
                      const SizedBox(width: 8),
                      Text(_bocwRegDate ?? 'Select date', style: TextStyle(color: _canEditBocw ? (_bocwRegDate != null ? Colors.black87 : Colors.grey) : Colors.grey.shade700, fontSize: 14)),
                    ],
                  ),
                ),
              ),

              _buildLabel('Valid Upto'),
              GestureDetector(
                onTap: _canEditBocw ? () async {
                  final initial = _bocwValidUpto != null ? (_parseDisplayDate(_bocwValidUpto!) ?? DateTime.now().add(const Duration(days: 365))) : DateTime.now().add(const Duration(days: 365));
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: initial,
                    firstDate: DateTime.now().subtract(const Duration(days: 365)),
                    lastDate: DateTime(2040),
                    builder: (ctx, child) => Theme(data: Theme.of(ctx).copyWith(colorScheme: const ColorScheme.light(primary: Color(0xFFE65100), onPrimary: Colors.white)), child: child!),
                  );
                  if (picked != null) setState(() => _bocwValidUpto = _formatDisplayDate(picked));
                } : null,
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                  decoration: BoxDecoration(
                    color: _canEditBocw ? Colors.transparent : Colors.grey.shade100,
                    border: Border.all(color: _canEditBocw ? Colors.grey.shade300 : Colors.grey.shade200),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.event_rounded, size: 16, color: Colors.grey),
                      const SizedBox(width: 8),
                      Text(_bocwValidUpto ?? 'Select date', style: TextStyle(color: _canEditBocw ? (_bocwValidUpto != null ? Colors.black87 : Colors.grey) : Colors.grey.shade700, fontSize: 14)),
                    ],
                  ),
                ),
              ),
            ],
            if (_canEditBocw) ...[
              const SizedBox(height: 20),
              _buildSectionSaveButton(
                onPressed: () => _saveSection('bocw'),
                label: 'Save BOCW Details',
                sectionType: 'bocw',
              ),
            ],
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
          _buildSectionHeaderActions(
            isEditing: _editFamily,
            onEditToggle: () => setState(() => _editFamily = true),
            onCancel: () => setState(() { _editFamily = false; _loadProfile(); }),
          ),
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
                  trailing: _canEditFamily ? IconButton(
                    icon: const Icon(Icons.remove_circle_outline_rounded, color: Colors.red),
                    onPressed: () => setState(() => _familyMembers.removeAt(index)),
                  ) : null,
                );
              },
            ),
          if (_canEditFamily) ...[
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
          ],
          if (_canEditFamily) ...[
            const SizedBox(height: 16),
            _buildSectionSaveButton(
              onPressed: () => _saveSection('family'),
              label: 'Save Family Details',
              sectionType: 'family',
            ),
          ],
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
          _buildSectionHeaderActions(
            isEditing: _editSignature,
            onEditToggle: () => setState(() => _editSignature = true),
            onCancel: () => setState(() { _editSignature = false; _loadProfile(); }),
          ),
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
          if (_canEditSignature) ...[
            const SizedBox(height: 4),
            const Text(
              'Minimum image size: 5 KB. Please upload a clear, legible signature.',
              style: TextStyle(fontSize: 11, color: Colors.grey),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 10),
            ElevatedButton.icon(
              onPressed: _chooseSignature,
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE65100), foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
              icon: const Icon(Icons.cloud_upload_rounded),
              label: const Text('Choose Signature Image'),
            ),
            const SizedBox(height: 16),
          ],
          if (_canEditSignature)
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
    bool readOnly = false,
    bool enabled = true,
  }) {
    final isDisabled = !enabled;
    return TextFormField(
      controller: controller,
      maxLength: maxLength,
      maxLines: maxLines ?? 1,
      keyboardType: keyboardType,
      validator: validator,
      readOnly: readOnly || isDisabled,
      enabled: enabled,
      style: TextStyle(
        fontSize: 14,
        color: (readOnly || isDisabled) ? Colors.grey.shade700 : Colors.black87,
      ),
      decoration: InputDecoration(
        hintText: hint,
        counterText: '',
        filled: readOnly || isDisabled,
        fillColor: isDisabled ? Colors.grey.shade200 : (readOnly ? Colors.grey.shade100 : Colors.transparent),
        contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: (readOnly || isDisabled) ? Colors.grey.shade200 : Colors.grey.shade300)),
        disabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: (readOnly || isDisabled) ? Colors.grey.shade200 : const Color(0xFFE65100), width: 1.5)),
        errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Colors.red)),
        focusedErrorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Colors.red, width: 1.5)),
      ),
    );
  }

  Widget _buildDropdown<T>({
    required T? value,
    required List<T> items,
    required ValueChanged<T?>? onChanged,
    bool enabled = true,
  }) {
    return DropdownButtonFormField<T>(
      value: value,
      onChanged: enabled ? onChanged : null,
      style: TextStyle(fontSize: 14, color: enabled ? Colors.black87 : Colors.grey.shade700),
      items: items.map((i) {
        String display = i.toString();
        if (display == 'male') display = 'Male';
        if (display == 'female') display = 'Female';
        if (display == 'other') display = 'Other';
        return DropdownMenuItem(value: i, child: Text(display));
      }).toList(),
      decoration: InputDecoration(
        filled: !enabled,
        fillColor: enabled ? Colors.transparent : Colors.grey.shade100,
        contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade300)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: enabled ? Colors.grey.shade300 : Colors.grey.shade200)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: enabled ? const Color(0xFFE65100) : Colors.grey.shade200, width: 1.5)),
      ),
    );
  }
}
