<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'mobile', 'aadhaar_hash', 'aadhaar_token', 'role',
        'is_active', 'is_verified', 'last_login',
        'name', 'email', 'password', 'registered_by',
        'department_code', 'designation', 'address', 'reg_no', 'ngo_id'
    ];

    protected $hidden = ['aadhaar_hash', 'aadhaar_token'];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_verified' => 'boolean',
        'last_login'  => 'datetime',
    ];

    public function worker()
    {
        return $this->hasOne(Worker::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
