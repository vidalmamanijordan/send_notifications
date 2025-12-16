<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'academic_period_id',
        'channel',
        'message',
        'status',
        'sent_at'
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function logs()
    {
        return $this->hasMany(NotificationLog::class);
    }
}
