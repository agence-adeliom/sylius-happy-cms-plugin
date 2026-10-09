<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Twig\Seo\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\SeoInterface;

/**
 * A project SEO embeddable that implements SeoInterface without extending the plugin Seo class.
 */
final class ProjectSeo implements SeoInterface
{
    private ?string $title = null;

    private ?string $description = null;

    private ?string $canonical = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getKeywords(): ?string
    {
        return null;
    }

    public function setKeywords(?string $keywords): void
    {
    }

    public function getCanonical(): ?string
    {
        return $this->canonical;
    }

    public function setCanonical(?string $canonical): void
    {
        $this->canonical = $canonical;
    }

    public function getCover(): MediaInterface|int|null
    {
        return null;
    }

    public function setCover(MediaInterface|int|null $cover): void
    {
    }

    public function getKey(): ?string
    {
        return null;
    }

    public function setKey(?string $key): void
    {
    }

    public function getSitemap(): ?bool
    {
        return true;
    }

    public function setSitemap(?bool $sitemap): void
    {
    }

    public function getRobots(): array
    {
        return [];
    }

    public function setRobots(array $robots): void
    {
    }

    public static function normalizeFormData(array $data): array
    {
        return $data;
    }
}
