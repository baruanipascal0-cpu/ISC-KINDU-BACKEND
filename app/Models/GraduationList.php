<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GraduationList extends Model
{
    protected $fillable = [
        'academic_year_id',
        'section_id',
        'program_id',
        'promotion_id',
        'title',
        'slug',
        'cycle',
        'decision_date',
        'published_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'decision_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function graduates(): HasMany
    {
        return $this->hasMany(Graduate::class);
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

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
