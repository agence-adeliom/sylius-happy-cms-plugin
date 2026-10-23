<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Admin\Field\Configurator\FlexibleContentConfigurator;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\Configurator\SharedBlockConfigurator;
use Adeliom\SyliusHappyCMSPlugin\Block\AccordionBlockType;
use Adeliom\SyliusHappyCMSPlugin\Block\CtaBlockType;
use Adeliom\SyliusHappyCMSPlugin\Block\GalleryBlockType;
use Adeliom\SyliusHappyCMSPlugin\Block\KeyFeaturesBlockType;
use Adeliom\SyliusHappyCMSPlugin\Block\SeoBlockType;
use Adeliom\SyliusHappyCMSPlugin\Block\SharedBlockType;
use Adeliom\SyliusHappyCMSPlugin\Block\TextCtaBlockType;
use Adeliom\SyliusHappyCMSPlugin\Block\TextImageCtaBlockType;
use Adeliom\SyliusHappyCMSPlugin\Block\WysiwygBlockType;
use Adeliom\SyliusHappyCMSPlugin\Controller\SharedBlock\SharedBlockController;
use Adeliom\SyliusHappyCMSPlugin\DataCollector\Block\BlockCollector;
use Adeliom\SyliusHappyCMSPlugin\DataCollector\SharedBlock\SharedBlockCollector;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper as BlockHelper;
use Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock\Helper as ContentBlockHelper;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\Helper as SharedBlockHelper;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockCollection;
use Adeliom\SyliusHappyCMSPlugin\SharedBlock\ExampleType;
use Adeliom\SyliusHappyCMSPlugin\Twig\Block\BlockExtension;
use Adeliom\SyliusHappyCMSPlugin\Twig\Cmf\RouteExtension;
use Adeliom\SyliusHappyCMSPlugin\Twig\ContentBlock\ContentBlockExtension;
use Adeliom\SyliusHappyCMSPlugin\Twig\SharedBlock\SharedBlockExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set(BlockCollection::class)
        ->autoconfigure()
        ->public()
        ->args([tagged_iterator('sylius.happy_cms.block')]);
    $services->alias('sylius.happy_cms.block_collection', BlockCollection::class);

    $services->set(FlexibleContentConfigurator::class)
        ->public()
        ->args([
            service('sylius.happy_cms.block_collection'),
            service('sylius.happy_cms.shared_block.block_collection'),
        ])
        ->tag('easy.fields.configurator');

    $services->set(SharedBlockConfigurator::class)
        ->public()
        ->args([service('sylius.happy_cms.shared_block.block_collection')])
        ->tag('easy.fields.configurator');

    $services->set(BlockExtension::class)
        ->public()
        ->tag('twig.extension');
    $services->alias('sylius.happy_cms.block.twig_extension', BlockExtension::class);

    $services->set(BlockHelper::class)
        ->public()
        ->args([
            service('twig'),
            service('event_dispatcher'),
            service('sylius.happy_cms.block_collection'),
            service('adeliom.sylius.asset.renderer'),
        ])
        ->tag('twig.runtime');
    $services->alias('sylius.happy_cms.block_helper', BlockHelper::class);

    $services->set(BlockCollector::class)
        ->args([service('sylius.happy_cms.block_helper')])
        ->tag('data_collector', [
            'template' => '@SyliusHappyCMSPlugin/profiler/block/collector.html.twig',
            'id' => BlockCollector::class,
        ]);

    // content block (new system)

    $services->set(ContentBlockExtension::class)
        ->public()
        ->tag('twig.extension');
    $services->alias('sylius.happy_cms.content_block.twig_extension', ContentBlockExtension::class);

    $services->set(ContentBlockHelper::class)
        ->public()
        ->args([
            service('twig'),
            service('event_dispatcher'),
            service('sylius.happy_cms.block_collection'),
            service('adeliom.sylius.asset.renderer'),
        ])
        ->tag('twig.runtime');
    $services->alias('sylius.happy_cms.content_block_helper', ContentBlockHelper::class);

    // shared block

    $services->set(SharedBlockCollection::class)
        ->autoconfigure()
        ->public()
        ->args([tagged_iterator('sylius.happy_cms.shared_block')]);
    $services->alias('sylius.happy_cms.shared_block.block_collection', SharedBlockCollection::class);

    $services->set(SharedBlockExtension::class)
        ->public()
        ->tag('twig.extension');

    $services->set(RouteExtension::class)
        ->public()
        ->tag('twig.extension');

    $services->alias('sylius.happy_cms.shared_block.twig_extension', SharedBlockExtension::class);

    $services->set('sylius.happy_cms.shared_block.shared_block_type', SharedBlockType::class)
        ->public()
        ->args([
            service('doctrine.orm.entity_manager'),
            service('translator'),
            service('form.factory'),
        ])
        ->tag('sylius.happy_cms.block')
        ->tag('form.type');

    $services->set(SharedBlockHelper::class)
        ->public()
        ->args([
            service('twig'),
            service('event_dispatcher'),
            service('sylius.happy_cms.shared_block.block_collection'),
            service('doctrine.orm.entity_manager'),
            service('request_stack'),
            service('adeliom.sylius.asset.renderer'),
        ])
        ->tag('twig.runtime');
    $services->alias('sylius.happy_cms.shared_block.block_helper', SharedBlockHelper::class);

    $services->set(SharedBlockCollector::class)
        ->args([service('sylius.happy_cms.shared_block.block_helper')])
        ->tag('data_collector', [
            'template' => '@SyliusHappyCMSPlugin/profiler/shared_block/collector.html.twig',
            'id' => SharedBlockCollector::class,
        ]);

    $services->set(SharedBlockController::class)
        ->args([
            service('twig'),
            service('sylius.happy_cms.shared_block.block_collection'),
        ])
        ->tag('controller.service_arguments')
        ->tag('container.service_subscriber');

    $services->set(ExampleType::class)
        ->public()
        ->autoconfigure()
        ->tag('sylius.happy_cms.shared_block');

    // all flex boxes
    $flexBlocks = [
        'sylius.happy_cms.blocks.accordion_type' => AccordionBlockType::class,
        'sylius.happy_cms.blocks.cta_block_type' => CtaBlockType::class,
        'sylius.happy_cms.blocks.gallery_block_type' => GalleryBlockType::class,
        'sylius.happy_cms.blocks.key_feature_block_type' => KeyFeaturesBlockType::class,
        'sylius.happy_cms.blocks.text_image_cta_block_type' => TextImageCtaBlockType::class,
        'sylius.happy_cms.blocks.text_cta_block_type' => TextCtaBlockType::class,
        'sylius.happy_cms.blocks.wysiwyg_block_type' => WysiwygBlockType::class,
        'sylius.happy_cms.blocks.seo_block_type' => SeoBlockType::class,
    ];

    foreach ($flexBlocks as $id => $class) {
        $services->set($id, $class)
            ->public()
            ->args([
                service('doctrine.orm.entity_manager'),
                service('translator'),
                service('form.factory'),
                service('assets.packages'),
            ])
            ->tag('sylius.happy_cms.block')
            ->tag('form.type');
    }
};
