<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationBatchDetail extends Model
{
    protected $fillable = [
        'notification_batch_id',
        'teacher_id',
        'pending_courses_count',
        'status',
        'sent_at'
    ];

    public function teacher()
    {
        return $this->belongsTo(\App\Models\Teacher::class, 'teacher_id');
    }
}
