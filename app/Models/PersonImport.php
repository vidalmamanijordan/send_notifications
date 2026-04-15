<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonImport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'file_name',
        'file_size',
        'total_rows',
        'created_count',
        'email_updated_count',
        'skipped_count',
        'ignored_count',
        'imported_by',
        'academic_period_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
