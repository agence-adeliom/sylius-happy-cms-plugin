<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Menu;

use Sylius\Resource\Model\ResourceInterface;

interface MenuInterface extends ResourceInterface, \Stringable
{
    public function addItem(MenuItemInterface $item): void;
}
