<?php

namespace App\Support;

use finfo;
use InvalidArgumentException;

final class ImageUploader
{
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAX_BYTES = 2 * 1024 * 1024; // 2MB

    /**
     * @param array $file one element of $_FILES (e.g. $_FILES['image'])
     * @return string|null relative path (stored in the image_path column), or null if no file was uploaded
     */
    public static function store(array $file): ?string
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Image upload failed.');
        }

        if ($file['size'] > self::MAX_BYTES) {
            throw new InvalidArgumentException('Image size must not exceed 2MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new InvalidArgumentException('Image format must be JPG, PNG, or WEBP.');
        }

        $destinationDir = __DIR__ . '/../../public/uploads/products';
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $extension = self::ALLOWED_MIME[$mime];
        $randomName = bin2hex(random_bytes(16)) . '.' . $extension;

        move_uploaded_file($file['tmp_name'], $destinationDir . '/' . $randomName);

        return 'uploads/products/' . $randomName;
    }
}
