<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'campus_id',
        'faculty_id',
        'program_id',
        'academic_period_id'
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class);
    }
}
