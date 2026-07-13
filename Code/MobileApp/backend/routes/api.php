<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\SchemeController;
use App\Http\Controllers\Api\GrievanceController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\InstitutionController;

// Public routes (no auth needed)
Route::prefix('auth')->group(function () {
    Route::post('/send-otp',           [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp',         [AuthController::class, 'verifyOtp']);
    Route::post('/digilocker-login',   [AuthController::class, 'digiLockerLogin']);
    Route::post('/institution-login',  [AuthController::class, 'institutionLogin']);
});

// Location / lookup routes (public)
Route::get('/states',              [LocationController::class, 'states']);
Route::get('/districts/{stateId}', [LocationController::class, 'districts']);
Route::get('/trades',              [LocationController::class, 'trades']);

// Serve storage files with CORS headers and test-fake fallback
Route::get('/storage/{type}/{id}/{filename}', function ($type, $id, $filename) {
    $path = storage_path('app/public/' . $type . '/' . $id . '/' . $filename);
    if (!file_exists($path)) {
        $path = storage_path('framework/testing/disks/public/' . $type . '/' . $id . '/' . $filename);
    }
    if (!file_exists($path)) {
        abort(404);
    }
    $file = file_get_contents($path);
    $mime = mime_content_type($path);
    return response($file, 200)
        ->header('Content-Type', $mime)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
});

// Protected routes (require Sanctum token)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Institution user management
    Route::get('/institution/registered-users', [InstitutionController::class, 'getRegisteredUsers']);
    Route::post('/institution/register-user',   [InstitutionController::class, 'registerUser']);
    Route::post('/institution/bulk-register',   [InstitutionController::class, 'bulkRegister']);
    Route::get('/institution/check-mobile/{mobile}', [InstitutionController::class, 'checkMobile']);
    Route::post('/institution/register-beneficiary-complete', [InstitutionController::class, 'registerBeneficiaryComplete']);
    Route::post('/institution/toggle-user-status/{id}', [InstitutionController::class, 'toggleUserStatus']);
    Route::get('/institution/dashboard-stats', [InstitutionController::class, 'getDashboardStats']);
    Route::get('/institution/department-details', [InstitutionController::class, 'getDepartmentDetails']);
    Route::get('/institution/workers', [InstitutionController::class, 'getWorkers']);
    Route::post('/institution/workers/{id}/verify', [InstitutionController::class, 'verifyWorker']);
    Route::post('/institution/workers/{id}/approve', [InstitutionController::class, 'approveWorker']);
    Route::post('/institution/workers/{id}/reject', [InstitutionController::class, 'rejectWorker']);

    // Worker profile
    Route::get('/worker/dashboard',       [WorkerController::class, 'dashboard']);
    Route::get('/worker/profile',         [WorkerController::class, 'profile']);
    Route::post('/worker/profile',        [WorkerController::class, 'createProfile']);
    Route::put('/worker/profile',         [WorkerController::class, 'updateProfile']);
    Route::post('/worker/verify-ration',  [WorkerController::class, 'verifyRation']);
    Route::post('/worker/parse-ration-pdf', [WorkerController::class, 'parseRationPdf']);
    Route::get('/worker/check-ration-card', [WorkerController::class, 'checkRationCard']);
    Route::get('/worker/lookup-ifsc/{ifsc}', [WorkerController::class, 'lookupIfsc']);


    // Schemes
    Route::get('/schemes',               [SchemeController::class, 'index']);
    Route::post('/schemes',              [SchemeController::class, 'store']);
    Route::put('/schemes/{id}',          [SchemeController::class, 'update']);
    Route::delete('/schemes/{id}',       [SchemeController::class, 'destroy']);
    Route::post('/schemes/{id}/toggle-active', [SchemeController::class, 'toggleActive']);
    Route::get('/schemes/my-applications', [SchemeController::class, 'myApplications']);
    Route::get('/schemes/{id}',          [SchemeController::class, 'show']);
    Route::post('/schemes/{id}/verify',  [SchemeController::class, 'verify']);
    Route::post('/schemes/{id}/approve', [SchemeController::class, 'approve']);
    Route::post('/schemes/{id}/apply',   [SchemeController::class, 'apply']);
    Route::post('/schemes/{id}/receive-benefit', [SchemeController::class, 'receiveBenefit']);
    
    // Scheme Applications Management (Officials)
    Route::get('/scheme-applications', [SchemeController::class, 'listApplications']);
    Route::post('/scheme-applications/{id}/verify', [SchemeController::class, 'verifyApplication']);
    Route::post('/scheme-applications/{id}/approve', [SchemeController::class, 'approveApplication']);
    Route::post('/scheme-applications/{id}/reject', [SchemeController::class, 'rejectApplication']);

    // Grievances
    Route::get('/grievances',       [GrievanceController::class, 'index']);
    Route::post('/grievances',      [GrievanceController::class, 'store']);
    Route::get('/grievances/{id}',  [GrievanceController::class, 'show']);
    Route::post('/grievances/{id}/action', [GrievanceController::class, 'takeAction']);

    // Documents
    Route::get('/documents',        [DocumentController::class, 'index']);
    Route::post('/documents',       [DocumentController::class, 'store']);
    Route::delete('/documents/{id}',[DocumentController::class, 'destroy']);

    // Notifications
    Route::get('/notifications',           [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
});
