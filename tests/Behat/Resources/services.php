<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tests\Adeliom\SyliusHappyCMSPlugin\Behat\Context\PageBuilderContext;
use Tests\Adeliom\SyliusHappyCMSPlugin\Behat\Context\Ui\Admin\MediaCsrfContext;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->public();

    $services->set('tests.adeliom_sylius_happy_cms_plugin.behat.context.page_builder', PageBuilderContext::class)
        ->args([
            service('behat.mink.default_session'),
            service('doctrine.orm.entity_manager'),
            service('sylius.happy_cms.command.create_demo_pages'),
            service('router'),
        ]);

    $services->set('tests.adeliom_sylius_happy_cms_plugin.behat.context.ui.admin.media_csrf', MediaCsrfContext::class)
        ->args([
            service('behat.mink.default_session'),
            service('sylius.behat.shared_storage'),
        ]);
};
