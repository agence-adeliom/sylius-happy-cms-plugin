<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Doctrine\FlexibleContentType;
use Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaType;
use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\Seo;
use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\SeoInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine', [
        'dbal' => [
            'types' => [
                'adeliom_happy_cms_flexible_content_type' => FlexibleContentType::class,
                'happy_cms_media_type' => MediaType::class,
            ],
        ],
        'orm' => [
            'resolve_target_entities' => [
                SeoInterface::class => Seo::class,
            ],
        ],
    ]);
};
