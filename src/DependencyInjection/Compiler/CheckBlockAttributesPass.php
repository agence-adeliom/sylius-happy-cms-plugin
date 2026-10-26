<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Attribute\BlockAttributeResolver;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockAttributeMetadataTrait;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockAttributeMetadataTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Checks the #[AsBlock] / #[AsSharedBlock] declarations at container build time, rather than when the page builder
 * first renders a block:
 *   - the attributes are only allowed when "sylius_easy_crud.attributes.enabled" is true (the project's "attribute
 *     mode", shared with #[AsAdmin]); a project left on the legacy declarations keeps overriding the methods;
 *   - an attribute is only read by BlockAttributeMetadataTrait / SharedBlockAttributeMetadataTrait, on a class
 *     extending AbstractBlock / AbstractSharedBlockType (opt-in in 2.x, see the traits): elsewhere it would be ignored;
 *   - a block using the trait without attribute must override getName(), getIcon() and getFrontEndTemplatePath();
 *   - #[AsSharedBlock] needs a name and a template, unless it takes them from a content block with $block: that
 *     block must be a registered content block, injected into the shared block (setSharedBlockSource()).
 */
final class CheckBlockAttributesPass implements CompilerPassInterface
{
    /** Set by the autoconfiguration on the services whose class carries #[AsBlock] or #[AsSharedBlock]. */
    public const ATTRIBUTE_TAG = 'sylius.happy_cms.block_attribute';

    private const METADATA_METHODS = ['getName', 'getIcon', 'getFrontEndTemplatePath'];

    /** @var array<class-string, array{tag: string, base: class-string, trait: class-string}> */
    private const ATTRIBUTES = [
        AsBlock::class => ['tag' => 'sylius.happy_cms.block', 'base' => AbstractBlock::class, 'trait' => BlockAttributeMetadataTrait::class],
        AsSharedBlock::class => ['tag' => 'sylius.happy_cms.shared_block', 'base' => AbstractSharedBlockType::class, 'trait' => SharedBlockAttributeMetadataTrait::class],
    ];

    public function process(ContainerBuilder $container): void
    {
        $attributesEnabled = $container->hasParameter('sylius_easy_crud.attributes.enabled') &&
            true === $container->getParameter('sylius_easy_crud.attributes.enabled');

        foreach ($container->findTaggedServiceIds(self::ATTRIBUTE_TAG) as $id => $tags) {
            $class = $this->serviceClass($container, $id);
            if (null === $class) {
                continue;
            }

            foreach (self::ATTRIBUTES as $attributeClass => $config) {
                if (null === BlockAttributeResolver::find($class, $attributeClass)) {
                    continue;
                }

                if (!is_subclass_of($class, $config['base'])) {
                    throw new \LogicException(sprintf(
                        'The class "%s" carries #[%s] but does not extend "%s": the attribute is only read on this class. ' .
                        'Extend it, or remove the attribute and implement the metadata methods.',
                        $class,
                        self::shortName($attributeClass),
                        $config['base'],
                    ));
                }

                self::assertUsesTrait($class, $attributeClass, $config['trait']);
            }
        }

        /** @var array<class-string, string> $contentBlocks service id by class */
        $contentBlocks = [];
        foreach ($container->findTaggedServiceIds('sylius.happy_cms.block') as $id => $tags) {
            $class = $this->serviceClass($container, $id);
            if (null !== $class && is_subclass_of($class, AbstractBlock::class)) {
                $contentBlocks[$class] = $id;
            }
        }

        foreach (self::ATTRIBUTES as $attributeClass => $config) {
            foreach ($container->findTaggedServiceIds($config['tag']) as $id => $tags) {
                $class = $this->serviceClass($container, $id);
                if (null === $class || !is_subclass_of($class, $config['base'])) {
                    continue;
                }

                $hasAttribute = null !== BlockAttributeResolver::find($class, $attributeClass);
                if ($hasAttribute && !$attributesEnabled) {
                    throw new \LogicException(sprintf(
                        'The block "%s" carries #[%s] but "sylius_easy_crud.attributes.enabled" is false. Set ' .
                        '"sylius_easy_crud.attributes.enabled: true", or remove the attribute and override the ' .
                        'metadata methods (getName(), getIcon(), getFrontEndTemplatePath()…).',
                        $class,
                        self::shortName($attributeClass),
                    ));
                }

                if ($hasAttribute) {
                    self::assertUsesTrait($class, $attributeClass, $config['trait']);
                    if (AsSharedBlock::class === $attributeClass) {
                        $this->configureSharedBlock($container, $id, $class, $contentBlocks);
                    }
                } elseif (self::usesTrait($class, $config['trait'])) {
                    $this->assertMetadataMethodsOverridden($class, $config['trait'], $attributeClass);
                }
            }
        }
    }

    /**
     * @param class-string                $class
     * @param array<class-string, string> $contentBlocks
     */
    private function configureSharedBlock(ContainerBuilder $container, string $id, string $class, array $contentBlocks): void
    {
        $attribute = BlockAttributeResolver::find($class, AsSharedBlock::class);
        if (null === $attribute) {
            return;
        }

        if (null === $attribute->block) {
            $missing = array_filter(
                ['name' => 'getName', 'template' => 'getFrontEndTemplatePath'],
                static fn (string $method, string $argument): bool => null === $attribute->{$argument} &&
                    self::comesFromTrait($class, $method, SharedBlockAttributeMetadataTrait::class),
                \ARRAY_FILTER_USE_BOTH,
            );
            if ([] !== $missing) {
                throw new \LogicException(sprintf(
                    'The shared block "%s" has no %s: set it in #[AsSharedBlock], or take it from a content block with ' .
                    '#[AsSharedBlock(block: YourContentBlock::class)].',
                    $class,
                    implode(' nor ', array_keys($missing)),
                ));
            }

            return;
        }

        $sourceId = $contentBlocks[$attribute->block] ?? throw new \LogicException(sprintf(
            'The shared block "%s" takes its metadata and its form from "%s" (#[AsSharedBlock(block:)]), which is not a ' .
            'registered content block: it must be a service extending "%s" (autoconfigured, or tagged "sylius.happy_cms.block").',
            $class,
            $attribute->block,
            AbstractBlock::class,
        ));

        $container->findDefinition($id)->addMethodCall('setSharedBlockSource', [new Reference($sourceId)]);
    }

    /**
     * @param class-string $class
     * @param class-string $trait
     * @param class-string $attributeClass
     */
    private function assertMetadataMethodsOverridden(string $class, string $trait, string $attributeClass): void
    {
        $missing = array_filter(
            self::METADATA_METHODS,
            static fn (string $method): bool => self::comesFromTrait($class, $method, $trait),
        );

        if ([] !== $missing) {
            throw new \LogicException(sprintf(
                'The block "%s" has no metadata: set #[%s] on the class, or override %s.',
                $class,
                self::shortName($attributeClass),
                implode(', ', array_map(static fn (string $method): string => $method . '()', $missing)),
            ));
        }
    }

    /**
     * @return class-string|null the class of a concrete, loadable service
     */
    private function serviceClass(ContainerBuilder $container, string $id): ?string
    {
        $definition = $container->findDefinition($id);
        if ($definition->isAbstract()) {
            return null;
        }

        $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
        if (!\is_string($class)) {
            return null;
        }

        $reflection = $container->getReflectionClass($class, false);
        if (null === $reflection || $reflection->isAbstract()) {
            return null;
        }

        return $reflection->getName();
    }

    /**
     * @param class-string $class
     * @param class-string $attributeClass
     * @param class-string $trait
     */
    private static function assertUsesTrait(string $class, string $attributeClass, string $trait): void
    {
        if (!self::usesTrait($class, $trait)) {
            throw new \LogicException(sprintf(
                'The block "%s" carries #[%s] but does not use "%s", which reads the attribute: add ' .
                '"use %s;" to the class or to its base class.',
                $class,
                self::shortName($attributeClass),
                $trait,
                self::shortName($trait),
            ));
        }
    }

    /**
     * Whether the class, one of its parents or one of their traits uses the trait.
     *
     * @param class-string $class
     * @param class-string $trait
     */
    private static function usesTrait(string $class, string $trait): bool
    {
        for ($reflection = new \ReflectionClass($class); false !== $reflection; $reflection = $reflection->getParentClass()) {
            $traits = $reflection->getTraitNames();
            while ([] !== $traits) {
                $name = array_shift($traits);
                if ($trait === $name) {
                    return true;
                }
                $traits = [...$traits, ...(new \ReflectionClass($name))->getTraitNames()];
            }
        }

        return false;
    }

    /**
     * Whether the implementation of the method is the one of the trait (not overridden by the class or a parent).
     *
     * @param class-string $class
     * @param class-string $trait
     */
    private static function comesFromTrait(string $class, string $method, string $trait): bool
    {
        return (new \ReflectionMethod($class, $method))->getFileName() === (new \ReflectionClass($trait))->getFileName();
    }

    /**
     * @param class-string $class
     */
    private static function shortName(string $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }
}
