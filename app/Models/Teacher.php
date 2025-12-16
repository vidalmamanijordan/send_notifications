<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'national_id',
        'first_name',
        'last_name',
        'email',
        'whatsapp_phone',
        'is_active'
    ];

    public function assignments()
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
}
