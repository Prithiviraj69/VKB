<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ngo extends Model
{
    use HasUuids;

    protected $table = 'ngos';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ngo_name', 'registration_no', 'office_address', 'email',
        'contact_person', 'mobile', 'created_by', 'created_by_dept_code'
    ];
}
