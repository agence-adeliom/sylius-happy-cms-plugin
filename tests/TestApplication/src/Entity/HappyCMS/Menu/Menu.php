<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\Menu as BaseMenu;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\Menu\MenuRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\Table(name: 'sylius_happy_cms__menu')]
class Menu extends BaseMenu
{
}
