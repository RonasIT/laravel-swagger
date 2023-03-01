<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Database\Eloquent\Model;

class TestNotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'is_email_enabled',
        'is_push_enabled',
        'is_sms_enabled'
    ];

    protected $hidden = ['pivot'];

    protected $casts = [
        'is_email_enabled' => 'boolean',
        'is_push_enabled' => 'boolean',
        'is_sms_enabled' => 'boolean'
    ];
}
