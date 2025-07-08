<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Cmf;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CheckboxField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Symfony\Component\Form\Extension\Core\Type\TextType;

abstract class AbstractRedirectRouteAdmin extends AbstractAdmin implements RedirectRouteAdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_redirect_route_admin';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'staticPrefix';
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
        yield TabField::new('Route', 'sylius_happy_cms.redirection.admin.tab.redirection')
            ->renderHorizontal();

        yield Field::new('host', 'sylius_happy_cms.redirection.admin.host')
            ->setHelp('sylius_happy_cms.redirection.admin.help.host');

        yield Field::new('staticPrefix', 'sylius_happy_cms.redirection.admin.static_prefix')
            ->setHelp('sylius_happy_cms.redirection.admin.help.static_prefix');

        yield Field::new('uri', 'sylius_happy_cms.redirection.admin.uri')
            ->setHelp('sylius_happy_cms.redirection.admin.help.uri')
            ->setFormType(TextType::class);

        yield CheckboxField::new('permanent', 'sylius_happy_cms.redirection.admin.permanent')
            ->setHelp('sylius_happy_cms.redirection.admin.help.permanent');
    }
}
