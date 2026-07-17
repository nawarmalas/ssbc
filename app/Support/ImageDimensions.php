<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ImageDimensions
{
    /**
     * Pixel dimensions [width, height] of an image on the public disk, or
     * null when unreadable. Cached forever — uploads get unique UUID names,
     * so a path never changes content.
     */
    public static function forPublic(string $path): ?array
    {
        return Cache::rememberForever('imgdim:'.$path, function () use ($path) {
            try {
                $full = Storage::disk('public')->path($path);
                if (! is_file($full)) {
                    return null;
                }
                $size = @getimagesize($full);

                return $size ? [$size[0], $size[1]] : null;
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
