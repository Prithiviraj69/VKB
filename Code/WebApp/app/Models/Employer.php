<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Employer extends Model
{
    use HasUuids;

    protected $table = 'employers';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employer_name', 'registration_no', 'office_address', 'email',
        'contact_person', 'mobile', 'created_by', 'ngo_id', 'created_by_dept_code', 'created_by_ngo_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
