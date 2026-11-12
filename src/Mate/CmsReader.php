<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Mate;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;

/**
 * Reads the CMS objects of the host application from its booted container, for the Mate tools: the block registries,
 * the Sylius resource registry (`sylius.resources`) with the easy-crud Admins, and the Doctrine entities.
 *
 * @phpstan-type CmsResource array{alias: string, model: class-string, admin: class-string<AdminInterface>|null}
 */
final class CmsReader
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function blocks(): BlockCollection
    {
        $collection = $this->container->get(BlockCollection::class);
        \assert($collection instanceof BlockCollection);

        return $collection;
    }

    public function sharedBlocks(): SharedBlockCollection
    {
        $collection = $this->container->get(SharedBlockCollection::class);
        \assert($collection instanceof SharedBlockCollection);

        return $collection;
    }

    /**
     * The Sylius resource matching an alias (`app.page`) or a model class.
     *
     * @return CmsResource|null
     */
    public function resource(string $aliasOrClass): ?array
    {
        $resources = $this->parameter('sylius.resources');
        if (!\is_array($resources)) {
            return null;
        }

        $byClass = null;
        foreach ($resources as $alias => $resource) {
            $classes = \is_array($resource) && \is_array($resource['classes'] ?? null) ? $resource['classes'] : [];
            $model = $classes['model'] ?? null;
            if (!\is_string($alias) || !\is_string($model) || !class_exists($model)) {
                continue;
            }

            $form = $classes['form'] ?? null;
            $description = [
                'alias' => $alias,
                'model' => $model,
                'admin' => \is_string($form) && is_a($form, AdminInterface::class, true) ? $form : null,
            ];

            if ($alias === $aliasOrClass) {
                return $description;
            }

            // Several aliases may share a model (filtered admins): the first one with an Admin wins.
            if (null === $byClass && is_a($aliasOrClass, $model, true) && null !== $description['admin']) {
                $byClass = $description;
            }
        }

        return $byClass;
    }

    /**
     * Whether the entity stores page builder blocks: content-editable, with its `contentBlocks` association mapped (a
     * project without page builder leaves it out, see `page_builder.map_page_content_block`).
     */
    public function hasPageBuilder(object $entity): bool
    {
        return $entity instanceof ContentEditableInterface &&
            $this->manager($entity::class)->getClassMetadata($entity::class)->hasAssociation('contentBlocks');
    }

    /**
     * @param class-string $class
     */
    public function find(string $class, int|string $id): ?object
    {
        return $this->manager($class)->find($class, $id);
    }

    /**
     * @param class-string $class
     */
    public function manager(string $class): ObjectManager
    {
        $doctrine = $this->container->get('doctrine');
        \assert($doctrine instanceof ManagerRegistry);

        $manager = $doctrine->getManagerForClass($class);
        if (null === $manager) {
            throw new \RuntimeException(sprintf('"%s" is not a Doctrine entity.', $class));
        }

        return $manager;
    }

    public function defaultLocale(): string
    {
        foreach (['locale', 'kernel.default_locale'] as $name) {
            $locale = $this->parameter($name);
            if (\is_string($locale) && '' !== $locale) {
                return $locale;
            }
        }

        return 'en';
    }

    public function parameter(string $name): mixed
    {
        if (!$this->container instanceof \Symfony\Component\DependencyInjection\ContainerInterface ||
            !$this->container->hasParameter($name)) {
            return null;
        }

        return $this->container->getParameter($name);
    }

    public function service(string $id): ?object
    {
        $service = $this->container->has($id) ? $this->container->get($id) : null;

        return \is_object($service) ? $service : null;
    }
}
