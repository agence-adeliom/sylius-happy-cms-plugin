<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $newSubmenu = $menu
            ->addChild('adeliom-sylius-cms')
            ->setLabel('sylius_happy_cms.admin.menu.cms')
        ;
        $newSubmenu
            ->addChild('happy_cms_page', ['route' => 'sylius_happy_cms_admin_page_index'])
            ->setLabel('sylius_happy_cms.admin.menu.pages')
            ->setLabelAttribute('icon', 'file')
        ;
        $newSubmenu
            ->addChild('media', ['route' => 'media.index'])
            ->setLabel('sylius_happy_cms.admin.menu.medias')
            ->setLabelAttribute('icon', 'image')
        ;
        $newSubmenu
            ->addChild('happy_cms_menu', ['route' => 'sylius_happy_cms_admin_menu_index'])
            ->setLabel('sylius_happy_cms.admin.menu.menus')
            ->setLabelAttribute('icon', 'uil:bars')
        ;
        $newSubmenu
            ->addChild('happy_cms_block', ['route' => 'sylius_happy_cms_admin_shared_block_index'])
            ->setLabel('sylius_happy_cms.admin.menu.shared_blocks')
            ->setLabelAttribute('icon', 'solar:box-bold')
        ;
        //$newSubmenu
        //    ->addChild('happy_cms_route', ['route' => 'sylius_happy_cms_admin_route_index'])
        //    ->setLabel('sylius_happy_cms.admin.menu.routes')
        //    ->setLabelAttribute('icon', 'file')
        //;
        $newSubmenu
            ->addChild('happy_cms_redirect_route', ['route' => 'sylius_happy_cms_admin_redirect_route_index'])
            ->setLabel('sylius_happy_cms.admin.menu.redirections')
            ->setLabelAttribute('icon', 'file')
        ;

        $children = $menu->getChildren();

        $cms = $children['adeliom-sylius-cms'];
        unset($children['adeliom-sylius-cms']);
        $menu->reorderChildren(array_keys([
            'adeliom-sylius-cms' => $cms,
        ] + $children));

        $children['configuration']
            ->addChild('adeliom-sylius-easy-config', ['route' => 'sylius_happy_cms_admin_config_index'])
                ->setLabel('sylius_happy_cms.admin.menu.configurations')
                ->setLabelAttribute('icon', 'cogs');
    }
}
