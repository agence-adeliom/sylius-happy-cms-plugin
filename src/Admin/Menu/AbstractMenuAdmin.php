<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Menu;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;

abstract class AbstractMenuAdmin extends AbstractAdmin implements MenuAdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_menu_admin';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'name';
    }

    public function configureActions(string $pageName): Actions
    {
        $actions = parent::configureActions($pageName);
        $manageMenuItems = Action::new('manage.menu_items', 'sylius_happy_cms.menu.admin.action.manage', 'bars')
            ->linkToRoute('sylius_happy_cms_admin_menu_item_create');

        $actions->addItemAction(Crud::PAGE_INDEX, $manageMenuItems);
        $actions->addItemAction(Crud::PAGE_DETAIL, $manageMenuItems);

        return $actions;
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('menu', 'sylius_happy_cms.menu.admin.tab.menu');

        yield Field::new('name', 'sylius_happy_cms.menu.admin.field.name');

        yield Field::new('code', 'sylius_happy_cms.menu.admin.field.code');

        yield Field::new('status', 'sylius_happy_cms.menu.admin.field.status');
    }
}
