<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Trade extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'category', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function skills()
    {
        return $this->hasMany(WorkerSkill::class);
    }
}
