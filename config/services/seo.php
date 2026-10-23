<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\DataCollector\Seo\SeoCollector;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Seo\SitemapSubscriber;
use Adeliom\SyliusHappyCMSPlugin\Form\Seo\SeoType;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\BreadcrumbCollection;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap\PagesSitemapDumper;
use Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(SeoExtension::class)
        ->args([
            service('twig'),
            service('event_dispatcher'),
            service('happy_cms_seo.breadcrumb'),
            param('sylius_happy_cms.seo.title'),
            param('sylius_happy_cms.seo.breadcrumbs'),
            param('sylius_happy_cms.seo.templates'),
        ])
        ->tag('twig.extension');

    $services->set('happy_cms_seo.breadcrumb', BreadcrumbCollection::class)
        ->public()
        ->call('setGenerator', [service('router.default')]);
    $services->alias(BreadcrumbCollection::class, 'happy_cms_seo.breadcrumb');

    $services->set(SeoType::class)
        ->public();

    $services->set(SeoCollector::class)
        ->args([
            service('happy_cms_seo.breadcrumb'),
            param('sylius_happy_cms.seo.title'),
            param('sylius_happy_cms.seo.enable_profiler'),
            param('sylius_happy_cms.seo.ignore_profiler'),
        ])
        ->tag('data_collector', [
            'template' => '@SyliusHappyCMSPlugin/profiler/seo/collector.html.twig',
            'id' => SeoCollector::class,
        ]);

    $services->set('sylius_happy_cms.seo.sitemap_subscriber', SitemapSubscriber::class)
        ->args([
            param('sylius_happy_cms.page.sitemap'),
            tagged_iterator('sylius_happy_cms.seo.sitemap_dumpable'),
            service('event_dispatcher'),
        ])
        ->tag('kernel.event_subscriber', ['priority' => 100]);

    $services->set(PagesSitemapDumper::class)
        ->public()
        ->autowire()
        ->tag('sylius_happy_cms.seo.sitemap_dumpable');
};
