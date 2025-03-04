<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Menu;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Sylius\Resource\Model\AbstractTranslation;

#[HasLifecycleCallbacks]
#[MappedSuperclass]
class MenuItemTranslation extends AbstractTranslation implements MenuItemTranslationInterface
{
    use EntityIdTrait;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(name: 'url', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $url = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }
}
