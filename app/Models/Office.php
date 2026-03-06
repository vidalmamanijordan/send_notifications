<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'email',
        'cc_email',
        'level',
        'signature',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope('orderByLevel', function ($query) {
            $query->orderBy('level')
                ->orderBy('name');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function notificationBatches()
    {
        return $this->hasMany(NotificationBatch::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
