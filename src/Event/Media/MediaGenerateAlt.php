<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Event\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MediaGenerateAlt extends Event
{
    /**
     * @var string
     */
    public const NAME = 'em.file.alt.generate';

    public function __construct(
        public MediaInterface $entity,
        public string $filePath,
        public string $alt = '',
    ) {
    }

    public function getEntity(): MediaInterface
    {
        return $this->entity;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getAlt(): string
    {
        return $this->alt;
    }

    public function setAlt(string $alt): void
    {
        $this->alt = $alt;
    }
}
