<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('flysystem', [
        'storages' => [
            'sylius.storage' => [
                'adapter' => 'local',
                'options' => [
                    'directory' => '%sylius_core.images_dir%',
                ],
            ],
            'uploads.storage.local' => [
                'adapter' => 'local',
                'options' => [
                    'directory' => '%kernel.project_dir%/public/media/download/',
                ],
                'visibility' => 'public',
                'directory_visibility' => 'public',
            ],
            'uploads.storage' => [
                'adapter' => 'lazy',
                'options' => [
                    'source' => 'uploads.storage.local',
                ],
                'visibility' => 'public',
                'directory_visibility' => 'public',
            ],
        ],
    ]);
};
