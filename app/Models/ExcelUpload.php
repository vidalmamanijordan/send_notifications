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
        'uploaded_by',
        'file_path',
        'status'
    ];

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function importBatch()
    {
        return $this->hasOne(ImportBatch::class);
    }
}
