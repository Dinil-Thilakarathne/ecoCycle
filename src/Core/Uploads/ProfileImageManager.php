<?php

namespace Core\Uploads;

/**
 * Handles validation, storage, and cleanup for profile image uploads.
 */
class ProfileImageManager
{
    public const UPLOAD_DIRECTORY = 'uploads/profile-images';
    private const MAX_UPLOAD_SIZE = 2097152; // 2 MiB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Validate and persist an uploaded profile photo.
     *
     * @param array $file $_FILES style payload for the upload field.
     * @return array{ok:bool,path:?string,error:?string}
     */
    public function store(array $file): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            return [
                'ok' => false,
                'path' => null,
                'error' => 'There was a problem with the uploaded file.',
            ];
        }

        $size = $file['size'] ?? 0;
        if ($size > self::MAX_UPLOAD_SIZE) {
            return [
                'ok' => false,
                'path' => null,
                'error' => 'Profile images must be 2 MB or smaller.',
            ];
        }

        $tmpPath = $file['tmp_name'] ?? '';
        if (!is_string($tmpPath) || !is_file($tmpPath)) {
            return [
                'ok' => false,
                'path' => null,
                'error' => 'Invalid upload detected.',
            ];
        }

        if (!is_uploaded_file($tmpPath)) {
            return [
                'ok' => false,
                'path' => null,
                'error' => 'Invalid upload detected.',
            ];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath) ?: '';
        if (!isset(self::ALLOWED_MIME_TYPES[$mime])) {
            return [
                'ok' => false,
                'path' => null,
                'error' => 'Unsupported image format. Please upload a JPG, PNG, GIF, or WEBP file.',
            ];
        }

        $extension = self::ALLOWED_MIME_TYPES[$mime];
        try {
            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'path' => null,
                'error' => 'Unable to prepare storage for profile images.',
            ];
        }

        $publicPath = rtrim(\base_path('public/' . self::UPLOAD_DIRECTORY), '/');
        if (!is_dir($publicPath) && !@mkdir($publicPath, 0775, true) && !is_dir($publicPath)) {
            return [
                'ok' => false,
                'path' => null,
                'error' => 'Unable to prepare storage for profile images.',
            ];
        }

        $targetPath = $publicPath . '/' . $fileName;
        if (!@move_uploaded_file($tmpPath, $targetPath)) {
            return [
                'ok' => false,
                'path' => null,
                'error' => 'Failed to store the uploaded image.',
            ];
        }

        @chmod($targetPath, 0644);

        return [
            'ok' => true,
            'path' => self::UPLOAD_DIRECTORY . '/' . $fileName,
            'error' => null,
        ];
    }

    /**
     * Remove a previously stored profile photo if it resides in the allowed directory.
     */
    public function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $cleanPath = str_replace(['..', '\\'], ['', '/'], $relativePath);
        if (strncmp($cleanPath, self::UPLOAD_DIRECTORY, strlen(self::UPLOAD_DIRECTORY)) !== 0) {
            return;
        }

        $fullPath = \base_path('public/' . ltrim($cleanPath, '/'));
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    public function getMaxUploadSize(): int
    {
        return self::MAX_UPLOAD_SIZE;
    }
}
