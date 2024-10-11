<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\Menu;

class MenuAdmin extends AbstractMenuAdmin implements MenuAdminInterface
{
    public static function getName(): string
    {
        return 'sylius_happy_cms_menu_admin';
    }

    public static function getEntityFqcn(): string
    {
        return Menu::class;
    }
}
