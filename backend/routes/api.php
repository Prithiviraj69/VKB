<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\SchemeController;
use App\Http\Controllers\Api\GrievanceController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\VerifierController;
use App\Http\Controllers\Api\ApproverController;

// Public login route (JWT auth based)
Route::post('/login', [SuperAdminController::class, 'login']);

// Super Admin, Verifier, and Approver Protected Routes (using custom JWT auth middleware)
Route::middleware(\App\Http\Middleware\JwtAuthMiddleware::class)->group(function () {
    // Super Admin Endpoints
    Route::post('/super-admin/create-user',              [SuperAdminController::class, 'createUser']);
    Route::get('/super-admin/users',                     [SuperAdminController::class, 'getUsers']);
    Route::patch('/super-admin/user/{id}/toggle-status', [SuperAdminController::class, 'toggleStatus']);
    Route::delete('/super-admin/user/{id}',             [SuperAdminController::class, 'deleteUser']);
    Route::get('/super-admin/labour-data',               [SuperAdminController::class, 'getLabourData']);

    // Verifier Endpoints
    Route::get('/verifier/pending-workers',              [VerifierController::class, 'getPendingWorkers']);
    Route::post('/verifier/worker/{id}/verify',          [VerifierController::class, 'verifyWorker']);
    Route::post('/verifier/worker/{id}/reject',          [VerifierController::class, 'rejectWorker']);

    // Approver Endpoints
    Route::get('/approver/verified-workers',             [ApproverController::class, 'getVerifiedWorkers']);
    Route::post('/approver/worker/{id}/approve',         [ApproverController::class, 'approveWorker']);
    Route::post('/approver/worker/{id}/reject',          [ApproverController::class, 'rejectWorker']);
});

// Public routes (no auth needed)
Route::prefix('auth')->group(function () {
    Route::post('/send-otp',          [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp',        [AuthController::class, 'verifyOtp']);
    Route::post('/digilocker-login',  [AuthController::class, 'digiLockerLogin']);
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

// Schemes listing (public)
Route::get('/schemes',             [SchemeController::class, 'index']);
Route::get('/schemes/{id}',        [SchemeController::class, 'show']);

// Protected routes (require Sanctum token)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Worker profile
    Route::get('/worker/dashboard',       [WorkerController::class, 'dashboard']);
    Route::get('/worker/profile',         [WorkerController::class, 'profile']);
    Route::post('/worker/profile',        [WorkerController::class, 'createProfile']);
    Route::put('/worker/profile',         [WorkerController::class, 'updateProfile']);
    Route::post('/worker/verify-ration',  [WorkerController::class, 'verifyRation']);


    // Schemes - apply
    Route::post('/schemes/{id}/apply',   [SchemeController::class, 'apply']);
    Route::get('/schemes/my-applications', [SchemeController::class, 'myApplications']);

    // Grievances
    Route::get('/grievances',       [GrievanceController::class, 'index']);
    Route::post('/grievances',      [GrievanceController::class, 'store']);
    Route::get('/grievances/{id}',  [GrievanceController::class, 'show']);

    // Documents
    Route::get('/documents',        [DocumentController::class, 'index']);
    Route::post('/documents',       [DocumentController::class, 'store']);
    Route::delete('/documents/{id}',[DocumentController::class, 'destroy']);

    // Notifications
    Route::get('/notifications',           [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
});
