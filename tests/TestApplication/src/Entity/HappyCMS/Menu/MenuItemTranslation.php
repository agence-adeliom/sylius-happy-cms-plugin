<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslation as BaseMenuItemTranslation;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__menu_item_translation')]
class MenuItemTranslation extends BaseMenuItemTranslation
{
}
