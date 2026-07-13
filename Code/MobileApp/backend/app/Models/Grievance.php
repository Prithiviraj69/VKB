<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Grievance extends Model
{
    protected $fillable = [
        'ticket_number', 'worker_id', 'category', 'subject',
        'description', 'status', 'priority', 'assigned_to',
        'resolved_at', 'resolution_note',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function comments()
    {
        return $this->hasMany(GrievanceComment::class);
    }
}
