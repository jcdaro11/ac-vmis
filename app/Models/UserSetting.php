<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_email_enabled',
        'notify_approvals',
        'notify_schedule_changes',
        'notify_attendance_changes',
        'notify_academic_alerts',
        'notify_attendance_exceptions',
    ];

    protected function casts(): array
    {
        return [
            'notification_email_enabled' => 'boolean',
            'notify_approvals' => 'boolean',
            'notify_schedule_changes' => 'boolean',
            'notify_attendance_changes' => 'boolean',
            'notify_academic_alerts' => 'boolean',
            'notify_attendance_exceptions' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
