<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'name',
        'academic_period_id',
        'campus_id',
        'imported_by',
        'excel_upload_id',
        'file_name',
        'imported_at',
        'is_active',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    // 🔗 Relación con estados evaluativos
    public function evaluationStatuses(): HasMany
    {
        return $this->hasMany(TeacherEvaluationStatus::class);
    }

    public function upload()
    {
        return $this->belongsTo(ExcelUpload::class, 'excel_upload_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
