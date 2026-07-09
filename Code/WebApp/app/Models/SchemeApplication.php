<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class SchemeApplication extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'application_number', 'worker_id', 'scheme_id', 'status',
        'applied_at', 'submitted_data', 'govt_reference_no', 'remarks',
        'reviewed_by', 'reviewed_at', 'approved_at', 'rejected_reason',
    ];

    protected $casts = [
        'submitted_data' => 'array',
        'applied_at'     => 'datetime',
        'reviewed_at'    => 'datetime',
        'approved_at'    => 'datetime',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function scheme()
    {
        return $this->belongsTo(Scheme::class);
    }
}
