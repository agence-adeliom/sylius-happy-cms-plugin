<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Media;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Secure file validator using whitelist and magic bytes verification
 */
class FileValidator
{
    /**
     * Whitelist of allowed MIME types
     * Using whitelist is more secure than blacklist
     */
    private const ALLOWED_MIME_TYPES = [
        // Images
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',
        'image/tiff',

        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',

        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-tar',
        'application/gzip',

        // Audio
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'audio/ogg',
        'audio/webm',

        // Video
        'video/mp4',
        'video/mpeg',
        'video/webm',
        'video/ogg',
        'video/quicktime',
    ];

    /**
     * Whitelist of allowed file extensions
     */
    private const ALLOWED_EXTENSIONS = [
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'tif',

        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',

        // Archives
        'zip', 'rar', 'tar', 'gz',

        // Audio
        'mp3', 'wav', 'ogg', 'oga', 'weba',

        // Video
        'mp4', 'mpeg', 'mpg', 'webm', 'ogv', 'mov',
    ];

    /**
     * Magic bytes for file type verification
     * First bytes of file to verify real content type
     */
    private const MAGIC_BYTES = [
        'image/jpeg' => [
            ['FFD8FFE0', 0],
            ['FFD8FFE1', 0],
            ['FFD8FFE2', 0],
            ['FFD8FFDB', 0],
        ],
        'image/png' => [
            ['89504E47', 0],
        ],
        'image/gif' => [
            ['474946383761', 0], // GIF87a
            ['474946383961', 0], // GIF89a
        ],
        'application/pdf' => [
            ['25504446', 0], // %PDF
        ],
        'application/zip' => [
            ['504B0304', 0],
            ['504B0506', 0],
            ['504B0708', 0],
        ],
    ];

