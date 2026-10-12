<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageContentBlock;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Leaves the `PageContentBlock` model (relational page builder) out of the classes listed by the Doctrine attribute
 * drivers when `sylius_happy_cms.page_builder.map_page_content_block` is false.
 *
 * The model is a mapped superclass whose `contentOwner` association is the owning side of `PageInterface::$contentBlocks`.
 * A project whose page does not declare `contentBlocks` (no page builder) fails `doctrine:schema:validate` as soon as
 * the plugin `Entity/Page` directory is mapped. Leaving the file out of the listing keeps the other page models mapped
 * and still read as parent classes; a project extending `PageContentBlock` is not affected (its own class is listed).
 */
final class PageContentBlockMappingPass implements CompilerPassInterface
{
    public const PARAMETER = 'sylius_happy_cms.page_builder.map_page_content_block';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter(self::PARAMETER) || false !== $container->getParameter(self::PARAMETER)) {
            return;
        }

        if (!$container->hasParameter('doctrine.entity_managers')) {
            return;
        }

        $entityManagers = $container->getParameter('doctrine.entity_managers');
        if (!is_array($entityManagers)) {
            return;
        }

        $file = (new \ReflectionClass(PageContentBlock::class))->getFileName();
        if (false === $file) {
            return;
        }

        foreach (array_keys($entityManagers) as $name) {
            $driver = sprintf('doctrine.orm.%s_attribute_metadata_driver', $name);
            if ($container->hasDefinition($driver)) {
                $container->getDefinition($driver)->addMethodCall('addExcludePaths', [[$file]]);
            }
        }
    }
}
