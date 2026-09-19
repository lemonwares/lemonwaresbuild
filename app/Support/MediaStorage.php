<?php

namespace App\Support;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaStorage
{
    public const PREFIX = 'cloudinary:';

    public static function enabled(): bool
    {
        return CloudinarySettings::isConfigured();
    }

    /**
     * Store an uploaded image. Returns a local relative path or cloudinary:{public_id}.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        if (! self::enabled()) {
            return $file->store($folder, 'public');
        }

        $result = self::client()->uploadApi()->upload($file->getRealPath(), [
            'folder' => trim('lemonwares/'.$folder, '/'),
            'resource_type' => 'image',
            'overwrite' => false,
        ]);

        $publicId = (string) ($result['public_id'] ?? '');

        if ($publicId === '') {
            throw new RuntimeException('Cloudinary upload did not return a public_id.');
        }

        return self::PREFIX.$publicId;
    }

    public static function url(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, self::PREFIX)) {
            $publicId = substr($path, strlen(self::PREFIX));
            $cloud = CloudinarySettings::cloudName();

            if (! self::enabled()) {
                return $cloud !== ''
                    ? 'https://res.cloudinary.com/'.$cloud.'/image/upload/'.ltrim($publicId, '/')
                    : null;
            }

            return (string) self::client()->image($publicId);
        }

        return asset('storage/'.$path);
    }

    public static function delete(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        if (str_starts_with($path, self::PREFIX)) {
            if (! self::enabled()) {
                return;
            }

            $publicId = substr($path, strlen(self::PREFIX));

            try {
                self::client()->uploadApi()->destroy($publicId, [
                    'resource_type' => 'image',
                ]);
            } catch (\Throwable) {
                // Ignore remote delete failures so local admin actions still succeed.
            }

            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public static function client(): Cloudinary
    {
        return new Cloudinary([
            'cloud' => [
                'cloud_name' => CloudinarySettings::cloudName(),
                'api_key' => CloudinarySettings::apiKey(),
                'api_secret' => CloudinarySettings::apiSecret(),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }
}
