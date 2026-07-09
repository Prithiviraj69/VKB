<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class OtpLog extends Model
{
    protected $fillable = [
        'mobile', 'otp_hash', 'purpose', 'expires_at', 'used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];
}
