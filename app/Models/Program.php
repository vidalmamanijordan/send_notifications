<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'level'];

    public function campuses()
    {
        return $this->belongsToMany(Campus::class, 'campus_faculty_program');
    }

    public function faculties()
    {
        return $this->belongsToMany(Faculty::class, 'campus_faculty_program');
    }
}
