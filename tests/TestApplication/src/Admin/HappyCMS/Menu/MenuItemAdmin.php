<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\Menu;

use Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuItemAdmin as BaseMenuItemAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\MenuItem;

class MenuItemAdmin extends BaseMenuItemAdmin
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_menu_item_admin';
    }

    public static function getEntityFqcn(): string
    {
        return MenuItem::class;
    }
}
