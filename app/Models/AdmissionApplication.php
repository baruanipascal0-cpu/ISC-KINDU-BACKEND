<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdmissionApplication extends Model
{
    protected $fillable = [
        'user_id',
        'section_id',
        'program_id',
        'academic_year_id',
        'level_id',
        'promotion_id',
        'application_number',
        'status',
        'academic_year',
        'level',
        'last_name',
        'post_name',
        'first_name',
        'gender',
        'nationality',
        'email',
        'phone',
        'address',
        'birth_date',
        'birth_place',
        'last_school',
        'diploma_year',
        'percentage',
        'guardian_phone',
        'diploma_path',
        'photo_path',
        'comment',
        'submitted_at',
        'reviewed_at',
        'review_note',
        'student_message',
        'internal_note',
        'reviewed_by',
        'approved_at',
        'rejected_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'diploma_year' => 'integer',
            'percentage' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(AdmissionDecision::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->last_name.' '.$this->post_name.' '.$this->first_name);
    }
}
