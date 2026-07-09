<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\SuperAdminDashboardController;
use App\Http\Controllers\Web\VerifierDashboardController;
use App\Http\Controllers\Web\ApproverDashboardController;
use App\Http\Controllers\Web\NgoDashboardController;
use App\Http\Controllers\Web\EmployerDashboardController;
use App\Http\Controllers\Web\ContractorDashboardController;
use App\Http\Controllers\Web\GovtDeptAdminController;

// Public Auth routes
Route::get('/', function () {
    return redirect()->route('login');
});
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);
Route::get('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Protected Dashboard routes (Requires session Auth)
Route::middleware('auth')->group(function () {

    // Force Password Change routes
    Route::get('/force-change-password', [WebAuthController::class, 'showForceChangePassword'])->name('force-change-password');
    Route::post('/force-change-password', [WebAuthController::class, 'updateForceChangePassword'])->name('force-change-password.update');

    // Super Admin Dashboard routes
    Route::get('/super-admin', [SuperAdminDashboardController::class, 'index'])->name('super-admin.index');
    Route::get('/super-admin/onboard', [SuperAdminDashboardController::class, 'onboardHub'])->name('super-admin.onboard');
    Route::post('/super-admin/create-department', [SuperAdminDashboardController::class, 'createDepartment'])->name('super-admin.create-department');
    Route::post('/super-admin/department/{id}/update', [SuperAdminDashboardController::class, 'updateDepartment'])->name('super-admin.update-department');
    Route::post('/super-admin/create-owner-department', [SuperAdminDashboardController::class, 'createOwnerDepartment'])->name('super-admin.create-owner-department');
    Route::post('/super-admin/create-ngo', [SuperAdminDashboardController::class, 'createNgo'])->name('super-admin.create-ngo');
    Route::post('/super-admin/create-employer', [SuperAdminDashboardController::class, 'createEmployer'])->name('super-admin.create-employer');
    Route::post('/super-admin/create-contractor', [SuperAdminDashboardController::class, 'createContractor'])->name('super-admin.create-contractor');
    Route::post('/super-admin/user/{id}/toggle-status', [SuperAdminDashboardController::class, 'toggleStatus'])->name('super-admin.toggle-status');
    Route::post('/super-admin/user/{id}/change-password', [SuperAdminDashboardController::class, 'changePassword'])->name('super-admin.change-password');
    Route::delete('/super-admin/user/{id}', [SuperAdminDashboardController::class, 'deleteUser'])->name('super-admin.delete-user');
    Route::get('/super-admin/trades', [SuperAdminDashboardController::class, 'tradesIndex'])->name('super-admin.trades');
    Route::post('/super-admin/trades', [SuperAdminDashboardController::class, 'createTrade'])->name('super-admin.create-trade');
    Route::post('/super-admin/trades/{id}/update', [SuperAdminDashboardController::class, 'updateTrade'])->name('super-admin.update-trade');
    Route::delete('/super-admin/trades/{id}', [SuperAdminDashboardController::class, 'deleteTrade'])->name('super-admin.delete-trade');

    // Activity Logs
    Route::get('/admin/activity-logs', [\App\Http\Controllers\Web\ActivityLogController::class, 'index'])->name('admin.activity-logs');

    // ── Government Department Admin Routes ────────────────────────────────────
    Route::get('/govt-dept-admin', [GovtDeptAdminController::class, 'index'])->name('govt-dept-admin.index');
    Route::get('/govt-dept-admin/users', [GovtDeptAdminController::class, 'users'])->name('govt-dept-admin.users');
    Route::post('/govt-dept-admin/create-verifier', [GovtDeptAdminController::class, 'createVerifier'])->name('govt-dept-admin.create-verifier');
    Route::post('/govt-dept-admin/create-approver', [GovtDeptAdminController::class, 'createApprover'])->name('govt-dept-admin.create-approver');
    Route::post('/govt-dept-admin/user/{id}/toggle-status', [GovtDeptAdminController::class, 'toggleStatus'])->name('govt-dept-admin.toggle-status');
    Route::delete('/govt-dept-admin/user/{id}', [GovtDeptAdminController::class, 'deleteUser'])->name('govt-dept-admin.delete-user');
    Route::get('/govt-dept-admin/schemes', [GovtDeptAdminController::class, 'schemes'])->name('govt-dept-admin.schemes');
    Route::post('/govt-dept-admin/schemes', [GovtDeptAdminController::class, 'createScheme'])->name('govt-dept-admin.create-scheme');
    Route::post('/govt-dept-admin/schemes/{id}/update', [GovtDeptAdminController::class, 'updateScheme'])->name('govt-dept-admin.update-scheme');
    Route::get('/govt-dept-admin/schemes/export', [GovtDeptAdminController::class, 'exportSchemesCsv'])->name('govt-dept-admin.export-schemes');
    Route::delete('/govt-dept-admin/schemes/{id}', [GovtDeptAdminController::class, 'deleteScheme'])->name('govt-dept-admin.delete-scheme');
    Route::get('/owner-dept/beneficiaries', [GovtDeptAdminController::class, 'beneficiaries'])->name('owner-dept.beneficiaries');
    Route::post('/owner-dept/beneficiary/{id}/update', [GovtDeptAdminController::class, 'updateBeneficiary'])->name('owner-dept.update-beneficiary');
    Route::get('/owner-dept/export-csv', [GovtDeptAdminController::class, 'exportLabourCsv'])->name('owner-dept.export-csv');
    Route::get('/owner-dept/onboard', [GovtDeptAdminController::class, 'onboardHub'])->name('owner-dept.onboard');
    Route::get('/owner-dept/review-registration/{id}', [GovtDeptAdminController::class, 'reviewRegistration'])->name('owner-dept.review-registration')->middleware('signed');
    Route::post('/owner-dept/review-registration/{id}/approve', [GovtDeptAdminController::class, 'approveRegistration'])->name('owner-dept.approve-registration');
    Route::post('/owner-dept/review-registration/{id}/reject', [GovtDeptAdminController::class, 'rejectRegistration'])->name('owner-dept.reject-registration');
    Route::post('/owner-dept/create-ngo', [GovtDeptAdminController::class, 'createNgo'])->name('owner-dept.create-ngo');
    Route::post('/owner-dept/create-employer', [GovtDeptAdminController::class, 'createEmployer'])->name('owner-dept.create-employer');
    Route::post('/owner-dept/create-contractor', [GovtDeptAdminController::class, 'createContractor'])->name('owner-dept.create-contractor');
    Route::post('/owner-dept/create-beneficiary', [GovtDeptAdminController::class, 'createBeneficiary'])->name('owner-dept.create-beneficiary');
    Route::get('/owner-dept/view-logs', [GovtDeptAdminController::class, 'viewLogs'])->name('owner-dept.view-logs');
    Route::post('/owner-dept/verify-ration-card', [GovtDeptAdminController::class, 'verifyRationCard'])->name('owner-dept.verify-ration-card');

    // ── Department Verifier Routes (dept-verifier) ────────────────────────────
    Route::get('/dept-verifier', [VerifierDashboardController::class, 'index'])->name('dept-verifier.index');
    Route::get('/dept-verifier/beneficiaries', [VerifierDashboardController::class, 'beneficiaries'])->name('dept-verifier.beneficiaries');
    Route::get('/dept-verifier/schemes', [VerifierDashboardController::class, 'schemes'])->name('dept-verifier.schemes');
    Route::get('/dept-verifier/scheme-applications', [VerifierDashboardController::class, 'schemeApplications'])->name('dept-verifier.scheme-applications');
    Route::get('/dept-verifier/grievances', [VerifierDashboardController::class, 'grievances'])->name('dept-verifier.grievances');
    Route::get('/dept-verifier/review/{id}', [VerifierDashboardController::class, 'review'])->name('dept-verifier.review');
    Route::post('/dept-verifier/review/{id}/verify', [VerifierDashboardController::class, 'verify'])->name('dept-verifier.verify');
    Route::post('/dept-verifier/review/{id}/reject', [VerifierDashboardController::class, 'reject'])->name('dept-verifier.reject');

    // ── Department Approver Routes (dept-approver) — shares approver dashboard
    Route::get('/dept-approver', [ApproverDashboardController::class, 'index'])->name('dept-approver.index');
    Route::get('/dept-approver/beneficiaries', [ApproverDashboardController::class, 'beneficiaries'])->name('dept-approver.beneficiaries');
    Route::get('/dept-approver/schemes', [ApproverDashboardController::class, 'schemes'])->name('dept-approver.schemes');
    Route::get('/dept-approver/scheme-applications', [ApproverDashboardController::class, 'schemeApplications'])->name('dept-approver.scheme-applications');
    Route::get('/dept-approver/grievances', [ApproverDashboardController::class, 'grievances'])->name('dept-approver.grievances');
    Route::get('/dept-approver/review/{id}', [ApproverDashboardController::class, 'review'])->name('dept-approver.review');
    Route::post('/dept-approver/review/{id}/approve', [ApproverDashboardController::class, 'approve'])->name('dept-approver.approve');
    Route::post('/dept-approver/review/{id}/reject', [ApproverDashboardController::class, 'reject'])->name('dept-approver.reject');

    // Verifier Dashboard routes
    Route::get('/verifier', [VerifierDashboardController::class, 'index'])->name('verifier.index');
    Route::get('/verifier/beneficiaries', [VerifierDashboardController::class, 'beneficiaries'])->name('verifier.beneficiaries');
    Route::get('/verifier/schemes', [VerifierDashboardController::class, 'schemes'])->name('verifier.schemes');
    Route::get('/verifier/scheme-applications', [VerifierDashboardController::class, 'schemeApplications'])->name('verifier.scheme-applications');
    Route::get('/verifier/grievances', [VerifierDashboardController::class, 'grievances'])->name('verifier.grievances');
    Route::get('/verifier/review/{id}', [VerifierDashboardController::class, 'review'])->name('verifier.review');
    Route::post('/verifier/review/{id}/verify', [VerifierDashboardController::class, 'verify'])->name('verifier.verify');
    Route::post('/verifier/review/{id}/reject', [VerifierDashboardController::class, 'reject'])->name('verifier.reject');

    // Approver Dashboard routes
    Route::get('/approver', [ApproverDashboardController::class, 'index'])->name('approver.index');
    Route::get('/approver/beneficiaries', [ApproverDashboardController::class, 'beneficiaries'])->name('approver.beneficiaries');
    Route::post('/approver/beneficiaries', [ApproverDashboardController::class, 'createBeneficiary'])->name('approver.create-beneficiary');
    Route::delete('/approver/beneficiaries/{id}', [ApproverDashboardController::class, 'deleteBeneficiary'])->name('approver.delete-beneficiary');
    Route::post('/approver/beneficiaries/{id}/toggle-status', [ApproverDashboardController::class, 'toggleBeneficiaryStatus'])->name('approver.toggle-beneficiary-status');
    
    Route::get('/approver/ngos', [ApproverDashboardController::class, 'ngos'])->name('approver.ngos');
    Route::post('/approver/ngos', [ApproverDashboardController::class, 'createNgo'])->name('approver.create-ngo');
    Route::delete('/approver/ngos/{id}', [ApproverDashboardController::class, 'deleteNgo'])->name('approver.delete-ngo');
    Route::post('/approver/ngos/{id}/toggle-status', [ApproverDashboardController::class, 'toggleNgoStatus'])->name('approver.toggle-ngo-status');

    Route::get('/approver/employers', [ApproverDashboardController::class, 'employers'])->name('approver.employers');
    Route::post('/approver/employers', [ApproverDashboardController::class, 'createEmployer'])->name('approver.create-employer');
    Route::delete('/approver/employers/{id}', [ApproverDashboardController::class, 'deleteEmployer'])->name('approver.delete-employer');
    Route::post('/approver/employers/{id}/toggle-status', [ApproverDashboardController::class, 'toggleEmployerStatus'])->name('approver.toggle-employer-status');

    Route::get('/approver/contractors', [ApproverDashboardController::class, 'contractors'])->name('approver.contractors');
    Route::post('/approver/contractors', [ApproverDashboardController::class, 'createContractor'])->name('approver.create-contractor');
    Route::delete('/approver/contractors/{id}', [ApproverDashboardController::class, 'deleteContractor'])->name('approver.delete-contractor');
    Route::post('/approver/contractors/{id}/toggle-status', [ApproverDashboardController::class, 'toggleContractorStatus'])->name('approver.toggle-contractor-status');

    Route::get('/approver/schemes', [ApproverDashboardController::class, 'schemes'])->name('approver.schemes');
    Route::get('/approver/scheme-applications', [ApproverDashboardController::class, 'schemeApplications'])->name('approver.scheme-applications');
    Route::get('/approver/grievances', [ApproverDashboardController::class, 'grievances'])->name('approver.grievances');
    Route::get('/approver/review/{id}', [ApproverDashboardController::class, 'review'])->name('approver.review');
    Route::post('/approver/review/{id}/approve', [ApproverDashboardController::class, 'approve'])->name('approver.approve');
    Route::post('/approver/review/{id}/reject', [ApproverDashboardController::class, 'reject'])->name('approver.reject');

    // NGO Dashboard routes
    Route::get('/ngo', [NgoDashboardController::class, 'index'])->name('ngo.index');
    Route::get('/ngo/users', [NgoDashboardController::class, 'users'])->name('ngo.users');
    Route::post('/ngo/create-verifier', [NgoDashboardController::class, 'createVerifier'])->name('ngo.create-verifier');
    Route::post('/ngo/create-approver', [NgoDashboardController::class, 'createApprover'])->name('ngo.create-approver');
    Route::post('/ngo/user/{id}/toggle-status', [NgoDashboardController::class, 'toggleStatus'])->name('ngo.toggle-status');
    Route::delete('/ngo/user/{id}', [NgoDashboardController::class, 'deleteUser'])->name('ngo.delete-user');
    Route::get('/ngo/employers', [NgoDashboardController::class, 'employers'])->name('ngo.employers');
    Route::post('/ngo/employers', [NgoDashboardController::class, 'createEmployer'])->name('ngo.create-employer');
    Route::delete('/ngo/employers/{id}', [NgoDashboardController::class, 'deleteEmployer'])->name('ngo.delete-employer');
    Route::post('/ngo/employers/{id}/toggle-status', [NgoDashboardController::class, 'toggleEmployerStatus'])->name('ngo.toggle-employer-status');
    Route::get('/ngo/contractors', [NgoDashboardController::class, 'contractors'])->name('ngo.contractors');
    Route::post('/ngo/contractors', [NgoDashboardController::class, 'createContractor'])->name('ngo.create-contractor');
    Route::delete('/ngo/contractors/{id}', [NgoDashboardController::class, 'deleteContractor'])->name('ngo.delete-contractor');
    Route::post('/ngo/contractors/{id}/toggle-status', [NgoDashboardController::class, 'toggleContractorStatus'])->name('ngo.toggle-contractor-status');
    Route::get('/ngo/beneficiaries', [NgoDashboardController::class, 'beneficiaries'])->name('ngo.beneficiaries');
    Route::post('/ngo/beneficiaries', [NgoDashboardController::class, 'createBeneficiary'])->name('ngo.create-beneficiary');
    Route::post('/ngo/verify-ration-card', [NgoDashboardController::class, 'verifyRationCard'])->name('ngo.verify-ration-card');
    Route::post('/ngo/beneficiary/{id}/update', [NgoDashboardController::class, 'updateBeneficiary'])->name('ngo.update-beneficiary');
    Route::delete('/ngo/beneficiaries/{id}', [NgoDashboardController::class, 'deleteBeneficiary'])->name('ngo.delete-beneficiary');
    Route::post('/ngo/beneficiaries/{id}/toggle-status', [NgoDashboardController::class, 'toggleBeneficiaryStatus'])->name('ngo.toggle-beneficiary-status');
    Route::get('/ngo/download-template', [NgoDashboardController::class, 'downloadBeneficiaryTemplate'])->name('ngo.download-template');
    Route::post('/ngo/import-beneficiaries', [NgoDashboardController::class, 'importBeneficiaries'])->name('ngo.import-beneficiaries');
    Route::get('/ngo/schemes', [NgoDashboardController::class, 'schemes'])->name('ngo.schemes');
    Route::post('/ngo/schemes', [NgoDashboardController::class, 'createScheme'])->name('ngo.create-scheme');
    Route::delete('/ngo/schemes/{id}', [NgoDashboardController::class, 'deleteScheme'])->name('ngo.delete-scheme');

    // ── NGO Verifier Routes ──────────────────────────────────────────────────
    Route::get('/ngo-verifier', [\App\Http\Controllers\Web\NgoVerifierController::class, 'index'])->name('ngo-verifier.index');
    Route::get('/ngo-verifier/scheme-applications', [\App\Http\Controllers\Web\NgoVerifierController::class, 'schemeApplications'])->name('ngo-verifier.scheme-applications');
    Route::get('/ngo-verifier/review/{id}', [\App\Http\Controllers\Web\NgoVerifierController::class, 'review'])->name('ngo-verifier.review');
    Route::post('/ngo-verifier/review/{id}/verify', [\App\Http\Controllers\Web\NgoVerifierController::class, 'verify'])->name('ngo-verifier.verify');
    Route::post('/ngo-verifier/review/{id}/reject', [\App\Http\Controllers\Web\NgoVerifierController::class, 'reject'])->name('ngo-verifier.reject');

    // ── NGO Approver Routes ──────────────────────────────────────────────────
    Route::get('/ngo-approver', [\App\Http\Controllers\Web\NgoApproverController::class, 'index'])->name('ngo-approver.index');
    Route::get('/ngo-approver/beneficiaries', [\App\Http\Controllers\Web\NgoApproverController::class, 'beneficiaries'])->name('ngo-approver.beneficiaries');
    Route::get('/ngo-approver/review-beneficiary/{id}', [\App\Http\Controllers\Web\NgoApproverController::class, 'reviewBeneficiary'])->name('ngo-approver.review-beneficiary');
    Route::post('/ngo-approver/review-beneficiary/{id}/approve', [\App\Http\Controllers\Web\NgoApproverController::class, 'approveBeneficiary'])->name('ngo-approver.approve-beneficiary');
    Route::post('/ngo-approver/review-beneficiary/{id}/reject', [\App\Http\Controllers\Web\NgoApproverController::class, 'rejectBeneficiary'])->name('ngo-approver.reject-beneficiary');
    Route::get('/ngo-approver/scheme-applications', [\App\Http\Controllers\Web\NgoApproverController::class, 'schemeApplications'])->name('ngo-approver.scheme-applications');
    Route::get('/ngo-approver/review-scheme/{id}', [\App\Http\Controllers\Web\NgoApproverController::class, 'reviewScheme'])->name('ngo-approver.review-scheme');
    Route::post('/ngo-approver/review-scheme/{id}/approve', [\App\Http\Controllers\Web\NgoApproverController::class, 'approveScheme'])->name('ngo-approver.approve-scheme');
    Route::post('/ngo-approver/review-scheme/{id}/reject', [\App\Http\Controllers\Web\NgoApproverController::class, 'rejectScheme'])->name('ngo-approver.reject-scheme');

    // Employer Dashboard routes
    Route::get('/employer', [EmployerDashboardController::class, 'index'])->name('employer.index');
    Route::get('/employer/contractors', [EmployerDashboardController::class, 'contractors'])->name('employer.contractors');
    Route::post('/employer/contractors', [EmployerDashboardController::class, 'createContractor'])->name('employer.create-contractor');
    Route::delete('/employer/contractors/{id}', [EmployerDashboardController::class, 'deleteContractor'])->name('employer.delete-contractor');
    Route::post('/employer/contractors/{id}/toggle-status', [EmployerDashboardController::class, 'toggleContractorStatus'])->name('employer.toggle-contractor-status');
    Route::get('/employer/beneficiaries', [EmployerDashboardController::class, 'beneficiaries'])->name('employer.beneficiaries');
    Route::post('/employer/beneficiaries', [EmployerDashboardController::class, 'createBeneficiary'])->name('employer.create-beneficiary');
    Route::delete('/employer/beneficiaries/{id}', [EmployerDashboardController::class, 'deleteBeneficiary'])->name('employer.delete-beneficiary');
    Route::post('/employer/beneficiaries/{id}/toggle-status', [EmployerDashboardController::class, 'toggleBeneficiaryStatus'])->name('employer.toggle-beneficiary-status');
    Route::get('/employer/download-template', [EmployerDashboardController::class, 'downloadBeneficiaryTemplate'])->name('employer.download-template');
    Route::post('/employer/import-beneficiaries', [EmployerDashboardController::class, 'importBeneficiaries'])->name('employer.import-beneficiaries');
    Route::post('/employer/verify-ration-card', [EmployerDashboardController::class, 'verifyRationCard'])->name('employer.verify-ration-card');
    Route::post('/employer/beneficiary/{id}/update', [EmployerDashboardController::class, 'updateBeneficiary'])->name('employer.update-beneficiary');

    // Contractor Dashboard routes
    Route::get('/contractor', [ContractorDashboardController::class, 'index'])->name('contractor.index');
    Route::get('/contractor/beneficiaries', [ContractorDashboardController::class, 'beneficiaries'])->name('contractor.beneficiaries');
    Route::post('/contractor/beneficiaries', [ContractorDashboardController::class, 'createBeneficiary'])->name('contractor.create-beneficiary');
    Route::post('/contractor/verify-ration-card', [ContractorDashboardController::class, 'verifyRationCard'])->name('contractor.verify-ration-card');
    Route::post('/contractor/beneficiary/{id}/update', [ContractorDashboardController::class, 'updateBeneficiary'])->name('contractor.update-beneficiary');
    Route::delete('/contractor/beneficiaries/{id}', [ContractorDashboardController::class, 'deleteBeneficiary'])->name('contractor.delete-beneficiary');
    Route::post('/contractor/beneficiaries/{id}/toggle-status', [ContractorDashboardController::class, 'toggleBeneficiaryStatus'])->name('contractor.toggle-beneficiary-status');
    Route::get('/contractor/download-template', [ContractorDashboardController::class, 'downloadBeneficiaryTemplate'])->name('contractor.download-template');
    Route::post('/contractor/import-beneficiaries', [ContractorDashboardController::class, 'importBeneficiaries'])->name('contractor.import-beneficiaries');
    
    // Secure Storage asset viewer (serving uploaded photos / signatures)
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
    })->name('storage.serve');
});
