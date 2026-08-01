<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Graduate extends Model
{
    protected $fillable = [
        'graduation_list_id',
        'student_id',
        'matricule',
        'last_name',
        'post_name',
        'first_name',
        'gender',
        'percentage',
        'mention',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
        ];
    }

    public function graduationList(): BelongsTo
    {
        return $this->belongsTo(GraduationList::class);
    }
}
