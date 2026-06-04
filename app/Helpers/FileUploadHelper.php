<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

class FileUploadHelper
{
    // Completed in PHASE-1 STEP-21. Stubs here to allow autoloading.

    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE     = 2097152; // 2MB

    public static function saveAvatar(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload error code: ' . $file['error'], 422);
        }

        if ($file['size'] > self::MAX_SIZE) {
            throw new RuntimeException('Ukuran file melebihi batas 2MB', 422);
        }

        // Double MIME check: magic bytes + GD parse
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new RuntimeException('Format file tidak diizinkan', 422);
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new RuntimeException('File bukan gambar yang valid', 422);
        }

        $ext = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest     = __DIR__ . '/../../public/uploads/avatars/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Gagal menyimpan file', 500);
        }

        return '/uploads/avatars/' . $filename;
    }

    public static function deleteAvatar(?string $path): void
    {
        if ($path === null) {
            return;
        }
        $full = __DIR__ . '/../../public' . $path;
        if (file_exists($full) && is_file($full)) {
            unlink($full);
        }
    }
}
