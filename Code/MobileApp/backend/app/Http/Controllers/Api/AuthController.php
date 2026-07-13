<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // POST /api/auth/send-otp
    public function sendOtp(Request $request)
    {
        $request->validate(['mobile' => 'required|digits:10']);

        // Check if there is any institutional user with this mobile, but no beneficiary
        $beneficiaryUser = User::where('mobile', $request->mobile)->where('role', 'beneficiary')->first();
        $anyUser = User::where('mobile', $request->mobile)->first();

        if ($anyUser && !$beneficiaryUser && !in_array($anyUser->role, ['beneficiary', 'super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'This mobile number is registered to an institutional account. Please use Institution Login.'
            ], 422);
        }

        $otp     = rand(100000, 999999);
        $otpHash = Hash::make($otp);

        // Store OTP hash in otp_logs collection
        \App\Models\OtpLog::create([
            'mobile'     => $request->mobile,
            'otp_hash'   => $otpHash,
            'purpose'    => 'login',
            'expires_at' => now()->addMinutes(10),
            'used'       => false,
        ]);

        // In production: send SMS via gateway
        // For dev: return OTP directly
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'otp'     => $otp, // Remove in production!
        ]);
    }

    // POST /api/auth/verify-otp
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'otp'    => 'required|digits:6',
        ]);

        // Get latest unused OTP record for this mobile
        $otpRecord = \App\Models\OtpLog::where('mobile', $request->mobile)
            ->where('purpose', 'login')
            ->where('expires_at', '>', now())
            ->where('used', false)
            ->latest('created_at')
            ->first();

        if (!$otpRecord || !Hash::check($request->otp, $otpRecord->otp_hash)) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 422);
        }

        // Get beneficiary user if exists
        $user = User::where('mobile', $request->mobile)->where('role', 'beneficiary')->first();
        
        // If not found, check if there is an institutional user
        if (!$user) {
            $anyUser = User::where('mobile', $request->mobile)->first();
            if ($anyUser && !in_array($anyUser->role, ['beneficiary', 'super-admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This mobile number is registered to an institutional account. Please use Institution Login.'
                ], 422);
            }
        }

        // Mark OTP as used
        $otpRecord->update(['used' => true]);

        // Get or create user
        if (!$user) {
            $user = User::create([
                'mobile' => $request->mobile,
                'role' => 'beneficiary',
                'is_active' => true,
                'is_verified' => false
            ]);
        }

        $user->update(['last_login' => now()]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Check if worker profile exists and is complete
        // Department-registered workers always have a profile (admin created it)
        // Self-registered workers need job_profile to be considered complete
        $worker = $user->worker()->first();
        $hasWorker = $worker !== null;
        $registrationSource = $hasWorker ? ($worker->registration_source ?? 'self') : null;

        // Dept-registered = profile complete (admin already saved their details)
        // Self-registered = complete only if job_profile is filled
        $isProfileComplete = $hasWorker && (
            $registrationSource === 'department' || !empty($worker->job_profile)
        );

        return response()->json([
            'success'             => true,
            'message'             => 'Login successful',
            'token'               => $token,
            'user'                => $user,
            'has_profile'         => $isProfileComplete,
            'registration_source' => $registrationSource,
        ]);
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    // POST /api/auth/change-password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password does not match.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.'
        ]);
    }

    // POST /api/auth/digilocker-login
    public function digiLockerLogin(Request $request)
    {
        $request->validate(['mobile' => 'required|digits:10']);

        // Get beneficiary user if exists
        $user = User::where('mobile', $request->mobile)->where('role', 'beneficiary')->first();
        
        // If not found, check if there is an institutional user
        if (!$user) {
            $anyUser = User::where('mobile', $request->mobile)->first();
            if ($anyUser && !in_array($anyUser->role, ['beneficiary', 'super-admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This mobile number is registered to an institutional account. Please use Institution Login.'
                ], 422);
            }
        }

        if (!$user) {
            $user = User::create([
                'mobile' => $request->mobile,
                'role' => 'beneficiary',
                'is_active' => true,
                'is_verified' => false
            ]);
        }

        $user->update(['last_login' => now()]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Mock DigiLocker details to fill automatically
        $digiLockerData = [
            'full_name'       => 'Rajesh Vishwakarma',
            'dob'             => '1992-06-15',
            'gender'          => 'male',
            'aadhaar_number'  => '987654321012',
            'aadhaar_address' => 'Plot No. 45, Sector 4, Indirapuram, Ghaziabad, Uttar Pradesh - 201014'
        ];

        return response()->json([
            'success'         => true,
            'message'         => 'DigiLocker login successful',
            'token'           => $token,
            'user'            => $user,
            'has_profile'     => $user->worker()->exists(),
            'digilocker_data' => $digiLockerData,
        ]);
    }

    // POST /api/auth/institution-login
    public function institutionLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        if (isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive'
            ], 403);
        }

        $user->update(['last_login' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
        ]);
    }
}

