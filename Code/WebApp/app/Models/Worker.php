<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Worker extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'worker_code', 'full_name', 'full_name_local',
        'father_name', 'mother_name', 'dob', 'gender', 'category',
        'religion', 'marital_status', 'education', 'aadhaar_number',
        'pan_number', 'voter_id', 'eshram_number', 'bocw_card_number',
        'mobile_alt', 'email', 'photo_url', 'profile_score',
        'is_verified', 'verified_by', 'verified_at',
        'aadhaar_address', 'ration_card_number', 'local_address',
        'identification_mark', 'job_profile', 'employer_name',
        'employment_site', 'bank_account_number', 'bank_name_branch',
        'bank_ifsc', 'signature_url', 'registration_no',
        'date_first_subscription', 'date_last_subscription',
        'installments_paid_count', 'installments_paid_total',
        'is_membership_revived', 'revival_period',
        'employment_status', 'is_bocw_registered', 'bocw_reg_no',
        'bocw_state_board', 'bocw_reg_date', 'bocw_valid_upto',
        'district', 'state', 'qualification', 'school_institution',
        'experience_years', 'daily_wage'
    ];

    protected $casts = [
        'dob'         => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'profile_score' => 'integer',
        'date_first_subscription' => 'date',
        'date_last_subscription' => 'date',
        'installments_paid_count' => 'integer',
        'installments_paid_total' => 'integer',
        'is_membership_revived' => 'boolean',
        'is_bocw_registered' => 'boolean',
        'bocw_reg_date' => 'date',
        'bocw_valid_upto' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function syncFamilyMembers($workerId, array $familyMembers)
    {
        if (empty($workerId)) return;

        // Clear existing family members to avoid duplicates
        \Illuminate\Support\Facades\DB::table('worker_family')->where('worker_id', (string)$workerId)->delete();

        // Insert new ones
        foreach ($familyMembers as $member) {
            \Illuminate\Support\Facades\DB::table('worker_family')->insert([
                'worker_id' => (string)$workerId,
                'name' => $member['name'] ?? '',
                'relation' => $member['relation'] ?? 'Member',
                'gender' => $member['gender'] ?? 'male',
                'age' => isset($member['age']) ? (int)$member['age'] : null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        }
    }

    public static function getFamilyMembers($workerId, $fallbackArray = [])
    {
        if (empty($workerId)) return $fallbackArray;

        $family = \Illuminate\Support\Facades\DB::table('worker_family')
            ->where('worker_id', (string)$workerId)
            ->get(['name', 'relation', 'gender', 'age'])
            ->map(fn($f) => [
                'name' => $f->name ?? '',
                'relation' => $f->relation ?? 'Member',
                'gender' => $f->gender ?? 'male',
                'age' => $f->age ?? 0,
            ])
            ->toArray();

        return !empty($family) ? $family : $fallbackArray;
    }
}
