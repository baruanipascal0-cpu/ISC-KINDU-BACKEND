<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id',
        'admission_application_id',
        'academic_year_id',
        'section_id',
        'program_id',
        'level_id',
        'promotion_id',
        'enrollment_number',
        'type',
        'status',
        'enrolled_on',
        'fiche_path',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_on' => 'date',
            'metadata' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function admissionApplication(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function scopeOrderedForRegistry(Builder $query): Builder
    {
        return $query
            ->orderByRaw('(select sort_order from promotions where promotions.id = enrollments.promotion_id) is null')
            ->orderBy(Promotion::select('sort_order')->whereColumn('promotions.id', 'enrollments.promotion_id')->limit(1))
            ->orderBy(Promotion::select('name')->whereColumn('promotions.id', 'enrollments.promotion_id')->limit(1))
            ->orderByRaw('(select name from programs where programs.id = enrollments.program_id) is null')
            ->orderBy(Program::select('name')->whereColumn('programs.id', 'enrollments.program_id')->limit(1))
            ->orderBy(Student::select('last_name')->whereColumn('students.id', 'enrollments.student_id')->limit(1))
            ->orderBy(Student::select('post_name')->whereColumn('students.id', 'enrollments.student_id')->limit(1))
            ->orderBy(Student::select('first_name')->whereColumn('students.id', 'enrollments.student_id')->limit(1))
            ->orderBy('enrollment_number');
    }
}
