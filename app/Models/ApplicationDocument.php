<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'admission_application_id',
        'document_type_id',
        'name',
        'file_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'uploaded_at' => 'datetime',
        ];
    }

    public function admissionApplication(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class);
    }
}