    /**
     * Dangerous file extensions that should NEVER be allowed
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht', 'phps', 'phar',
        'exe', 'com', 'bat', 'cmd', 'sh', 'bash', 'zsh',
        'js', 'jar', 'app', 'vb', 'vbs', 'wsf', 'asp', 'aspx',
        'cer', 'csr', 'jsp', 'drv', 'sys', 'ade', 'adp', 'bas',
        'chm', 'cpl', 'crt', 'hlp', 'hta', 'inf', 'ins', 'isp',
        'jse', 'lnk', 'mdb', 'mde', 'msc', 'msi', 'msp', 'mst',
        'pcd', 'pif', 'reg', 'scr', 'sct', 'shs', 'url', 'vbe',
        'wsc', 'wsf', 'wsh',
    ];

    /**
     * Validate uploaded file
     *
     * @throws \InvalidArgumentException If file is not valid or secure
     */
    public function validate(UploadedFile $file): void
    {
        // 1. Check if file was uploaded via HTTP POST
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        // 2. Get real file extension
        $extension = strtolower($file->getClientOriginalExtension());

        // 3. Check for dangerous extensions (double check)
        if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Dangerous file extension detected: %s', $extension),
            );
        }

        // 4. Check extension whitelist
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(
                sprintf('File extension not allowed: %s', $extension),
            );
        }

        // 5. Verify real MIME type based on file content (not client-provided)
        $realMimeType = $this->getRealMimeType($file);

        // 6. Check MIME type whitelist
        if (!in_array($realMimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('MIME type not allowed: %s', $realMimeType),
            );
        }

        // 7. Verify magic bytes for critical file types
        $this->verifyMagicBytes($file, $realMimeType);

        // 8. Check for double extensions (e.g., file.php.jpg)
        $this->checkDoubleExtension($file);
    }

    /**
     * Get real MIME type based on file content, not client-provided header
     */
    public function getRealMimeType(UploadedFile $file): string
    {
        $path = $file->getPathname();

        // Use finfo to detect MIME type from file content
        $finfo = new \finfo(\FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        if ($mimeType === false) {
            throw new \InvalidArgumentException('Could not determine file MIME type');
        }

        return $mimeType;
    }

    /**
     * Verify magic bytes for critical file types
     */
    private function verifyMagicBytes(UploadedFile $file, string $expectedMimeType): void
    {
        // Only verify magic bytes for types we have signatures for
        if (!isset(self::MAGIC_BYTES[$expectedMimeType])) {
            return;
        }

        $path = $file->getPathname();
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new \InvalidArgumentException('Could not open file for reading');
        }

        try {
            // Read first 12 bytes (enough for most signatures)
            $header = fread($handle, 12);

            if ($header === false) {
                throw new \InvalidArgumentException('Could not read file header');
            }

            $headerHex = strtoupper(bin2hex($header));
            $signatureMatched = false;

            foreach (self::MAGIC_BYTES[$expectedMimeType] as [$signature, $offset]) {
                $signatureLength = strlen($signature);
                $fileSignature = substr($headerHex, $offset * 2, $signatureLength);

                if ($fileSignature === $signature) {
                    $signatureMatched = true;

                    break;
                }
            }

            if (!$signatureMatched) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'File content does not match expected type %s (magic bytes verification failed)',
                        $expectedMimeType,
                    ),
                );
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Check for double extensions (e.g., file.php.jpg)
     */
    private function checkDoubleExtension(UploadedFile $file): void
    {
        $filename = $file->getClientOriginalName();
        $parts = explode('.', $filename);

        // If more than 2 parts (name.ext1.ext2), check all extensions
        if (count($parts) > 2) {
            // Remove the filename part
            array_shift($parts);

            // Check each extension part
            foreach ($parts as $part) {
                $ext = strtolower($part);
                if (in_array($ext, self::DANGEROUS_EXTENSIONS, true)) {
                    throw new \InvalidArgumentException(
                        sprintf('Dangerous double extension detected: %s', $filename),
                    );
                }
            }
        }
    }

    /**
     * Check if a MIME type is allowed
     */
    public function isMimeTypeAllowed(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_MIME_TYPES, true);
    }

    /**
     * Check if an extension is allowed
     */
    public function isExtensionAllowed(string $extension): bool
    {
        return in_array(strtolower($extension), self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Get list of allowed extensions
     */
    public function getAllowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    /**
     * Get list of allowed MIME types
     */
    public function getAllowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }

    /**
     * Validate file size
     *
     * @param UploadedFile $file File to validate
     * @param int $maxSizeMb Maximum size in MB (0 = no limit)
     *
     * @throws \InvalidArgumentException If file size exceeds limit
     */
    public function validateFileSize(UploadedFile $file, int $maxSizeMb = 0): void
    {
        // Skip if no limit configured
        if ($maxSizeMb <= 0) {
            return;
        }

        $fileSizeBytes = $file->getSize();
        $maxSizeBytes = $maxSizeMb * 1024 * 1024;

        if ($fileSizeBytes > $maxSizeBytes) {
            throw new \InvalidArgumentException(
                sprintf(
                    'File size (%s) exceeds maximum allowed size of %d MB',
                    $this->formatBytes($fileSizeBytes),
                    $maxSizeMb,
                ),
            );
        }
    }

    /**
     * Validate total upload size for multiple files
     *
     * @param UploadedFile[] $files Files to validate
     * @param int $maxTotalSizeMb Maximum total size in MB (0 = no limit)
     *
     * @throws \InvalidArgumentException If total size exceeds limit
     */
    public function validateTotalSize(array $files, int $maxTotalSizeMb = 0): void
    {
        // Skip if no limit configured
        if ($maxTotalSizeMb <= 0) {
            return;
        }

        $totalSize = 0;
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $totalSize += $file->getSize();
            }
        }

        $maxTotalSizeBytes = $maxTotalSizeMb * 1024 * 1024;

        if ($totalSize > $maxTotalSizeBytes) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Total upload size (%s) exceeds maximum allowed size of %d MB',
                    $this->formatBytes($totalSize),
                    $maxTotalSizeMb,
                ),
            );
        }
    }

    /**
     * Validate number of files in upload
     *
     * @param UploadedFile[] $files Files to validate
     * @param int $maxCount Maximum number of files (0 = no limit)
     *
     * @throws \InvalidArgumentException If file count exceeds limit
     */
    public function validateFileCount(array $files, int $maxCount = 0): void
    {
        // Skip if no limit configured
        if ($maxCount <= 0) {
            return;
        }

        $count = count($files);

        if ($count > $maxCount) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Too many files (%d). Maximum %d files allowed per upload',
                    $count,
                    $maxCount,
                ),
            );
        }
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1024 ** $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get PHP upload max file size in bytes
     *
     * @return int Maximum upload size in bytes from PHP configuration
     */
    public function getPhpMaxUploadSize(): int
    {
        $uploadMax = $this->parseSize(ini_get('upload_max_filesize') ?: '');
        $postMax = $this->parseSize(ini_get('post_max_size') ?: '');

        // Return the smaller of the two limits
        if ($postMax > 0 && $postMax < $uploadMax) {
            return $postMax;
        }

        return $uploadMax;
    }

    /**
     * Parse PHP size string (e.g., "10M", "2G") to bytes
     */
    private function parseSize(string $size): int
    {
        $size = trim($size);
        $unit = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;

        switch ($unit) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}
