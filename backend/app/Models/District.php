<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class District extends Model
{
    public $timestamps = false;

    protected $fillable = ['state_id', 'code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
