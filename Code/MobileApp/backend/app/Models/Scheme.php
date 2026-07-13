<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Scheme extends Model
{
    protected $fillable = [
        'scheme_code', 'name', 'short_name', 'ministry', 'level',
        'state_id', 'description', 'benefit_description',
        'max_benefit_amount', 'eligibility_rules', 'required_documents',
        'apply_url', 'api_endpoint', 'is_active',
        'created_by', 'creator_role', 'creator_name', 'department_code', 'ngo_id',
        'status', 'is_verified', 'verified_by', 'verified_at', 'verifier_remarks',
        'is_approved', 'approved_by', 'approved_at', 'approver_remarks',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'eligibility_rules'   => 'array',
        'required_documents'  => 'array',
        'max_benefit_amount'  => 'decimal:2',
    ];

    public function applications()
    {
        return $this->hasMany(SchemeApplication::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
