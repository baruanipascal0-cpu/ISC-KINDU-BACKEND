<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UniqueSlug
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function forModel(string $modelClass, string $source, ?Model $ignore = null, string $column = 'slug'): string
    {
        $base = Str::slug($source) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while ($modelClass::query()
            ->where($column, $slug)
            ->when($ignore?->exists, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
