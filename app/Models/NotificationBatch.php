<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationBatch extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'import_batch_id',
        'academic_period_id',
        'campus_id',
        'notification_template_id',
        'subject',
        'body',
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

    public function notificationTemplate()
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
