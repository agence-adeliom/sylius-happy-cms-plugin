<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $env = $_ENV['APP_ENV'] ?? 'dev';

    if (str_starts_with($env, 'test')) {

        $container->import('../../../vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services.xml');
        $container->import('@SyliusHappyCMSPlugin/tests/Behat/Resources/services.xml');

        // Define behat.mink.parameters service BEFORE importing Sylius Behat services
        // This service is normally provided by Behat\MinkExtension during Behat execution
        $container->services()
            ->set('behat.mink.parameters', \FriendsOfBehat\SymfonyExtension\Mink\MinkParameters::class)
                ->public()
                ->args([[
                    'base_url' => 'https://127.0.0.1:8080/',
                    'files_path' => '%kernel.project_dir%/vendor/sylius/sylius/src/Sylius/Behat/Resources/fixtures/',
                    'show_auto' => false,
                    'show_cmd' => null,
                    'show_tmp_dir' => '%kernel.cache_dir%',
                    'browser_name' => 'chrome',
                    'javascript_session' => 'panther',
                    'default_session' => 'symfony',
                ]]);
    }
};
