<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/*
 * Configuration of the plugin, to import from the project:
 *
 *     imports:
 *         - { resource: "@SyliusHappyCMSPlugin/config/config.php" }
 *
 * Override any option in your own `config/packages/sylius_happy_cms.php` (or `.yaml`), e.g.:
 *
 *     $container->extension('sylius_happy_cms', [
 *         'media' => [
 *             'storage_name' => 'uploads.storage',
 *             'base_url' => '/media/download',
 *         ],
 *     ]);
 */
return static function (ContainerConfigurator $container): void {
    $container->import('packages/cmf_routing.php');
    $container->import('packages/doctrine.php');
    $container->import('packages/sylius_ui.php');
    $container->import('packages/sylius_happy_cms.php');
    $container->import('packages/flysystem.php');
    $container->import('packages/tinymce.php');
};
