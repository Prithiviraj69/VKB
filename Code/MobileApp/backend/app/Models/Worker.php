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
        'religion', 'marital_status', 'education', 'education_status', 'aadhaar_number',
        'pan_number', 'voter_id', 'eshram_number', 'bocw_card_number',
        'mobile_alt', 'email', 'photo_url', 'profile_score',
        'is_verified', 'verified_by', 'verified_at', 'verifier_remarks',
        'department_code',
        'verified_by_dept',
        'approved_by', 'approved_at', 'approved_by_dept', 'approver_remarks',
        'status',
        'aadhaar_address', 'ration_card_number', 'local_address',
        'identification_mark', 'job_profile', 'employer_name',
        'employment_site', 'bank_account_number', 'bank_name_branch',
        'bank_ifsc', 'signature_url', 'registration_no',
        'registration_source', 'is_active',
        'date_first_subscription', 'date_last_subscription',
        'installments_paid_count', 'installments_paid_total',
        'is_membership_revived', 'revival_period',
        'employment_status', 'is_bocw_registered', 'bocw_reg_no',
        'bocw_state_board', 'bocw_reg_date', 'bocw_valid_upto',
        'business_name', 'business_address', 'business_category',
        'family_members', 'created_by', 'ngo_id', 'created_by_ngo_id',
    ];

    protected $casts = [
        'dob'         => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'profile_score' => 'integer',
        'date_first_subscription' => 'date',
        'date_last_subscription' => 'date',
        'installments_paid_count' => 'integer',
        'installments_paid_total' => 'integer',
        'is_membership_revived' => 'boolean',
        'is_bocw_registered' => 'boolean',
        'bocw_reg_date' => 'date',
        'bocw_valid_upto' => 'date',
        'family_members'  => 'array',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->hasMany(WorkerSkill::class);
    }

    public function documents()
    {
        return $this->hasMany(WorkerDocument::class);
    }

    public function grievances()
    {
        return $this->hasMany(Grievance::class);
    }

    public function schemeApplications()
    {
        return $this->hasMany(SchemeApplication::class);
    }

    public function addresses()
    {
        return $this->hasMany(WorkerAddress::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(WorkerBankAccount::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(WorkerFamily::class);
    }
}
