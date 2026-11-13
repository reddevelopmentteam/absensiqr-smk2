<?php

namespace App\Services\Biometric;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagePathResolver
{
    public function resolve(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }

        if (Str::contains($path, '..')) {
            return null;
        }

        if (Str::startsWith($path, DIRECTORY_SEPARATOR) && $this->isAllowedFile($path)) {
            return realpath($path);
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        if ($path === '') {
            return null;
        }

        $publicPath = Storage::disk('public')->path($path);

        if ($this->isAllowedFile($publicPath)) {
            return realpath($publicPath);
        }

        // Some legacy records store a path relative to the application root.
        // Never resolve outside the project/public/storage roots.
        if (Str::startsWith($path, 'public/')) {
            $candidate = public_path(Str::after($path, 'public/'));
            if ($this->isAllowedFile($candidate)) {
                return realpath($candidate);
            }
        }

        $candidate = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;

        return $this->isAllowedFile($candidate) ? realpath($candidate) : null;
    }

    private function isAllowedFile(?string $path): bool
    {
        if (! $path || ! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $realPath = realpath($path);

        if (! $realPath) {
            return false;
        }

        $allowedRoots = array_filter([
            realpath(base_path()),
            realpath(public_path()),
            realpath(storage_path('app')),
        ]);

        foreach ($allowedRoots as $root) {
            if ($realPath === $root || Str::startsWith($realPath, $root.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    public function hash(?string $absolutePath): ?string
    {
        if (! $absolutePath || ! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return null;
        }

        return hash_file('sha256', $absolutePath) ?: null;
    }
}
