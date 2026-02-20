<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'start_date',
        'end_date',
        'status'
    ];

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function evaluationStatuses()
    {
        return $this->hasMany(TeacherEvaluationStatus::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationBatches()
    {
        return $this->hasMany(NotificationBatch::class, 'academic_period_id');
    }
}
