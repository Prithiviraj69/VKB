<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add department audit fields to workers table.
     * - department_code: which department this worker belongs to (set when verifier verifies)
     * - verified_by_dept: department_code of the verifier who verified
     * - approved_by_dept: department_code of the approver who approved
     */
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            if (!Schema::hasColumn('workers', 'department_code')) {
                $table->string('department_code')->nullable()->after('is_verified');
            }
            if (!Schema::hasColumn('workers', 'verified_by_dept')) {
                $table->string('verified_by_dept')->nullable()->after('verified_at');
            }
            if (!Schema::hasColumn('workers', 'approved_by')) {
                $table->string('approved_by')->nullable()->after('verified_by_dept');
            }
            if (!Schema::hasColumn('workers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('workers', 'approved_by_dept')) {
                $table->string('approved_by_dept')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('workers', 'approver_remarks')) {
                $table->text('approver_remarks')->nullable()->after('approved_by_dept');
            }
            if (!Schema::hasColumn('workers', 'verifier_remarks')) {
                $table->text('verifier_remarks')->nullable()->after('approver_remarks');
            }
            if (!Schema::hasColumn('workers', 'status')) {
                $table->string('status')->nullable()->after('verifier_remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn([
                'department_code',
                'verified_by_dept',
                'approved_by_dept',
            ]);
        });
    }
};
