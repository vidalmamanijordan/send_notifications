<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
     use SoftDeletes;

    protected $fillable = ['name', 'code'];

    public function campuses()
    {
        return $this->belongsToMany(Campus::class, 'campus_faculty');
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'campus_faculty_program');
    }
}
