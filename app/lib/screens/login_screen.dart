import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'dashboard_screen.dart';
import 'profile_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _mobileController = TextEditingController();
  final _otpController = TextEditingController();
  final _apiService = ApiService();

  bool _isOtpSent = false;
  bool _isLoading = false;
  String? _errorMessage;
  String? _successMessage;

  @override
  void dispose() {
    _mobileController.dispose();
    _otpController.dispose();
    super.dispose();
  }

  Future<void> _handleSendOtp() async {
    final mobile = _mobileController.text.trim();
    if (mobile.length != 10 || int.tryParse(mobile) == null) {
      setState(() {
        _errorMessage = 'Please enter a valid 10-digit mobile number.';
        _successMessage = null;
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _successMessage = null;
    });

    final res = await _apiService.sendOtp(mobile);

    setState(() {
      _isLoading = false;
    });

    if (res['success'] == true) {
      setState(() {
        _isOtpSent = true;
        // In local development, show the OTP for convenience
        final devOtp = res['otp'] != null ? ' (OTP: ${res['otp']})' : '';
        _successMessage = 'OTP has been sent to your mobile number.$devOtp';
      });
    } else {
      setState(() {
        _errorMessage = res['message'] ?? 'Error sending OTP. Please try again.';
      });
    }
  }

  Future<void> _handleVerifyOtp() async {
    final mobile = _mobileController.text.trim();
    final otp = _otpController.text.trim();

    if (otp.length < 4) {
      setState(() {
        _errorMessage = 'Please enter the correct OTP.';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final res = await _apiService.verifyOtp(mobile, otp);

    setState(() {
      _isLoading = false;
    });

    if (res['success'] == true) {
      if (mounted) {
        final hasProfile = res['has_profile'] == true || res['has_profile'] == 1;
        if (hasProfile) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const DashboardScreen()),
          );
        } else {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const ProfileScreen()),
          );
        }
      }
    } else {
      setState(() {
        _errorMessage = res['message'] ?? 'Invalid OTP entered.';
      });
    }
  }

  void _showDigiLockerLoginDialog() {
    final digiMobileController = TextEditingController(text: _mobileController.text);
    final digiPinController = TextEditingController();
    bool isDialogLoading = false;
    String? dialogError;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Row(
            children: [
              const Icon(Icons.cloud_done_rounded, color: Color(0xFF1565C0), size: 28),
              const SizedBox(width: 8),
              const Text(
                'DigiLocker Sign In',
                style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1565C0)),
              ),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Sign in to your DigiLocker account to fetch your verified documents automatically.',
                  style: TextStyle(fontSize: 13, color: Colors.grey),
                ),
                const SizedBox(height: 20),
                if (dialogError != null) ...[
                  Text(
                    dialogError!,
                    style: const TextStyle(color: Colors.red, fontSize: 13, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                ],
                const Text('Mobile Number / Aadhaar *', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 6),
                TextField(
                  controller: digiMobileController,
                  keyboardType: TextInputType.phone,
                  maxLength: 10,
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.phone_android, color: Color(0xFF1565C0)),
                    hintText: '10-digit mobile number',
                    counterText: '',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
                const SizedBox(height: 16),
                const Text('6-Digit Security PIN *', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 6),
                TextField(
                  controller: digiPinController,
                  keyboardType: TextInputType.number,
                  obscureText: true,
                  maxLength: 6,
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.lock_outline_rounded, color: Color(0xFF1565C0)),
                    hintText: 'Enter 6-digit PIN',
                    counterText: '',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: isDialogLoading ? null : () => Navigator.of(ctx).pop(),
              child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              onPressed: isDialogLoading ? null : () async {
                final mobile = digiMobileController.text.trim();
                final pin = digiPinController.text.trim();

                if (mobile.length != 10 || int.tryParse(mobile) == null) {
                  setModalState(() {
                    dialogError = 'Please enter a valid 10-digit mobile number.';
                  });
                  return;
                }
                if (pin.length != 6 || int.tryParse(pin) == null) {
                  setModalState(() {
                    dialogError = 'Please enter a 6-digit security PIN.';
                  });
                  return;
                }

                setModalState(() {
                  isDialogLoading = true;
                  dialogError = null;
                });

                final res = await _apiService.verifyDigiLocker(mobile);

                if (!mounted) return;

                setModalState(() {
                  isDialogLoading = false;
                });

                if (res['success'] == true) {
                  Navigator.of(ctx).pop(); // Close dialog
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('DigiLocker Authentication Successful!'),
                      backgroundColor: Colors.green,
                    ),
                  );

                  // Check if profile exists, redirect accordingly
                  final hasProfile = res['has_profile'] == true || res['has_profile'] == 1;
                  if (hasProfile) {
                    Navigator.of(context).pushReplacement(
                      MaterialPageRoute(builder: (_) => const DashboardScreen()),
                    );
                  } else {
                    Navigator.of(context).pushReplacement(
                      MaterialPageRoute(
                        builder: (_) => ProfileScreen(digiLockerData: res['digilocker_data']),
                      ),
                    );
                  }
                } else {
                  setModalState(() {
                    dialogError = res['message'] ?? 'DigiLocker Authentication Failed.';
                  });
                }
              },
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1565C0)),
              child: isDialogLoading
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Text('Sign In', style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [const Color(0xFFFFF3E0), Colors.white],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 40.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 20),
                Center(
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFE0B2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.lock_person_rounded,
                      size: 56,
                      color: Color(0xFFE65100),
                    ),
                  ),
                ),
                const SizedBox(height: 30),
                const Center(
                  child: Text(
                    'Login / Register',
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFFE65100),
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                const Center(
                  child: Text(
                    'Securely sign in with your mobile number',
                    style: TextStyle(fontSize: 14, color: Colors.grey),
                  ),
                ),
                const SizedBox(height: 40),
                
                if (_errorMessage != null)
                  Container(
                    margin: const EdgeInsets.only(bottom: 20),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.red.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.red.shade200),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.error_outline, color: Colors.red.shade700),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _errorMessage!,
                            style: TextStyle(color: Colors.red.shade900, fontSize: 13),
                          ),
                        ),
                      ],
                    ),
                  ),

                if (_successMessage != null)
                  Container(
                    margin: const EdgeInsets.only(bottom: 20),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.green.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.green.shade200),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.check_circle_outline, color: Colors.green.shade700),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _successMessage!,
                            style: TextStyle(color: Colors.green.shade900, fontSize: 13),
                          ),
                        ),
                      ],
                    ),
                  ),

                Text(
                  'Mobile Number',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.grey.shade700,
                  ),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: _mobileController,
                  enabled: !_isOtpSent,
                  keyboardType: TextInputType.phone,
                  maxLength: 10,
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.phone_android, color: Color(0xFFE65100)),
                    hintText: '10-digit mobile number',
                    counterText: '',
                    filled: true,
                    fillColor: Colors.white,
                    contentPadding: const EdgeInsets.symmetric(vertical: 16.0),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE65100), width: 2),
                    ),
                  ),
                ),
                
                if (_isOtpSent) ...[
                  const SizedBox(height: 24),
                  Text(
                    'OTP',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Colors.grey.shade700,
                    ),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _otpController,
                    keyboardType: TextInputType.number,
                    maxLength: 6,
                    decoration: InputDecoration(
                      prefixIcon: const Icon(Icons.security, color: Color(0xFFE65100)),
                      hintText: 'Enter OTP',
                      counterText: '',
                      filled: true,
                      fillColor: Colors.white,
                      contentPadding: const EdgeInsets.symmetric(vertical: 16.0),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(color: Colors.grey.shade300),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: const BorderSide(color: Color(0xFFE65100), width: 2),
                      ),
                    ),
                  ),
                ],
                
                const SizedBox(height: 32),
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton(
                    onPressed: _isLoading 
                        ? null 
                        : (_isOtpSent ? _handleVerifyOtp : _handleSendOtp),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFE65100),
                      foregroundColor: Colors.white,
                      elevation: 2,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: _isLoading
                        ? const CircularProgressIndicator(color: Colors.white)
                        : Text(
                            _isOtpSent ? 'Verify OTP' : 'Send OTP',
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                  ),
                ),

                if (!_isOtpSent) ...[
                  const SizedBox(height: 20),
                  const Center(
                    child: Text(
                      'OR',
                      style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: OutlinedButton.icon(
                      onPressed: _isLoading ? null : _showDigiLockerLoginDialog,
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: Color(0xFF1565C0), width: 1.5),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      icon: const Icon(Icons.cloud_done_rounded, color: Color(0xFF1565C0)),
                      label: const Text(
                        'Login with DigiLocker',
                        style: TextStyle(
                          color: Color(0xFF1565C0),
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                ],
                
                if (_isOtpSent) ...[
                  const SizedBox(height: 16),
                  Center(
                    child: TextButton(
                      onPressed: _isLoading ? null : () {
                        setState(() {
                          _isOtpSent = false;
                          _otpController.clear();
                          _successMessage = null;
                        });
                      },
                      child: const Text(
                        'Change Mobile Number',
                        style: TextStyle(color: Color(0xFFE65100), fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
