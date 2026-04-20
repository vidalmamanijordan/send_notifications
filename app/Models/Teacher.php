<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dni',
        'full_name',
        'email',
        'phone',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evaluationStatuses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherEvaluationStatus::class);
    }
}
