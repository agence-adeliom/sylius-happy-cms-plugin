<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Attribute\BlockAttributeResolver;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Checks the #[AsBlock] / #[AsSharedBlock] declarations at container build time, rather than when the page builder
 * first renders a block:
 *   - the attributes are only allowed when "sylius_easy_crud.attributes.enabled" is true (the project's "attribute
 *     mode", shared with #[AsAdmin]); a project left on the legacy declarations keeps overriding the methods;
 *   - an attribute is only read by AbstractBlock / AbstractSharedBlockType: on any other class it would be ignored;
 *   - a block without attribute must override getName(), getIcon() and getFrontEndTemplatePath().
 */
final class CheckBlockAttributesPass implements CompilerPassInterface
{
    /** Set by the autoconfiguration on the services whose class carries #[AsBlock] or #[AsSharedBlock]. */
    public const ATTRIBUTE_TAG = 'sylius.happy_cms.block_attribute';

    private const METADATA_METHODS = ['getName', 'getIcon', 'getFrontEndTemplatePath'];

    /** @var array<class-string, array{tag: string, base: class-string}> */
    private const ATTRIBUTES = [
        AsBlock::class => ['tag' => 'sylius.happy_cms.block', 'base' => AbstractBlock::class],
        AsSharedBlock::class => ['tag' => 'sylius.happy_cms.shared_block', 'base' => AbstractSharedBlockType::class],
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
                if (null !== BlockAttributeResolver::find($class, $attributeClass) && !is_subclass_of($class, $config['base'])) {
                    throw new \LogicException(sprintf(
                        'The class "%s" carries #[%s] but does not extend "%s": the attribute is only read by this class. ' .
                        'Extend it, or remove the attribute and implement the metadata methods.',
                        $class,
                        self::shortName($attributeClass),
                        $config['base'],
                    ));
                }
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

                if (!$hasAttribute) {
                    $this->assertMetadataMethodsOverridden($class, $config['base'], $attributeClass);
                }
            }
        }
    }

    /**
     * @param class-string $class
     * @param class-string $base
     * @param class-string $attributeClass
     */
    private function assertMetadataMethodsOverridden(string $class, string $base, string $attributeClass): void
    {
        $missing = array_filter(
            self::METADATA_METHODS,
            static fn (string $method): bool => $base === (new \ReflectionMethod($class, $method))->getDeclaringClass()->getName(),
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
     */
    private static function shortName(string $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }
}
