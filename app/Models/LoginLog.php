<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $fillable = [
        'access_session_id',
        'email',
        'name',
        'password',
        'ip',
        'isp',
        'country',
        'country_code',
        'city',
        'user_agent',
        'logged_in_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
        ];
    }
}
