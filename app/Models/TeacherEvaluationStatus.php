<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherEvaluationStatus extends Model
{
    use SoftDeletes;

    protected $table = 'teacher_evaluation_status';

    protected $fillable = [
        'import_batch_id',
        'excel_upload_id',
        'teacher_id',
        'academic_period_id',
        'campus_id',
        'cycle',
        'group',
        'total_components',
        'evaluated_components',
        'expired_components',
    ];

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function excelUpload()
    {
        return $this->belongsTo(ExcelUpload::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeWithExpired($query)
    {
        return $query->where('expired_components', '>', 0);
    }

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
