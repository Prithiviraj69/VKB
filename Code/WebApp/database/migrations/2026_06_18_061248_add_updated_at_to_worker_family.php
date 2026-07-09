<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worker_family', function (Blueprint $table) {
            // Add missing updated_at so Eloquent timestamps work
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('worker_family', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }
};
