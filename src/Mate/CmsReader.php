<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Mate;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\FlexibleContentField;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Form\Block\BlockCollectionType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Reads the CMS objects of the host application from its booted container, for the Mate tools: the block registries,
 * the Sylius resource registry (`sylius.resources`), the easy-crud Admins and their flexible content fields, and the
 * Doctrine entities.
 *
 * @phpstan-type CmsResource array{alias: string, model: class-string, admin: class-string<AdminInterface>|null}
 * @phpstan-type FlexibleField array{property: string, blocks: list<string>|null}
 */
final class CmsReader
{
    /** Public locator of the Admins, registered by easy-crud since its Mate tools. */
    private const ADMIN_LOCATOR = 'sylius_easy_crud.admin_locator';

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
     * The Admin service, as the form registry uses it, without building its form when easy-crud exposes its locator.
     *
     * @param class-string<AdminInterface> $adminClass
     */
    public function admin(string $adminClass): AdminInterface
    {
        if ($this->container->has(self::ADMIN_LOCATOR)) {
            $locator = $this->container->get(self::ADMIN_LOCATOR);
            if ($locator instanceof ContainerInterface && $locator->has($adminClass)) {
                $admin = $locator->get($adminClass);
                \assert($admin instanceof AdminInterface);

                return $admin;
            }
        }

        // Older easy-crud: the Admin is a private form type, reached through a built form (needs the database).
        $formFactory = $this->container->get('form.factory');
        \assert($formFactory instanceof FormFactoryInterface);
        $admin = $formFactory->create($adminClass, null, ['page_name' => Crud::PAGE_EDIT])->getConfig()->getType()->getInnerType();
        \assert($admin instanceof AdminInterface);

        return $admin;
    }

    /**
     * The flexible content fields of the edit page of an Admin, with the block types they declare (null: every block).
     * The fields of a TranslationField are included (their property is read on the translation), and so are those of
     * the `flexible_content:<locale>` context, where the plugin pages declare their translated content.
     *
     * @return list<FlexibleField>
     */
    public function flexibleContentFields(AdminInterface $admin, string $locale): array
    {
        $fields = [];
        foreach ([null, 'flexible_content:' . $locale] as $context) {
            foreach ($this->flattenFields($admin->configureFields(Crud::PAGE_EDIT, $context)) as $field) {
                $dto = $field->getAsDto();
                // The field class reaches the DTO only once the CRUD controller processes the fields: read the object.
                if (!$field instanceof FlexibleContentField && BlockCollectionType::class !== $dto->getFormType()) {
                    continue;
                }

                $blocks = $dto->getCustomOptions()->get(FlexibleContentField::OPTION_BLOCKS);
                $fields[$dto->getProperty()] ??= [
                    'property' => $dto->getProperty(),
                    'blocks' => \is_array($blocks) && [] !== $blocks ? array_values(array_filter($blocks, 'is_string')) : null,
                ];
            }
        }

        return array_values($fields);
    }

    /**
     * @param iterable<FieldInterface> $fields
     *
     * @return iterable<FieldInterface>
     */
    private function flattenFields(iterable $fields): iterable
    {
        foreach ($fields as $field) {
            yield $field;

            if ($field instanceof TranslationField) {
                $children = $field->getAsDto()->getCustomOptions()->get('fieldsDto');
                yield from $this->flattenFields(\is_array($children) ? array_filter($children, static fn (mixed $child): bool => $child instanceof FieldInterface) : []);
            }
        }
    }

    /**
     * Reads a flexible content property of an entity, from its translation in the given locale when the entity does
     * not expose it (the plugin pages store their content on the translation).
     *
     * @return array<mixed>|null null when the property cannot be read
     */
    public function readContent(object $entity, string $property, string $locale): ?array
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        if ($entity instanceof TranslatableInterface) {
            $entity->setCurrentLocale($locale);
            $entity->setFallbackLocale($locale);
        }

        $targets = [$entity];
        if ($entity instanceof TranslatableInterface) {
            $targets[] = $entity->getTranslation($locale);
        }

        foreach ($targets as $target) {
            if ($accessor->isReadable($target, $property)) {
                $value = $accessor->getValue($target, $property);

                return \is_array($value) ? $value : [];
            }
        }

        return null;
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
