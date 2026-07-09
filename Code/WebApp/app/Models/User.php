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
        'name', 'email', 'password', 'mobile', 'aadhaar_hash', 'aadhaar_token', 'role',
        'is_active', 'is_verified', 'last_login', 'department_code', 'created_by', 'designation', 'ngo_id',
        'created_by_dept_code', 'created_by_ngo_id', 'needs_password_change', 'address', 'job_profile', 'trade',
    ];

    protected $hidden = ['password', 'aadhaar_hash', 'aadhaar_token'];

    protected $casts = [
        'is_active'             => 'boolean',
        'is_verified'           => 'boolean',
        'last_login'            => 'datetime',
        'needs_password_change' => 'boolean',
    ];

    public function worker()
    {
        return $this->hasOne(Worker::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_code', 'department_code');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (in_array($user->role, ['beneficiary', 'worker'])) {
                return;
            }
            if (!isset($user->needs_password_change)) {
                if (\Illuminate\Support\Facades\Auth::check()) {
                    $user->needs_password_change = true;
                } else {
                    $user->needs_password_change = false;
                }
            }
        });
    }
}
