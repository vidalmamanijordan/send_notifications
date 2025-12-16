<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use SoftDeletes;

    protected $table = 'campus';

    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    public function faculties()
    {
        return $this->belongsToMany(Faculty::class, 'campus_faculty');
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'campus_faculty_program');
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function excelUploads()
    {
        return $this->hasMany(ExcelUpload::class);
    }
}
