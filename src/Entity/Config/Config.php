<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Config;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusHappyCMSPlugin\Entity\Channel\ChannelAwareInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Channel\ChannelAwareTrait;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Resource\Model\TranslatableTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity('key')]
#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass(repositoryClass: ConfigRepository::class)]
class Config implements ConfigInterface, ChannelAwareInterface
{
    use ChannelAwareTrait;
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

    /** @var Collection<int, ChannelInterface> */
    #[ORM\ManyToMany(targetEntity: ChannelInterface::class)]
    #[ORM\JoinTable(
        name: 'sylius_happy_cms__config_channels',
        joinColumns: [new ORM\JoinColumn(name: 'config_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
    )]
    protected Collection $channels;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->initializeChannels();
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

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }
}
