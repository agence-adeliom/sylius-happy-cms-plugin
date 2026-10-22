<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Maker\Block\MakeBlock;
use Adeliom\SyliusHappyCMSPlugin\Maker\CMS\MakeHappyCMS;
use Adeliom\SyliusHappyCMSPlugin\Maker\InstallDefaultFiles;
use Adeliom\SyliusHappyCMSPlugin\Maker\SharedBlock\MakeSharedBlock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius.happy_cms.maker.make_block', MakeBlock::class)
        ->private()
        ->args([service('maker.file_manager')])
        ->tag('maker.command');

    $services->set('sylius.happy_cms.maker.make_happy_cms', MakeHappyCMS::class)
        ->private()
        ->args([
            service('doctrine'),
            service('parameter_bag'),
        ])
        ->tag('maker.command');

    $services->set('sylius.happy_cms.maker.happy_cms_init_default_files', InstallDefaultFiles::class)
        ->private()
        ->args([service('parameter_bag')])
        ->tag('maker.command');

    $services->set('sylius.happy_cms.shared_block.maker.make_shared_block', MakeSharedBlock::class)
        ->private()
        ->args([service('maker.file_manager')])
        ->tag('maker.command');
};
