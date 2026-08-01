<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentReview extends Model
{
    protected $fillable = [
        'application_document_id',
        'user_id',
        'decision',
        'internal_note',
        'student_message',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function applicationDocument(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
