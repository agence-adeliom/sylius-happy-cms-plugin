<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock;

use Sylius\Resource\Model\ResourceInterface;

interface ContentBlockInterface
{
    public function setContentOwner(?ResourceInterface $contentOwner): void;

    public function getContentOwner(): ?ResourceInterface;

    public function getType(): ?string;

    public function setType(string $type): void;

    /**
     * @return array<string, mixed>|null
     */
    public function getPublishedData(): ?array;

    /**
     * @param array<string, mixed>|null $data
     */
    public function setPublishedData(?array $data): void;

    /**
     * @return array<string, mixed>|null
     */
    public function getDraftData(): ?array;

    /**
     * @param array<string, mixed>|null $data
     */
    public function setDraftData(?array $data): void;

    public function getLocale(): ?string;

    public function setLocale(string $locale): void;

    public function getPosition(): ?int;

    public function setPosition(int $position): void;

    public function isPublished(): bool;

    public function setPublished(bool $published): void;

    public function getLayer(): ?string;

    public function setLayer(?string $layer): void;

    /**
     * Publish the draft data by copying it to published data.
     */
    public function publish(): void;

    /**
     * Check if there are unpublished changes.
     */
    public function hasUnpublishedChanges(): bool;
}
