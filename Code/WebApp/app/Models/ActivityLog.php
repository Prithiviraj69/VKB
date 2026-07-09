<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ActivityLog extends Model
{
    protected $collection = 'activity_logs';

    protected $fillable = [
        'user_id', 'user_name', 'user_email', 'user_role',
        'action', 'resource_type', 'resource_id', 'resource_name',
        'details', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
