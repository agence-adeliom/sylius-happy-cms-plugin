<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepositoryInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines SyliusHappyCMSExtension::CONFIG_REPOSITORY without depending on the name of the config
 * resource in the project. In this order:
 *
 * 1. The Sylius repository of the resource whose model is `config_model` (or a subclass of it), when
 *    it is a config repository (plugin test application: `sylius_happy_cms.config`).
 * 2. The `repositoryClass` of the Doctrine mapping of that model, when it is a config repository
 *    service (a resource that declares the generic Sylius repository, as Tryba with `app.config`).
 * 3. `doctrine->getRepository(<model>)`, so the container still builds (2.x, no BC break).
 *
 * The repository is not aliased by the `config_repository` class name: an application that loads
 * `src/*` as services redefines that id with plain autowiring, which cannot build a Sylius repository.
 */
final class ConfigRepositoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('sylius_happy_cms.config.config_model')) {
            return;
        }

        $configModel = $container->getParameterBag()->resolveValue($container->getParameter('sylius_happy_cms.config.config_model'));
        if (!is_string($configModel)) {
            return;
        }

        $model = $configModel;
        $resource = $this->findResource($container, $configModel);
        if (null !== $resource) {
            [$alias, $model] = $resource;
            [$applicationName, $resourceName] = explode('.', $alias, 2);
            $syliusRepository = sprintf('%s.repository.%s', $applicationName, $resourceName);

            if ($this->isConfigRepository($container, $syliusRepository)) {
                $container->setAlias(SyliusHappyCMSExtension::CONFIG_REPOSITORY, $syliusRepository)->setPublic(true);

                return;
            }
        }

        $mappedRepository = $this->getMappedRepositoryClass($model);
        if (null !== $mappedRepository && $this->isConfigRepository($container, $mappedRepository)) {
            $container->setAlias(SyliusHappyCMSExtension::CONFIG_REPOSITORY, $mappedRepository)->setPublic(true);

            return;
        }

        $container->setDefinition(SyliusHappyCMSExtension::CONFIG_REPOSITORY, (new Definition(ConfigRepositoryInterface::class))
            ->setFactory([new Reference('doctrine'), 'getRepository'])
            ->setArguments([$model])
            ->setPublic(true));
    }

    /**
     * The resource whose model is the config model, else the only one whose model extends it.
     *
     * @return array{string, string}|null [resource alias, resource model]
     */
    private function findResource(ContainerBuilder $container, string $configModel): ?array
    {
        if (!$container->hasParameter('sylius.resources')) {
            return null;
        }

        /** @var array<string, array{classes?: array{model?: mixed}}> $resources */
        $resources = $container->getParameter('sylius.resources');

        $subclasses = [];
        foreach ($resources as $alias => $resource) {
            $resourceModel = $resource['classes']['model'] ?? null;
            if (!is_string($resourceModel) || !str_contains($alias, '.')) {
                continue;
            }

            if ($resourceModel === $configModel) {
                return [$alias, $resourceModel];
            }

            if (is_a($resourceModel, $configModel, true)) {
                $subclasses[$alias] = $resourceModel;
            }
        }

        if (count($subclasses) > 1) {
            throw new InvalidArgumentException(sprintf('Several Sylius resources extend the config model "%s" set in "sylius_happy_cms.config.config_model": "%s". Set the model of the config resource in this option.', $configModel, implode('", "', array_keys($subclasses))));
        }

        foreach ($subclasses as $alias => $resourceModel) {
            return [$alias, $resourceModel];
        }

        return null;
    }

    private function isConfigRepository(ContainerBuilder $container, string $id): bool
    {
        if (!$container->has($id)) {
            return false;
        }

        $class = $container->getParameterBag()->resolveValue($container->findDefinition($id)->getClass() ?? $id);

        return is_string($class) && is_a($class, ConfigRepositoryInterface::class, true);
    }

    private function getMappedRepositoryClass(string $model): ?string
    {
        if (!class_exists($model)) {
            return null;
        }

        foreach ((new \ReflectionClass($model))->getAttributes(Entity::class) as $attribute) {
            return $attribute->newInstance()->repositoryClass;
        }

        return null;
    }
}
