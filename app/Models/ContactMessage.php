<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'response',
        'answered_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
        ];
    }
}
