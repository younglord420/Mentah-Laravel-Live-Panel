<?php

namespace App\Models;

use App\Support\TrafficLog;
use Illuminate\Database\Eloquent\Model;

class BlockLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip',
        'reason',
        'detail',
        'isp',
        'country',
        'country_code',
        'city',
        'path',
        'method',
        'user_agent',
        'blocked_at',
    ];

    protected function casts(): array
    {
        return [
            'blocked_at' => 'datetime',
        ];
    }

    public static function reasonLabel(string $reason): string
    {
        return TrafficLog::reasonLabel($reason);
    }
}
