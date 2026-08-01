<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'admission_application_id',
        'section_id',
        'program_id',
        'matricule',
        'last_name',
        'post_name',
        'first_name',
        'gender',
        'birth_date',
        'birth_place',
        'email',
        'phone',
        'address',
        'status',
        'admitted_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'admitted_at' => 'datetime',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->last_name.' '.$this->post_name.' '.$this->first_name);
    }
}
