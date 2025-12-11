<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityPublishableTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityTimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
abstract class ContentBlock implements ContentBlockInterface
{
    use EntityIdTrait;
    use EntityTimestampableTrait {
        EntityTimestampableTrait::__construct as private timestampableConstruct;
    }
    use EntityPublishableTrait {
        EntityPublishableTrait::__construct as private publishableConstruct;
    }

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    protected ?string $type = null;

    /** @var array<string, mixed>|null $publishedData */
    #[ORM\Column(name: 'published_data', type: Types::JSON, nullable: true)]
    #[Assert\Type('array')]
    protected ?array $publishedData = null;

    /** @var array<string, mixed>|null $draftData */
    #[ORM\Column(name: 'draft_data', type: Types::JSON, nullable: true)]
    #[Assert\Type('array')]
    protected ?array $draftData = null;

    #[ORM\Column(name: 'locale', type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    #[Assert\Locale]
    protected ?string $locale = null;

    #[ORM\Column(name: 'position', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    #[Assert\Type('integer')]
    #[Assert\PositiveOrZero]
    protected int $position = 0;

    #[ORM\Column(name: 'layer', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Type('string')]
    protected ?string $layer = null;

    public function __construct()
    {
        $this->timestampableConstruct();
        $this->publishableConstruct();
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPublishedData(): ?array
    {
        return $this->publishedData;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function setPublishedData(?array $data): void
    {
        $this->publishedData = $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDraftData(): ?array
    {
        return $this->draftData;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function setDraftData(?array $data): void
    {
        $this->draftData = $data;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isPublished(): bool
    {
        return $this->isOnline();
    }

    public function getLayer(): ?string
    {
        return $this->layer;
    }

    public function setLayer(?string $layer): void
    {
        $this->layer = $layer;
    }

    public function publish(): void
    {
        $this->publishedData = $this->draftData;
        $this->setPublishState(ThreeStateStatusEnum::PUBLISHED);
    }

    public function hasUnpublishedChanges(): bool
    {
        return $this->draftData !== $this->publishedData;
    }
}
