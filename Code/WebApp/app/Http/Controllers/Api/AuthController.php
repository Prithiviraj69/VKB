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

        // Mark OTP as used
        $otpRecord->update(['used' => true]);

        // Get or create user
        $user = User::firstOrCreate(
            ['mobile' => $request->mobile],
            ['role' => 'worker', 'is_active' => true, 'is_verified' => false]
        );

        $user->update(['last_login' => now()]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success'      => true,
            'message'      => 'Login successful',
            'token'        => $token,
            'user'         => $user,
            'has_profile'  => $user->worker()->exists(),
        ]);
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    // POST /api/auth/digilocker-login
    public function digiLockerLogin(Request $request)
    {
        $request->validate(['mobile' => 'required|digits:10']);

        // Get or create user
        $user = User::firstOrCreate(
            ['mobile' => $request->mobile],
            ['role' => 'worker', 'is_active' => true, 'is_verified' => false]
        );

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
}
