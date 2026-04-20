<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItContact extends Model
{
    /** @use HasFactory<\Database\Factories\ItContactFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'department',
        'email',
        'phone',
        'whatsapp',
        'schedule',
        'specialties',
        'is_available',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'is_available' => 'boolean',
            'is_primary' => 'boolean',
        ];
    }
}
