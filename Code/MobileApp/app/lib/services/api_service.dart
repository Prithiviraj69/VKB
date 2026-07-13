import 'dart:convert';
import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:image_picker/image_picker.dart' show XFile;
import '../models/models.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  String get baseUrl {
    if (kIsWeb) {
      return 'http://localhost:8833/api';
    }
    return 'https://labourdnhdd.in/vkb/public/api';
  }

  String resolveImageUrl(String? url) {
    if (url == null || url.isEmpty) return '';
    if (!url.startsWith('http')) {
      final path = url.replaceFirst('/storage/', '/storage/');
      return '$baseUrl$path';
    }
    if (!kIsWeb) {
      try {
        if (Platform.isAndroid) {
          return url
              .replaceAll('127.0.0.1:8000', '10.0.2.2:8000')
              .replaceAll('localhost:8000', '10.0.2.2:8000');
        }
      } catch (_) {}
    }
    return url;
  }

  String? _token;

  Future<String?> getToken() async {
    if (_token != null) return _token;
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
    return _token;
  }

  Future<void> saveToken(String token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  Future<void> clearToken() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_role');
    await prefs.remove('user_name');
    await prefs.remove('user_email');
  }

  Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  Map<String, String> _getHeaders(String? token, {bool isJson = true}) {
    final headers = <String, String>{
      'Accept': 'application/json',
    };
    if (isJson) {
      headers['Content-Type'] = 'application/json';
    }
    if (token != null) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  // ================= AUTHENTICATION =================

  Future<Map<String, dynamic>> sendOtp(String mobile) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/send-otp'),
        headers: _getHeaders(null),
        body: jsonEncode({'mobile': mobile}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> verifyOtp(String mobile, String otp) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/verify-otp'),
        headers: _getHeaders(null),
        body: jsonEncode({'mobile': mobile, 'otp': otp}),
      );
      final data = jsonDecode(response.body);
      if (data['success'] == true && data['token'] != null) {
        await saveToken(data['token']);
        final prefs = await SharedPreferences.getInstance();
        String role = 'beneficiary';
        if (data['user'] != null) {
          role = data['user']['role'] ?? 'beneficiary';
          await prefs.setString('user_name', data['user']['name'] ?? '');
          await prefs.setString('user_email', data['user']['email'] ?? '');
        }
        await prefs.setString('user_role', role);
      }
      return data;
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> verifyDigiLocker(String mobile) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/digilocker-login'),
        headers: _getHeaders(null),
        body: jsonEncode({'mobile': mobile}),
      );
      final data = jsonDecode(response.body);
      if (data['success'] == true && data['token'] != null) {
        await saveToken(data['token']);
        final prefs = await SharedPreferences.getInstance();
        String role = 'beneficiary';
        if (data['user'] != null) {
          role = data['user']['role'] ?? 'beneficiary';
          await prefs.setString('user_name', data['user']['name'] ?? '');
          await prefs.setString('user_email', data['user']['email'] ?? '');
        }
        await prefs.setString('user_role', role);
      }
      return data;
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> logout() async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/auth/logout'),
        headers: _getHeaders(token),
      );
      await clearToken();
      return jsonDecode(response.body);
    } catch (e) {
      await clearToken(); // Force logout locally anyway
      return {'success': false, 'message': 'Logged out locally'};
    }
  }

  Future<Map<String, dynamic>> changePassword(String currentPassword, String newPassword) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/auth/change-password'),
        headers: _getHeaders(token),
        body: jsonEncode({
          'current_password': currentPassword,
          'new_password': newPassword,
          'new_password_confirmation': newPassword,
        }),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  // ================= WORKER PROFILE & DASHBOARD =================

  Future<Map<String, dynamic>> getDashboard() async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/worker/dashboard'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> getProfile() async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/worker/profile'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> submitProfile({
    required Map<String, String> fields,
    required List<WorkerFamilyMember> family,
    String? signaturePath,
    XFile? signatureXFile,
    String? photoPath,
    XFile? photoXFile,
    bool isUpdate = false,
  }) async {
    try {
      final token = await getToken();
      final uri = Uri.parse('$baseUrl/worker/profile');

      // Use POST with method spoofing for PUT updates to allow multipart files in PHP
      final request = http.MultipartRequest('POST', uri);

      request.headers.addAll(_getHeaders(token, isJson: false));

      // Add all text fields (remove photo_url if we're uploading a file)
      final filteredFields = Map<String, String>.from(fields);
      if (photoXFile != null || photoPath != null) {
        filteredFields.remove('photo_url');
      }
      if (isUpdate) {
        filteredFields['_method'] = 'PUT';
      }
      request.fields.addAll(filteredFields);

      // Add family members as JSON
      request.fields['family'] = jsonEncode(family.map((m) => m.toJson()).toList());

      // ── Upload Photo ──
      if (photoXFile != null) {
        if (kIsWeb) {
          final bytes = await photoXFile.readAsBytes();
          request.files.add(http.MultipartFile.fromBytes(
            'photo',
            bytes,
            filename: photoXFile.name,
          ));
        } else {
          try {
            request.files.add(await http.MultipartFile.fromPath(
              'photo',
              photoXFile.path,
              filename: photoXFile.name,
            ));
          } catch (_) {/* skip if file not accessible */}
        }
      } else if (photoPath != null && photoPath.isNotEmpty && !kIsWeb) {
        // Fallback for non-web with path only
        try {
          request.files.add(await http.MultipartFile.fromPath(
            'photo',
            photoPath,
            filename: photoPath.split('/').last,
          ));
        } catch (_) {/* skip if file not accessible */}
      }

      // ── Upload Signature ──
      if (signatureXFile != null) {
        if (kIsWeb) {
          final bytes = await signatureXFile.readAsBytes();
          request.files.add(http.MultipartFile.fromBytes(
            'signature',
            bytes,
            filename: signatureXFile.name,
          ));
        } else {
          try {
            request.files.add(await http.MultipartFile.fromPath(
              'signature',
              signatureXFile.path,
              filename: signatureXFile.name,
            ));
          } catch (_) {/* skip if file not accessible */}
        }
      } else if (signaturePath != null && signaturePath.isNotEmpty && !kIsWeb) {
        // Fallback for non-web with path only
        try {
          request.files.add(await http.MultipartFile.fromPath(
            'signature',
            signaturePath,
            filename: signaturePath.split('/').last,
          ));
        } catch (_) {/* skip */}
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> verifyRationCard(String rationCardNumber) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/worker/verify-ration'),
        headers: _getHeaders(token),
        body: jsonEncode({'ration_card_number': rationCardNumber}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  // ================= SCHEMES =================

  Future<List<Scheme>> getSchemes({String? search, String? ministry, String? level, String? type}) async {
    try {
      String query = '';
      if (search != null && search.isNotEmpty) query += 'search=$search&';
      if (ministry != null && ministry.isNotEmpty) query += 'ministry=$ministry&';
      if (level != null && level.isNotEmpty) query += 'level=$level&';
      if (type != null && type.isNotEmpty) query += 'type=$type&';
      
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/schemes?$query'),
        headers: _getHeaders(token),
      );
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true) {
        final List list = decoded['data']['data'] ?? [];
        return list.map((item) => Scheme.fromJson(item)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<Scheme?> getSchemeDetails(String schemeId) async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/schemes/$schemeId'),
        headers: _getHeaders(token),
      );
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true && decoded['data'] != null) {
        return Scheme.fromJson(decoded['data']);
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  Future<Map<String, dynamic>> createScheme(String title, String description, String url) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/schemes'),
        headers: _getHeaders(token),
        body: jsonEncode({
          'title': title,
          'description': description,
          'url': url,
        }),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> updateScheme(String id, String title, String description, String url) async {
    try {
      final token = await getToken();
      final response = await http.put(
        Uri.parse('$baseUrl/schemes/$id'),
        headers: _getHeaders(token),
        body: jsonEncode({
          'title': title,
          'description': description,
          'url': url,
        }),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> deleteScheme(String id) async {
    try {
      final token = await getToken();
      final response = await http.delete(
        Uri.parse('$baseUrl/schemes/$id'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> toggleSchemeActive(String id) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/schemes/$id/toggle-active'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> verifyScheme(String id, String remarks) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/schemes/$id/verify'),
        headers: _getHeaders(token),
        body: jsonEncode({'remarks': remarks}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> approveScheme(String id, String remarks) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/schemes/$id/approve'),
        headers: _getHeaders(token),
        body: jsonEncode({'remarks': remarks}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> applyForScheme(String schemeId, Map<String, dynamic> applicationData) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/schemes/$schemeId/apply'),
        headers: _getHeaders(token),
        body: jsonEncode({'data': applicationData}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> receiveBenefit(String schemeId) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/schemes/$schemeId/receive-benefit'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<List<SchemeApplication>> getMyApplications() async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/schemes/my-applications'),
        headers: _getHeaders(token),
      );
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true) {
        final List list = decoded['data']['data'] ?? [];
        return list.map((item) => SchemeApplication.fromJson(item)).toList();
      }
      return [];
    } catch (e, stack) {
      print("ERROR IN getMyApplications: $e");
      print(stack);
      return [];
    }
  }

  // ================= GRIEVANCES =================

  Future<List<Grievance>> getGrievances({String? status}) async {
    try {
      final token = await getToken();
      final url = status != null ? '$baseUrl/grievances?status=$status' : '$baseUrl/grievances';
      final response = await http.get(
        Uri.parse(url),
        headers: _getHeaders(token),
      );
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true) {
        final dataObj = decoded['data'];
        final List list = (dataObj is Map && dataObj['data'] != null)
            ? dataObj['data']
            : (dataObj is List ? dataObj : []);
        return list.map((item) => Grievance.fromJson(item)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<Map<String, dynamic>> fileGrievance(String subject, String category, String description) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/grievances'),
        headers: _getHeaders(token),
        body: jsonEncode({
          'subject': subject,
          'category': category,
          'description': description,
        }),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  // ================= DOCUMENTS =================

  Future<List<WorkerDocument>> getDocuments() async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/documents'),
        headers: _getHeaders(token),
      );
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true) {
        final List list = decoded['data'] ?? [];
        return list.map((item) => WorkerDocument.fromJson(item)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<Map<String, dynamic>> uploadDocument(String docType, String docNumber, String simulatedFilePath) async {
    try {
      final token = await getToken();
      // Since it's a simulated file upload in the backend / local test, 
      // the controller can handle multipart or simple json if needed.
      // Let's do multipart to make it authentic, falling back to JSON.
      var request = http.MultipartRequest('POST', Uri.parse('$baseUrl/documents'));
      request.headers.addAll(_getHeaders(token, isJson: false));
      request.fields['document_type'] = docType;
      request.fields['document_number'] = docNumber;
      
      // Add fake/mock file
      request.files.add(http.MultipartFile.fromString(
        'file',
        'simulated_document_content',
        filename: simulatedFilePath.split('/').last,
      ));

      var streamedResponse = await request.send();
      var response = await http.Response.fromStream(streamedResponse);
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> deleteDocument(int docId) async {
    try {
      final token = await getToken();
      final response = await http.delete(
        Uri.parse('$baseUrl/documents/$docId'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  // ================= NOTIFICATIONS =================

  Future<List<AppNotification>> getNotifications() async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/notifications'),
        headers: _getHeaders(token),
      );
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true) {
        final List list = decoded['data']['data'] ?? [];
        return list.map((item) => AppNotification.fromJson(item)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<void> readAllNotifications() async {
    try {
      final token = await getToken();
      await http.post(
        Uri.parse('$baseUrl/notifications/read-all'),
        headers: _getHeaders(token),
      );
    } catch (_) {}
  }

  // ================= METADATA LOOKUPS =================

  Future<List<StateModel>> getStates() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/states'), headers: _getHeaders(null));
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true) {
        final List list = decoded['data'] ?? [];
        return list.map((item) => StateModel.fromJson(item)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<List<DistrictModel>> getDistricts(int stateId) async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/districts/$stateId'), headers: _getHeaders(null));
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true) {
        final List list = decoded['data'] ?? [];
        return list.map((item) => DistrictModel.fromJson(item)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<List<Trade>> getTrades() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/trades'), headers: _getHeaders(null));
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true) {
        final List list = decoded['data'] ?? [];
        return list.map((item) => Trade.fromJson(item)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  // ================= RATION CARD PDF PARSING =================

  Future<Map<String, dynamic>> parseRationCardPdf(
    List<int> pdfBytes,
    String filename, {
    String? rationCardNumber,
  }) async {
    try {
      final token = await getToken();
      final uri = Uri.parse('$baseUrl/worker/parse-ration-pdf');
      final request = http.MultipartRequest('POST', uri);
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';
      request.files.add(http.MultipartFile.fromBytes(
        'ration_pdf',
        pdfBytes,
        filename: filename,
      ));
      if (rationCardNumber != null && rationCardNumber.isNotEmpty) {
        request.fields['ration_card_number'] = rationCardNumber;
      }
      final streamed = await request.send();
      final response = await http.Response.fromStream(streamed);
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  // ================= CHECK RATION CARD DUPLICATE =================

  Future<Map<String, dynamic>> checkRationCardExists(String number) async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/worker/check-ration-card?number=${Uri.encodeComponent(number)}'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  // ================= IFSC LOOKUP =================


  Future<Map<String, dynamic>> lookupIfsc(String ifsc) async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/worker/lookup-ifsc/$ifsc'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  // ================= INSTITUTION ENDPOINTS =================

  Future<Map<String, dynamic>> loginInstitution(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/institution-login'),
        headers: _getHeaders(null),
        body: jsonEncode({'email': email, 'password': password}),
      );
      final data = jsonDecode(response.body);
      if (data['success'] == true && data['token'] != null) {
        await saveToken(data['token']);
        final prefs = await SharedPreferences.getInstance();
        if (data['user'] != null) {
          await prefs.setString('user_role', data['user']['role'] ?? '');
          await prefs.setString('user_name', data['user']['name'] ?? '');
          await prefs.setString('user_email', data['user']['email'] ?? '');
        }
      }
      return data;
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> getRegisteredUsers() async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/institution/registered-users'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> getDashboardStats() async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/institution/dashboard-stats'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> getDepartmentDetails() async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/institution/department-details'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> getWorkers(String type) async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/institution/workers?type=$type'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> verifyWorker(String id, String remarks) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/institution/workers/$id/verify'),
        headers: _getHeaders(token),
        body: jsonEncode({'remarks': remarks}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> approveWorker(String id, String remarks) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/institution/workers/$id/approve'),
        headers: _getHeaders(token),
        body: jsonEncode({'remarks': remarks}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> registerUser(Map<String, dynamic> userData) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/institution/register-user'),
        headers: _getHeaders(token),
        body: jsonEncode(userData),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> checkMobile(String mobile) async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/institution/check-mobile/$mobile'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> registerBeneficiaryComplete(Map<String, dynamic> payload) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/institution/register-beneficiary-complete'),
        headers: _getHeaders(token),
        body: jsonEncode(payload),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> bulkRegisterUsers(List<int> fileBytes, String filename, String role) async {
    try {
      final token = await getToken();
      final uri = Uri.parse('$baseUrl/institution/bulk-register');
      final request = http.MultipartRequest('POST', uri);
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';
      
      request.fields['role'] = role;
      request.files.add(http.MultipartFile.fromBytes(
        'file',
        fileBytes,
        filename: filename,
      ));

      final streamed = await request.send();
      final response = await http.Response.fromStream(streamed);
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> toggleUserStatus(String userId) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/institution/toggle-user-status/$userId'),
        headers: _getHeaders(token),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> rejectWorker(String id, String remarks) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/institution/workers/$id/reject'),
        headers: _getHeaders(token),
        body: jsonEncode({'remarks': remarks}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> updateGrievanceStatus(String id, String status, String? resolutionNote) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/grievances/$id/action'),
        headers: _getHeaders(token),
        body: jsonEncode({
          'status': status,
          'resolution_note': resolutionNote,
        }),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<List<SchemeApplication>> getSchemeApplications({String? status}) async {
    try {
      final token = await getToken();
      final url = status != null ? '$baseUrl/scheme-applications?status=$status' : '$baseUrl/scheme-applications';
      final response = await http.get(
        Uri.parse(url),
        headers: _getHeaders(token),
      );
      final decoded = jsonDecode(response.body);
      if (decoded['success'] == true) {
        final List list = decoded['data'] ?? [];
        return list.map((item) => SchemeApplication.fromJson(item)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<Map<String, dynamic>> verifySchemeApplication(String id, String remarks) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/scheme-applications/$id/verify'),
        headers: _getHeaders(token),
        body: jsonEncode({'remarks': remarks}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> approveSchemeApplication(String id, String remarks) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/scheme-applications/$id/approve'),
        headers: _getHeaders(token),
        body: jsonEncode({'remarks': remarks}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<Map<String, dynamic>> rejectSchemeApplication(String id, String remarks) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/scheme-applications/$id/reject'),
        headers: _getHeaders(token),
        body: jsonEncode({'remarks': remarks}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }
}

