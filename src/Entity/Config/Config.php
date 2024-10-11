<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Config;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity('key')]
#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass(repositoryClass: ConfigRepository::class)]
class Config implements ConfigInterface
{
    use EntityIdTrait;
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
        getTranslation as private doGetTranslation;
    }

    #[ORM\Column(name: 'config', type: Types::STRING, length: 255, unique: true)]
    private ?string $key = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $type = null;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
    }

    protected function createTranslation(): ConfigTranslationInterface
    {
        return new ConfigTranslation();
    }

    public function getTranslation(?string $locale = null): ConfigTranslationInterface
    {
        /** @var ConfigTranslationInterface $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    public static function getTranslationClass(): string
    {
        return ConfigTranslation::class;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(mixed $type): void
    {
        $this->type = $type;
    }
}
