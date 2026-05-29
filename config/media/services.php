<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\MediaController;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Media\FolderSubscriber;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Media\MediaSubscriber;
use Adeliom\SyliusHappyCMSPlugin\Form\MediaType;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\FileValidator;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaDataLoader;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaHelper;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\UploadRateLimiter;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\UrlValidator;
use Adeliom\SyliusHappyCMSPlugin\Twig\Media\MediaExtension;
use Adeliom\SyliusHappyCMSPlugin\Twig\Media\MediaRuntime;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set('happy.cms.media.controller', MediaController::class)
        ->public()
        ->arg('$manager', service('happy.cms.media.manager'))
        ->tag('controller.service_arguments');
    $services->alias(MediaController::class, 'happy.cms.media.controller')->public();

    $services->set('happy.cms.media.file_validator', FileValidator::class)
        ->public();
    $services->alias(FileValidator::class, 'happy.cms.media.file_validator')->public();

    $services->set('happy.cms.media.upload_rate_limiter', UploadRateLimiter::class)
        ->public()
        ->lazy()
        ->arg('$enabled', false)
        ->arg('$maxRequests', 60)
        ->arg('$period', 60)
        ->arg('$banDuration', 300)
        ->arg('$cache', service('cache.app'))
        ->arg('$logger', service('logger')->nullOnInvalid());
    $services->alias(UploadRateLimiter::class, 'happy.cms.media.upload_rate_limiter')->public();

    $services->set('happy.cms.media.url_validator', UrlValidator::class)
        ->public();
    $services->alias(UrlValidator::class, 'happy.cms.media.url_validator')->public();

    $services->set('happy.cms.media.manager', MediaManager::class)
        ->public()
        ->arg('$filesystem', service('happy.cms.media.storage'))
        ->arg('$helper', service('happy.cms.media.helper'))
        ->arg('$em', service('doctrine.orm.entity_manager'))
        ->arg('$parameters', service('parameter_bag'))
        ->arg('$translator', service('translator'))
        ->arg('$eventDispatcher', service('event_dispatcher'))
        ->arg('$fileValidator', service('happy.cms.media.file_validator'))
        ->arg('$urlValidator', service('happy.cms.media.url_validator'));
    $services->alias(MediaManager::class, 'happy.cms.media.manager')->public();

    $services->set('happy.cms.media.helper', MediaHelper::class)
        ->public()
        ->arg('$parameters', service('parameter_bag'))
        ->arg('$em', service('doctrine.orm.entity_manager'));
    $services->alias(MediaHelper::class, 'happy.cms.media.helper')->public();

    $services->set('happy.cms.media.form.media', MediaType::class)
        ->public()
        ->call('setManager', [service('happy.cms.media.manager')]);

    $services->set('happy.cms.media.twig.happy.cms.media_extension', MediaExtension::class)
        ->public()
        ->arg('$manager', service('happy.cms.media.manager'))
        ->tag('twig.extension');

    $services->set('happy.cms.media.twig.happy.cms.media_runtime', MediaRuntime::class)
        ->public()
        ->arg('$manager', service('happy.cms.media.manager'))
        ->arg('$twig', service('twig'))
        ->arg('$filterManager', service('liip_imagine.filter.manager'))
        ->tag('twig.runtime');

    $services->set('happy.cms.media.imagine.data.loader', MediaDataLoader::class)
        ->public()
        ->args([
            service('happy.cms.media.storage'),
            service('liip_imagine.extension_guesser'),
            service('liip_imagine.binary.loader.default'),
        ])
        ->tag('liip_imagine.binary.loader', ['loader' => 'happy.cms.media_data_loader']);

    $services->set('happy.cms.media.event_listener.folder', FolderSubscriber::class)
        ->public()
        ->arg('$manager', service('happy.cms.media.manager'))
        ->tag('doctrine.event_listener', ['event' => 'preUpdate']);

    $services->set('happy.cms.media.event_listener.media', MediaSubscriber::class)
        ->public()
        ->arg('$manager', service('happy.cms.media.manager'))
        ->tag('doctrine.event_listener', ['event' => 'preUpdate']);
};
