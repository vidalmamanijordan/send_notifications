<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherEvaluationStatus extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'academic_period_id',
        'campus_id',
        'total_components',
        'evaluated_components',
        'expired_components'
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
}
