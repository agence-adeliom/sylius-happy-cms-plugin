<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait for managing preview publication state.
 * This trait is similar to EntityPublishableTrait but for preview/draft mode.
 */
trait EntityPreviewPublishableTrait
{
    #[Groups('main')]
    #[ORM\Column(name: 'preview_publish_state', length: 100, nullable: true)]
    private ?string $previewPublishState;

    #[Groups('main')]
    #[ORM\Column(name: 'preview_publish_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $previewPublishDate;

    #[Groups('main')]
    #[Assert\Expression(
        expression: 'this.getPreviewUnpublishDate() == null or this.getPreviewUnpublishDate() > this.getPreviewPublishDate()',
        message: 'The preview unpublish date must be greater than the preview publish date',
    )]
    #[ORM\Column(name: 'preview_unpublish_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $previewUnpublishDate = null;

    /**
     * Initialize preview publishable fields.
     */
    private function initializePreviewPublishable(): void
    {
        $this->previewPublishDate = null;
        $this->previewUnpublishDate = null;
        $this->previewPublishState = ThreeStateStatusEnum::UNPUBLISHED;
    }

    public function getPreviewPublishState(): ?string
    {
        return $this->previewPublishState;
    }

    public function setPreviewPublishState(?string $state): void
    {
        if ($state) {
            ThreeStateStatusEnum::assertValidValue($state);
        }

        $this->previewPublishState = $state;
    }

    public function getPreviewPublishDate(): ?\DateTimeInterface
    {
        return $this->previewPublishDate;
    }

    public function setPreviewPublishDate(?\DateTimeInterface $publishDate): self
    {
        $this->previewPublishDate = $publishDate;

        return $this;
    }

    public function getPreviewUnpublishDate(): ?\DateTimeInterface
    {
        return $this->previewUnpublishDate;
    }

    public function setPreviewUnpublishDate(?\DateTimeInterface $unpublishDate): self
    {
        $this->previewUnpublishDate = $unpublishDate;

        return $this;
    }

    public function isPreviewOnline(): bool
    {
        return $this->hasPreviewState(ThreeStateStatusEnum::PUBLISHED()->getValue()) && $this->isPreviewDatePublished();
    }

    public function previewPreviewIsAvailable(): bool
    {
        return !$this->hasPreviewState(ThreeStateStatusEnum::UNPUBLISHED()->getValue());
    }

    public function isPreviewStatePublished(): bool
    {
        return $this->hasPreviewState(ThreeStateStatusEnum::PUBLISHED()->getValue());
    }

    public function isPreviewStateUnpublished(): bool
    {
        return null === $this->previewPublishState || $this->hasPreviewState(ThreeStateStatusEnum::UNPUBLISHED()->getValue());
    }

    public function isPreviewStatePending(): bool
    {
        return $this->hasPreviewState(ThreeStateStatusEnum::PENDING()->getValue());
    }

    public function hasPreviewState(?string $state): bool
    {
        return null !== $this->previewPublishState && strtolower($this->previewPublishState) === strtolower($state);
    }

    public function isPreviewDatePublished(): bool
    {
        $now = new \DateTime();

        if (null === $this->getPreviewPublishDate() && null === $this->getPreviewUnpublishDate()) {
            return true;
        }

        return
            (null === $this->getPreviewUnpublishDate() && $this->getPreviewPublishDate() <= $now) ||
            ($now <= $this->getPreviewUnpublishDate() && $this->getPreviewPublishDate() <= $now);
    }

    /**
     * Check if preview version is published.
     */
    public function isPreviewPublished(): bool
    {
        return $this->isPreviewOnline();
    }
}