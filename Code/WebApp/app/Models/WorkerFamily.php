<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class WorkerFamily extends Model
{
    protected $table = 'worker_family';

    protected $fillable = [
        'worker_id', 'name', 'relation', 'dob',
        'aadhaar_last4', 'mobile', 'is_dependent',
        'is_migrating', 'gender', 'age'
    ];

    protected $casts = [
        'dob'          => 'date',
        'is_dependent' => 'boolean',
        'is_migrating' => 'boolean',
        'age'          => 'integer',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
