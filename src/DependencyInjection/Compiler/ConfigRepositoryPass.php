<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Points SyliusHappyCMSExtension::CONFIG_REPOSITORY to the repository of the Sylius resource
 * that holds the configured config model, so the plugin does not depend on the resource name.
 *
 * The repository is not aliased by its class name: an application that loads `src/*` as
 * services redefines that id with plain autowiring, which cannot build a Sylius repository.
 */
final class ConfigRepositoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('sylius_happy_cms.config.config_model') || !$container->hasParameter('sylius.resources')) {
            return;
        }

        $model = $container->getParameterBag()->resolveValue($container->getParameter('sylius_happy_cms.config.config_model'));

        /** @var array<string, array{classes?: array{model?: string}}> $resources */
        $resources = $container->getParameter('sylius.resources');

        foreach ($resources as $alias => $resource) {
            if (($resource['classes']['model'] ?? null) !== $model) {
                continue;
            }

            [$applicationName, $resourceName] = explode('.', $alias, 2);
            $container->setAlias(SyliusHappyCMSExtension::CONFIG_REPOSITORY, sprintf('%s.repository.%s', $applicationName, $resourceName))->setPublic(true);

            return;
        }

        throw new InvalidArgumentException(sprintf('No Sylius resource uses the config model "%s" set in "sylius_happy_cms.config.config_model". Declare it under "sylius_resource.resources".', is_string($model) ? $model : get_debug_type($model)));
    }
}
