<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityStatusTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityTimestampableTrait;
use Adeliom\SyliusHappyCMSPlugin\Repository\SharedBlock\SharedBlockRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass(repositoryClass: SharedBlockRepository::class)]
class SharedBlock implements SharedBlockInterface
{
    use EntityIdTrait;
    use TranslatableTrait {
        TranslatableTrait::__construct as private initializeTranslationsCollection;
        getTranslation as private doGetTranslation;
    }
    use EntityTimestampableTrait {
        EntityTimestampableTrait::__construct as private timestampableConstruct;
    }
    use EntityStatusTrait;

    #[ORM\Column(name: 'block_key', type: Types::STRING, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    protected ?string $key = null;

    #[ORM\Column(name: 'type', type: Types::STRING)]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    protected ?string $type = null;

    /** @var array<string, mixed>|null $settings */
    #[ORM\Column(name: 'settings', type: Types::JSON)]
    #[Assert\Type('array')]
    protected ?array $settings = [];

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->timestampableConstruct();
    }

    protected function createTranslation(): SharedBlockTranslationInterface
    {
        return new SharedBlockTranslation();
    }

    public function getTranslation(?string $locale = null): SharedBlockTranslation
    {
        /** @var SharedBlockTranslation $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    public static function getTranslationClass(): string
    {
        return SharedBlockTranslation::class;
    }

    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }

    /**
     * @param  array<string, mixed> $settings
     */
    public function setSettings(?array $settings): void
    {
        $this->settings = $settings;
    }
}
