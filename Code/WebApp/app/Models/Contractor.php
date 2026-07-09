<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Contractor extends Model
{
    use HasUuids;

    protected $table = 'contractors';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'contractor_name', 'license_no', 'office_address', 'email',
        'mobile', 'created_by', 'ngo_id', 'created_by_dept_code', 'created_by_ngo_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
