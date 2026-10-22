<?php

namespace App\Support\Marketing;

/**
 * URL of a file on the "public" disk, relative to the current host.
 * Storage::url() builds it from APP_URL, which differs from the host users open the app with
 * (e.g. APP_URL=http://localhost locally), so images came out broken.
 */
class PublicFile
{
    public static function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        return '/storage/' . ltrim(preg_replace('#^/?storage/#', '', $path), '/');
    }
}
