<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Sylius\Resource\Model\AbstractTranslation;

#[HasLifecycleCallbacks]
#[MappedSuperclass]
class SharedBlockTranslation extends AbstractTranslation implements SharedBlockTranslationInterface
{
    use EntityIdTrait;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $content = null;

    public function getContent(): ?array
    {
        return $this->content;
    }

    public function setContent(?array $content): void
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
