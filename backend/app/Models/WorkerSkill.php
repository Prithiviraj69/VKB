<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class WorkerSkill extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'worker_id', 'trade_id', 'experience_years', 'skill_level',
        'certification', 'certified_by', 'valid_until',
    ];

    protected $casts = [
        'valid_until'      => 'date',
        'experience_years' => 'integer',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }
}
