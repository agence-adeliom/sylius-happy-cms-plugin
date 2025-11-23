<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Event\Media;

use Symfony\Contracts\EventDispatcher\Event;

class MediaFileUploaded extends Event
{
    /**
     * @var string
     */
    public const NAME = 'em.file.uploaded';

    /**
     * @param  array<string, mixed> $options
     */
    public function __construct(
        private string $filePath,
        private string|null $mimeType,
        private array $options = [],
    ) {
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getMimeType(): string
    {
        return $this->mimeType ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
