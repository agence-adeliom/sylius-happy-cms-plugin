<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Cmf;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ResourceChoiceField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\SlugField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\FlexibleContentField;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\SEOField;
use Adeliom\SyliusHappyCMSPlugin\Admin\Page\PageAdminInterface;
use App\Entity\HappyCMS\Page\Page;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;

abstract class AbstractRouteAdmin extends AbstractAdmin implements RouteAdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_route_admin';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'id';
    }

    public function configureFilters(): iterable
    {
        yield from parent::configureFilters();
    }

    public function configureActions(string $pageName): Actions
    {
        $actions = parent::configureActions($pageName);

        return $actions;
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('Page', 'sylius_happy_cms.page.admin.tab.page');

        yield Field::new('staticPrefix', 'sylius_happy_cms.page.admin.slug');
    }
}
