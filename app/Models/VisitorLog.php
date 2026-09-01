<?php

namespace App\Models;

use App\Support\TrafficLog;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
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
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    public static function reasonLabel(string $reason): string
    {
        return TrafficLog::reasonLabel($reason);
    }
}
