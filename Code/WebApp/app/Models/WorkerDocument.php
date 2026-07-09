<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class WorkerDocument extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'worker_id', 'doc_type', 'doc_number', 'file_url',
        'digilocker_uri', 'is_verified', 'verified_at', 'uploaded_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'uploaded_at' => 'datetime',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
