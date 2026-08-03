<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PublicUpload
{
    public static function store(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'public');

        return Storage::disk('public')->url($path);
    }
}
