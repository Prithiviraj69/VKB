<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class State extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function districts()
    {
        return $this->hasMany(District::class);
    }
}
