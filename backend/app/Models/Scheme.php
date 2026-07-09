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
        'title', 'url', 'created_by', 'created_by_dept_code'
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'eligibility_rules'   => 'array',
        'required_documents'  => 'array',
        'max_benefit_amount'  => 'decimal:2',
    ];

    protected $appends = ['department_detail'];

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

    public function getDepartmentDetailAttribute()
    {
        $code = $this->created_by_dept_code;
        if (!$code) {
            return null;
        }
        return \App\Models\Department::where('department_code', (string)$code)
            ->orWhere('department_code', (int)$code)
            ->first();
    }
}
