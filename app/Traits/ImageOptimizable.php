<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Image Optimizable Trait
 *
 * Handles image upload, optimization, and storage with UUID-based flat structure.
 *
 * @context Use this trait in models that have image uploads (Student, User, etc.)
 *
 * @pattern Flat UUID structure for scalability and CDN-friendliness
 */
trait ImageOptimizable
{
    /**
     * Upload and optimize image
     *
     * @param  UploadedFile  $file  The uploaded image file
     * @param  string  $directory  The storage directory (e.g., 'students', 'users')
     * @param  int  $maxWidth  Maximum width in pixels (default: 500)
     * @param  int  $maxHeight  Maximum height in pixels (default: 500)
     * @param  int  $quality  JPEG quality 0-100 (default: 85)
     * @return string The stored filename (UUID.jpg)
     */
    public function uploadAndOptimizeImage(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 500,
        int $maxHeight = 500,
        int $quality = 85
    ): string {
        // Generate UUID filename
        $filename = Str::uuid().'.jpg';

        // Create image manager instance with GD driver
        $manager = new ImageManager(new Driver);

        // Read and process the image
        $image = $manager->read($file->getRealPath());

        // Resize image maintaining aspect ratio, fit within bounds
        $image->scale($maxWidth, $maxHeight);

        // Encode to JPEG with specified quality
        $encoded = $image->toJpeg($quality);

        // Store in public disk
        Storage::disk('public')->put(
            "{$directory}/{$filename}",
            (string) $encoded
        );

        return $filename;
    }

    /**
     * Delete image from storage
     *
     * @param  string|null  $filename  The filename to delete
     * @param  string  $directory  The storage directory
     * @return bool Success status
     */
    public function deleteImage(?string $filename, string $directory): bool
    {
        if (! $filename) {
            return false;
        }

        $path = "{$directory}/{$filename}";

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Get public URL for image
     *
     * @param  string|null  $filename  The filename
     * @param  string  $directory  The storage directory
     * @return string|null The public URL or null
     */
    public function getImageUrl(?string $filename, string $directory): ?string
    {
        if (! $filename) {
            return null;
        }

        return Storage::disk('public')->url("{$directory}/{$filename}");
    }

    /**
     * Replace existing image with new one
     *
     * @param  UploadedFile  $file  New image file
     * @param  string|null  $oldFilename  Old filename to delete
     * @param  string  $directory  Storage directory
     * @param  int  $maxWidth  Maximum width
     * @param  int  $maxHeight  Maximum height
     * @param  int  $quality  JPEG quality
     * @return string New filename
     */
    public function replaceImage(
        UploadedFile $file,
        ?string $oldFilename,
        string $directory,
        int $maxWidth = 500,
        int $maxHeight = 500,
        int $quality = 85
    ): string {
        // Delete old image if exists
        if ($oldFilename) {
            $this->deleteImage($oldFilename, $directory);
        }

        // Upload and optimize new image
        return $this->uploadAndOptimizeImage(
            $file,
            $directory,
            $maxWidth,
            $maxHeight,
            $quality
        );
    }
}
