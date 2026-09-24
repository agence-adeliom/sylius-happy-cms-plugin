<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class ButtonEmbeddable implements ButtonEmbeddableInterface
{
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $action = null;

    /**
     * @param array<string, string|null> $data
     */
    public static function new(array $data): self
    {
        $button = new self();
        $button->setLabel($data['label'] ?? null);
        $button->setIcon($data['icon'] ?? null);
        $button->setLink($data['link'] ?? null);
        $button->setAction($data['action'] ?? null);

        return $button;
    }

    public function __toString(): string
    {
        return $this->getLabel() . ' : ' . $this->getLink();
    }

    /**
     * @return array{label: string|null, link: string|null, icon: string|null, action: string|null}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->getLabel(),
            'link' => $this->getLink(),
            'icon' => $this->getIcon(),
            'action' => $this->getAction(),
        ];
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

    public function isOK(): bool
    {
        return $this->link && $this->label;
    }
}
