<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Seo;

use Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaType;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Seo implements SeoInterface, \Stringable
{
    #[ORM\Column]
    public string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    #[ORM\Column(nullable: true)]
    public ?string $keywords;

    #[ORM\Column(nullable: true)]
    public ?string $canonical;

    #[ORM\Column(type: MediaType::TYPE, nullable: true)]
    public MediaInterface|int|null $cover;

    #[ORM\Column(nullable: true)]
    public ?string $key;

    #[ORM\Column(type: Types::BOOLEAN)]
    public ?bool $sitemap = true;

    /** @var array<int, string> */
    #[ORM\Column(type: Types::JSON)]
    public array $robots = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
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
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): void
    {
        $this->keywords = $keywords;
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
        return $this->cover;
    }

    public function setCover(MediaInterface|int|null $cover): void
    {
        $this->cover = $cover;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    public function getSitemap(): ?bool
    {
        return $this->sitemap;
    }

    public function setSitemap(?bool $sitemap): void
    {
        $this->sitemap = $sitemap;
    }

    /**
     * @return string[]
     */
    public function getRobots(): array
    {
        return $this->robots;
    }

    /**
     * @param string[] $robots
     */
    public function setRobots(array $robots): void
    {
        $this->robots = $robots;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
