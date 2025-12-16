<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationComponent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_offering_id',
        'name',
        'start_date',
        'end_date',
        'status'
    ];

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }
}
