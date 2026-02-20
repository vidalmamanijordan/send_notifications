<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'name',
        'subject',
        'body',
        'is_active',
    ];

    public function notificationBatches()
    {
        return $this->hasMany(NotificationBatch::class, 'notification_template_id');
    }
}
