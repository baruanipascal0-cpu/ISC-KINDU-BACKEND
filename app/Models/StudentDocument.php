<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    protected $fillable = [
        'user_id',
        'admission_application_id',
        'name',
        'type',
        'file_path',
        'status',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admissionApplication(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class);
    }
}
