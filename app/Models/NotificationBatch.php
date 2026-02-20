<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationBatch extends Model
{
    protected $fillable = [
        'academic_period_id',
        'campus_id',
        'name',
        'description',
        'execution_date',
        'status'
    ];

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function details()
    {
        return $this->hasMany(NotificationBatchDetail::class);
    }
}
