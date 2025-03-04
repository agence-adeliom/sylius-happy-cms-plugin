<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Menu;

use Doctrine\Common\Collections\Collection;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;

interface MenuItemInterface extends ResourceInterface, TranslatableInterface, \Stringable
{
    public function getId(): ?int;

    public function getLft(): ?int;

    public function getRgt(): ?int;

    public function getRoot(): ?int;

    public function getLvl(): ?int;

    public function getPosition(): ?int;

    public function setMenu(?MenuInterface $menu): void;

    public function setPublishState(?string $state): void;

    public function setPosition(int $position): void;

    public function addChild(self $child): void;

    public function getPublishState(): ?string;

    /**
     * @param array<string>|null $parents
     *
     * @return array<string>
     */
    public function getParents(?array $parents = [], ?self $parent = null): array;

    public function getParent(): ?self;

    public function getMenu(): ?MenuInterface;

    /**
     * @return Collection<int, MenuItemInterface>
     */
    public function getChildren(): Collection;
}
