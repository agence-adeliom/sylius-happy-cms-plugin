<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepositoryInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Points a plugin repository id (or interface) to the repository of a model configured in
 * `sylius_happy_cms`, without depending on the name of the Sylius resource in the project.
 * In this order:
 *
 * 1. The Sylius repository of the resource whose model is the configured one (or a subclass of it),
 *    when it implements the repository interface (`sylius_happy_cms.config` in the test application).
 * 2. The `repositoryClass` of the Doctrine mapping of that model, when it is a service implementing
 *    the interface (a resource that declares the generic Sylius repository, as Tryba with `app.config`).
 * 3. `doctrine->getRepository(<model>)`, so the container still builds (2.x, no BC break).
 *
 * The repository is not aliased by the `*_repository` class name of the configuration: an
 * application that loads `src/*` as services redefines that id with plain autowiring, which cannot
 * build a Sylius repository.
 */
final class ResourceRepositoryPass implements CompilerPassInterface
{
    /**
     * @param string       $modelParameter      parameter holding the configured model class
     * @param class-string $repositoryInterface interface the repository must implement
     * @param string       $serviceId           id (or interface alias) to define
     * @param string|null  $legacyServiceId     id a project may already define for $serviceId: kept when it exists
     * @param bool         $failOnAmbiguity     several resources extending the model: error, else $serviceId stays undefined
     */
    public function __construct(
        private readonly string $modelParameter,
        private readonly string $repositoryInterface,
        private readonly string $serviceId,
        private readonly ?string $legacyServiceId = null,
        private readonly bool $failOnAmbiguity = false,
    ) {
    }

    /** Repository of `ConfigValueResolver` (`happy_cms_config()`). */
    public static function config(): self
    {
        return new self('sylius_happy_cms.config.config_model', ConfigRepositoryInterface::class, SyliusHappyCMSExtension::CONFIG_REPOSITORY, failOnAmbiguity: true);
    }

    /** Autowiring of PageRepositoryInterface, previously aliased to `happy_cms_page.page.repository`, never defined. */
    public static function page(): self
    {
        return new self('sylius_happy_cms.page.page_model', PageRepositoryInterface::class, PageRepositoryInterface::class, 'happy_cms_page.page.repository');
    }

    /** Autowiring of RouteRepositoryInterface, previously aliased to `happy_cms_page.route.repository`, never defined. */
    public static function route(): self
    {
        return new self('sylius_happy_cms.route.route_model', RouteRepositoryInterface::class, RouteRepositoryInterface::class, 'happy_cms_page.route.repository');
    }

    public function process(ContainerBuilder $container): void
    {
        if (null !== $this->legacyServiceId && $container->has($this->legacyServiceId)) {
            $container->setAlias($this->serviceId, $this->legacyServiceId);

            return;
        }

        if (!$container->hasParameter($this->modelParameter)) {
            return;
        }

        $configuredModel = $container->getParameterBag()->resolveValue($container->getParameter($this->modelParameter));
        if (!is_string($configuredModel)) {
            return;
        }

        $model = $configuredModel;
        $resource = $this->findResource($container, $configuredModel);
        if (false === $resource) {
            return;
        }

        if (null !== $resource) {
            [$alias, $model] = $resource;
            [$applicationName, $resourceName] = explode('.', $alias, 2);
            $syliusRepository = sprintf('%s.repository.%s', $applicationName, $resourceName);

            if ($this->isRepository($container, $syliusRepository)) {
                $container->setAlias($this->serviceId, $syliusRepository)->setPublic(true);

                return;
            }
        }

        $mappedRepository = $this->getMappedRepositoryClass($model);
        if (null !== $mappedRepository && $this->isRepository($container, $mappedRepository)) {
            $container->setAlias($this->serviceId, $mappedRepository)->setPublic(true);

            return;
        }

        $container->setDefinition($this->serviceId, (new Definition($this->repositoryInterface))
            ->setFactory([new Reference('doctrine'), 'getRepository'])
            ->setArguments([$model])
            ->setPublic(true));
    }

    /**
     * The resource whose model is the configured model, else the only one whose model extends it.
     *
     * @return array{string, string}|false|null [resource alias, resource model]; null: no resource; false: ambiguous, leave the id undefined
     */
    private function findResource(ContainerBuilder $container, string $configuredModel): array|false|null
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

            if ($resourceModel === $configuredModel) {
                return [$alias, $resourceModel];
            }

            if (is_a($resourceModel, $configuredModel, true)) {
                $subclasses[$alias] = $resourceModel;
            }
        }

        if (count($subclasses) > 1) {
            if (!$this->failOnAmbiguity) {
                return false;
            }

            throw new InvalidArgumentException(sprintf('Several Sylius resources extend the model "%s" set in "%s": "%s". Set the model of the resource in this option.', $configuredModel, $this->modelParameter, implode('", "', array_keys($subclasses))));
        }

        foreach ($subclasses as $alias => $resourceModel) {
            return [$alias, $resourceModel];
        }

        return null;
    }

    private function isRepository(ContainerBuilder $container, string $id): bool
    {
        if (!$container->has($id)) {
            return false;
        }

        $class = $container->getParameterBag()->resolveValue($container->findDefinition($id)->getClass() ?? $id);

        return is_string($class) && is_a($class, $this->repositoryInterface, true);
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
