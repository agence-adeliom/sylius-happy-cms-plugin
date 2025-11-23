<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\Menu;

use Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuAdmin as BaseMenuAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;

class MenuAdmin extends BaseMenuAdmin
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_menu_admin';
    }

    public static function getEntityFqcn(): string
    {
        return Menu::class;
    }
}
