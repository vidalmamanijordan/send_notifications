<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationBatch extends Model
{
    use SoftDeletes;

    public const TYPE_RUBRICS = 'rubrics';

    public const TYPE_FREE = 'free';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'type',
        'import_batch_id',
        'academic_period_id',
        'campus_id',
        'office_id',
        'notification_template_id',
        'subject',
        'body',
        'name',
        'description',
        'execution_date',
        'status',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

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

    public function office()
    {
        return $this->belongsTo(Office::class);
    }
}
