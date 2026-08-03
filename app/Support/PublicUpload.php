<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PublicUpload
{
    public static function store(UploadedFile $file, string $directory): string
    {
        return self::storeImage($file, $directory)['image_url'];
    }

    public static function storeImage(UploadedFile $file, string $directory, ?string $alt = null): array
    {
        $disk = self::disk();
        $path = $file->store($directory, $disk);

        return [
            'image_url' => Storage::disk($disk)->url($path),
            'image_public_id' => $path,
            'image_disk' => $disk,
            'image_alt' => $alt,
        ];
    }

    public static function externalImage(?string $url, ?string $alt = null): array
    {
        return [
            'image_url' => $url,
            'image_public_id' => null,
            'image_disk' => $url ? 'external' : null,
            'image_alt' => $alt,
        ];
    }

    public static function imageFrom(?object $model): array
    {
        return [
            'image_url' => $model?->image_url,
            'image_public_id' => $model?->image_public_id,
            'image_disk' => $model?->image_disk,
            'image_alt' => $model?->image_alt,
        ];
    }

    public static function delete(?string $disk, ?string $publicId): void
    {
        if (! $disk || ! $publicId || $disk === 'external') {
            return;
        }

        try {
            Storage::disk($disk)->delete($publicId);
        } catch (Throwable) {
            report(new \RuntimeException('Media deletion failed for '.$disk.':'.$publicId));
        }
    }

    public static function deleteIfReplaced(array $oldMedia, array $newMedia): void
    {
        if (($oldMedia['image_public_id'] ?? null) === ($newMedia['image_public_id'] ?? null)) {
            return;
        }

        self::delete($oldMedia['image_disk'] ?? null, $oldMedia['image_public_id'] ?? null);
    }

    private static function disk(): string
    {
        return env('MEDIA_DISK', env('PUBLIC_UPLOAD_DISK', 'public'));
    }
}
