<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExcelUpload extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'academic_period_id',
        'campus_id',
        'file_path',
        'is_processed'
    ];

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
}
