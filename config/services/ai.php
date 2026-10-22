<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Command\AI\ShowBlockSchemaCommand;
use Adeliom\SyliusHappyCMSPlugin\Services\AI\AIBundleDetector;
use Adeliom\SyliusHappyCMSPlugin\Services\AI\BlockContentGenerator;
use Adeliom\SyliusHappyCMSPlugin\Services\AI\BlockSchemaSerializer;
use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // AI Services
    $services->set(AIBundleDetector::class)
        ->public()
        ->args([service('kernel')]);
    $services->alias('sylius.happy_cms.ai.bundle_detector', AIBundleDetector::class);

    $services->set(BlockSchemaSerializer::class)
        ->public()
        ->args([
            service('sylius.happy_cms.block_collection'),
            service('form.factory'),
            service('translator'),
        ]);
    $services->alias('sylius.happy_cms.ai.block_schema_serializer', BlockSchemaSerializer::class);

    $services->set(BlockContentGenerator::class)
        ->public()
        ->args([
            service('sylius.happy_cms.ai.block_schema_serializer'),
            service(AgentInterface::class)->ignoreOnInvalid(), // Optional: will be null if AI bundle is not configured
            param('sylius_happy_cms.page_builder.ai_generate_content_prompt'),
        ]);
    $services->alias('sylius.happy_cms.ai.block_content_generator', BlockContentGenerator::class);

    $services->set('sylius.happy_cms.ai.show_block_list', ShowBlockSchemaCommand::class)
        ->args([service('sylius.happy_cms.ai.block_schema_serializer')])
        ->tag('console.command');
};
