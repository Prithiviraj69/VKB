<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->text('aadhaar_address')->nullable();
            $table->string('ration_card_number', 50)->nullable();
            $table->text('local_address')->nullable();
            $table->string('identification_mark', 255)->nullable();
            $table->string('job_profile', 150)->nullable();
            $table->string('employer_name', 255)->nullable();
            $table->string('employment_site', 255)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_name_branch', 255)->nullable();
            $table->string('bank_ifsc', 20)->nullable();
            $table->string('signature_url', 500)->nullable();
            $table->string('registration_no', 50)->nullable();
            $table->date('date_first_subscription')->nullable();
            $table->date('date_last_subscription')->nullable();
            $table->integer('installments_paid_count')->default(0);
            $table->integer('installments_paid_total')->default(0);
            $table->boolean('is_membership_revived')->default(false);
            $table->string('revival_period', 100)->nullable();
        });

        Schema::table('worker_family', function (Blueprint $table) {
            $table->string('gender', 20)->nullable();
            $table->integer('age')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn([
                'aadhaar_address', 'ration_card_number', 'local_address',
                'identification_mark', 'job_profile', 'employer_name',
                'employment_site', 'bank_account_number', 'bank_name_branch',
                'bank_ifsc', 'signature_url', 'registration_no',
                'date_first_subscription', 'date_last_subscription',
                'installments_paid_count', 'installments_paid_total',
                'is_membership_revived', 'revival_period'
            ]);
        });

        Schema::table('worker_family', function (Blueprint $table) {
            $table->dropColumn(['gender', 'age']);
        });
    }
};
