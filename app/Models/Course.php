<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'credits'];

    public function offerings()
    {
        return $this->hasMany(CourseOffering::class);
    }
}
