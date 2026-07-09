<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Department extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'department_code', 'department_name', 'parent_department_name',
        'office_address', 'email', 'logo_path',
    ];

    protected $casts = [
        'department_code' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'department_code', 'department_code');
    }

    public function admin()
    {
        return $this->hasOne(User::class, 'department_code', 'department_code')
            ->where('role', 'govt-dept-admin');
    }
}
