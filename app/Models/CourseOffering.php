<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseOffering extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'teacher_assignment_id',
        'term',
        'group'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function teacherAssignment()
    {
        return $this->belongsTo(TeacherAssignment::class);
    }

    public function evaluationComponents()
    {
        return $this->hasMany(EvaluationComponent::class);
    }
}
