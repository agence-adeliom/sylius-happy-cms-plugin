<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Menu;

use Sylius\Resource\Model\ResourceInterface;

interface MenuInterface extends ResourceInterface, \Stringable
{
    public function addItem(MenuItemInterface $item): void;

    public function setCode(string $code): void;

    public function setName(?string $name): void;

    public function setStatus(bool $status = false): void;
}
