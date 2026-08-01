<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    protected $fillable = [
        'block_group',
        'key',
        'title',
        'subtitle',
        'body',
        'image_url',
        'link_url',
        'link_label',
        'icon',
        'metadata',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
