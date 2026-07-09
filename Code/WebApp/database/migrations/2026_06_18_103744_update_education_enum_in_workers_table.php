<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change education ENUM to include new values
        DB::statement("ALTER TABLE workers MODIFY COLUMN education ENUM(
            'Not Educated',
            'Illiterate',
            'Primary',
            'Secondary',
            '10th',
            'Higher Secondary',
            '12th',
            'Diploma',
            'Graduate',
            'Post Graduate',
            'Other'
        ) NULL");

        // Also add employment_status and BOCW fields if not already present
        $columns = DB::select("SHOW COLUMNS FROM workers");
        $colNames = array_column($columns, 'Field');

        if (!in_array('employment_status', $colNames)) {
            DB::statement("ALTER TABLE workers ADD COLUMN employment_status ENUM('Employed','Not Employed') NULL DEFAULT 'Employed' AFTER employment_site");
        }
        if (!in_array('is_bocw_registered', $colNames)) {
            DB::statement("ALTER TABLE workers ADD COLUMN is_bocw_registered TINYINT(1) NOT NULL DEFAULT 0 AFTER employment_status");
        }
        if (!in_array('bocw_reg_no', $colNames)) {
            DB::statement("ALTER TABLE workers ADD COLUMN bocw_reg_no VARCHAR(100) NULL AFTER is_bocw_registered");
        }
        if (!in_array('bocw_state_board', $colNames)) {
            DB::statement("ALTER TABLE workers ADD COLUMN bocw_state_board VARCHAR(200) NULL AFTER bocw_reg_no");
        }
        if (!in_array('bocw_reg_date', $colNames)) {
            DB::statement("ALTER TABLE workers ADD COLUMN bocw_reg_date DATE NULL AFTER bocw_state_board");
        }
        if (!in_array('bocw_valid_upto', $colNames)) {
            DB::statement("ALTER TABLE workers ADD COLUMN bocw_valid_upto DATE NULL AFTER bocw_reg_date");
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE workers MODIFY COLUMN education ENUM(
            'Illiterate','Primary','Secondary','Higher Secondary','Graduate','Post Graduate'
        ) NULL");

        DB::statement("ALTER TABLE workers DROP COLUMN IF EXISTS employment_status");
        DB::statement("ALTER TABLE workers DROP COLUMN IF EXISTS is_bocw_registered");
        DB::statement("ALTER TABLE workers DROP COLUMN IF EXISTS bocw_reg_no");
        DB::statement("ALTER TABLE workers DROP COLUMN IF EXISTS bocw_state_board");
        DB::statement("ALTER TABLE workers DROP COLUMN IF EXISTS bocw_reg_date");
        DB::statement("ALTER TABLE workers DROP COLUMN IF EXISTS bocw_valid_upto");
    }
};
