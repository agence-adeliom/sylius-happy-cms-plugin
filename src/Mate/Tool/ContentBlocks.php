<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Mate\Tool;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock\Helper as ContentBlockHelper;
use Adeliom\SyliusHappyCMSPlugin\Mate\BlockRenderer;
use Adeliom\SyliusHappyCMSPlugin\Mate\CmsReader;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\MateExtension\Kernel\HostContainerProvider;
use Sylius\MateExtension\Output\Envelope;
use Sylius\Resource\Model\TranslatableInterface;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Twig\Environment;

final class ContentBlocks
{
    /** Entities with an unknown block type reported by a scan. */
    private const MAX_UNKNOWN_OCCURRENCES = 20;

    public function __construct(
        private readonly HostContainerProvider $host,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[MateTool(
        name: 'happy_cms_content_blocks',
        description: 'Read the flex blocks stored in the flexible content fields (JSON) and in the page builder (relational blocks) of an easy-crud resource (alias such as app.page). With "id": the blocks of that entity (position, block_type, published, registered in the block registry) and, with render=true, each block rendered on its own like the front does: rendered (HTML length), empty, unpublished, unknown_type or error (the exception, even when a decorating helper swallows it). "index" restricts to one block and adds its settings. Without "id": scans up to "limit" entities and counts the block types stored, flagging those missing from the registry. Rendering runs the block code (queries, HTTP clients).',
    )]
    public function __invoke(string $alias, ?string $id = null, ?string $locale = null, bool $render = false, ?int $index = null, int $limit = 1000): array
    {
        return Envelope::guard(function () use ($alias, $id, $locale, $render, $index, $limit): array {
            $reader = new CmsReader($this->host->getContainer());
            $resource = $reader->resource($alias);
            if (null === $resource || null === $resource['admin']) {
                return Envelope::empty(sprintf('"%s" is not an easy-crud resource. Call easy_crud_list_admins to find it.', $alias));
            }

            $locale ??= $reader->defaultLocale();
            $properties = array_column($reader->flexibleContentFields($reader->admin($resource['admin']), $locale), 'property');
            if ([] === $properties && !is_a($resource['model'], ContentEditableInterface::class, true)) {
                return Envelope::empty(sprintf('The Admin of "%s" has no FlexibleContentField on its update page, and its model has no page builder blocks.', $alias));
            }

            if (null === $id) {
                return $this->scan($reader, $resource['model'], $properties, max(1, $limit));
            }

            $entity = $reader->find($resource['model'], $id);
            if (null === $entity) {
                return Envelope::empty(sprintf('No %s has the id "%s".', $resource['model'], $id));
            }

            return Envelope::items([$this->describeEntity($reader, $entity, $id, $properties, $locale, $render, $index)]);
        });
    }

    /**
     * @param list<string> $properties
     *
     * @return array<string, mixed>
     */
    private function describeEntity(CmsReader $reader, object $entity, string $id, array $properties, string $locale, bool $render, ?int $index): array
    {
        $registered = $reader->blocks()->getBlocks();
        $renderer = $render ? $this->renderer($reader, $entity, $locale) : null;

        $fields = [];

        try {
            foreach ($properties as $property) {
                $content = $reader->readContent($entity, $property, $locale);
                if (null === $content) {
                    $fields[$property] = ['error' => sprintf('"%s" is not readable on the entity nor on its "%s" translation.', $property, $locale)];

                    continue;
                }

                $blocks = [];
                foreach ($content as $key => $data) {
                    if (null !== $index && $key !== $index) {
                        continue;
                    }
                    $blocks[] = $this->describeBlock($key, $data, $registered, $index === $key, $renderer);
                }

                $fields[$property] = [
                    'count' => \count($content),
                    // An object keyed by non sequential indexes instead of a list is how a sort without reindexing shows.
                    'is_list' => array_is_list($content),
                    'blocks' => $blocks,
                ];
            }
        } finally {
            $renderer?->close();
        }

        return array_filter([
            'class' => $entity::class,
            'id' => $id,
            'locale' => $locale,
            'online' => $entity instanceof CmsRoutableInterface ? $entity->isOnline() : null,
            'fields' => $fields,
            'page_builder' => $entity instanceof ContentEditableInterface && $reader->hasPageBuilder($entity) ? $this->describePageBuilder($reader, $entity, $locale, $registered, $render) : null,
        ], static fn (mixed $value): bool => null !== $value);
    }

    /**
     * The blocks of the relational page builder (one row per block), in the given locale.
     *
     * @param array<string, BlockTypeInterface> $registered
     *
     * @return list<array<string, mixed>>
     */
    private function describePageBuilder(CmsReader $reader, ContentEditableInterface $entity, string $locale, array $registered, bool $render): array
    {
        $helper = $render ? $reader->service(ContentBlockHelper::class) : null;

        $blocks = [];
        foreach ($entity->getContentBlocks() as $contentBlock) {
            if (null !== $contentBlock->getLocale() && $contentBlock->getLocale() !== $locale) {
                continue;
            }

            $description = array_filter([
                'id' => $contentBlock->getId(),
                'position' => $contentBlock->getPosition(),
                'block_type' => $contentBlock->getType(),
                'registered' => isset($registered[(string) $contentBlock->getType()]),
                'layer' => $contentBlock->getLayer(),
                'publish_state' => $contentBlock->getPublishState(),
                'published' => $contentBlock->isPublished(),
                'unpublished_changes' => $contentBlock->hasUnpublishedChanges() ?: null,
                'deleted' => $contentBlock->isDeleted() ?: null,
            ], static fn (mixed $value): bool => null !== $value);

            if ($helper instanceof ContentBlockHelper) {
                $description['render'] = $this->renderContentBlock($helper, $contentBlock);
            }

            $blocks[] = $description;
        }

        usort($blocks, static fn (array $a, array $b): int => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        return $blocks;
    }

    /**
     * @return array<string, mixed>
     */
    private function renderContentBlock(ContentBlockHelper $helper, ContentBlockInterface $contentBlock): array
    {
        try {
            $html = $helper->renderContentBlock($contentBlock);
        } catch (\Throwable $exception) {
            return ['status' => 'error', 'exception' => $exception::class, 'message' => $exception->getMessage()];
        }

        if (null === $html) {
            return ['status' => 'not_rendered', 'note' => 'Unpublished, deleted or of an unknown type.'];
        }

        $length = \strlen(trim((string) $html));

        return ['status' => 0 === $length ? 'empty' : 'rendered', 'html_length' => $length];
    }

    /**
     * @param array<string, BlockTypeInterface> $registered
     *
     * @return array<string, mixed>
     */
    private function describeBlock(int|string $key, mixed $data, array $registered, bool $withSettings, ?BlockRenderer $renderer): array
    {
        if (!\is_array($data)) {
            return ['index' => $key, 'error' => sprintf('The block is a %s, not an array.', get_debug_type($data))];
        }
        $data = array_filter($data, '\is_string', \ARRAY_FILTER_USE_KEY);

        $type = \is_string($data['block_type'] ?? null) ? $data['block_type'] : null;
        $block = null !== $type ? ($registered[$type] ?? null) : null;

        $description = array_filter([
            'index' => $key,
            'position' => $data['position'] ?? null,
            'block_type' => $type,
            'name' => $block?->getName(),
            'registered' => null !== $block,
            'published' => \array_key_exists('block_published', $data) ? (bool) $data['block_published'] : null,
            'settings' => $withSettings ? array_diff_key($data, ['block_type' => true, 'position' => true, 'block_published' => true]) : null,
        ], static fn (mixed $value): bool => null !== $value);

        if (null !== $renderer) {
            $description['render'] = $renderer->render($data, $block);
        }

        return $description;
    }

    /**
     * Counts the block types stored by every entity of the model, flagging the types missing from the registry: data
     * written before a block was renamed or removed, rendered as nothing by the front.
     *
     * @param class-string $model
     * @param list<string> $properties
     *
     * @return array<string, mixed>
     */
    private function scan(CmsReader $reader, string $model, array $properties, int $limit): array
    {
        $manager = $reader->manager($model);
        \assert($manager instanceof EntityManagerInterface);
        $registered = $reader->blocks()->getBlocks();

        $query = $manager->createQueryBuilder()->select('entity')->from($model, 'entity')->setMaxResults($limit + 1)->getQuery();

        $scanned = 0;
        $types = [];
        $unknown = [];
        foreach ($query->toIterable() as $entity) {
            if (++$scanned > $limit) {
                break;
            }
            \assert(\is_object($entity));
            $id = method_exists($entity, 'getId') ? $entity->getId() : null;

            if ($entity instanceof ContentEditableInterface && $reader->hasPageBuilder($entity)) {
                foreach ($entity->getContentBlocks() as $contentBlock) {
                    $type = $contentBlock->getType() ?? '(none)';
                    $types[$type] = ($types[$type] ?? 0) + 1;
                    if (!isset($registered[$type]) && \count($unknown) < self::MAX_UNKNOWN_OCCURRENCES) {
                        $unknown[] = ['id' => $id, 'locale' => $contentBlock->getLocale(), 'field' => 'page_builder', 'index' => $contentBlock->getId(), 'block_type' => $type];
                    }
                }
            }

            foreach ($this->locales($reader, $entity) as $locale) {
                foreach ($properties as $property) {
                    foreach ($reader->readContent($entity, $property, $locale) ?? [] as $key => $data) {
                        $type = \is_array($data) && \is_string($data['block_type'] ?? null) ? $data['block_type'] : '(none)';
                        $types[$type] = ($types[$type] ?? 0) + 1;
                        if (!isset($registered[$type]) && \count($unknown) < self::MAX_UNKNOWN_OCCURRENCES) {
                            $unknown[] = ['id' => $id, 'locale' => $locale, 'field' => $property, 'index' => $key, 'block_type' => $type];
                        }
                    }
                }
            }
            $manager->detach($entity);
        }

        arsort($types);
        $items = [];
        foreach ($types as $type => $count) {
            $items[] = ['block_type' => $type, 'count' => $count, 'registered' => isset($registered[$type])];
        }

        return Envelope::items([[
            'model' => $model,
            'scanned' => min($scanned, $limit),
            'truncated' => $scanned > $limit,
            'types' => $items,
            'registered_unused' => array_values(array_diff(array_keys($registered), array_keys($types))),
            'unknown_occurrences' => $unknown,
        ]]);
    }

    /**
     * @return list<string>
     */
    private function locales(CmsReader $reader, object $entity): array
    {
        if (!$entity instanceof TranslatableInterface) {
            return [$reader->defaultLocale()];
        }

        $locales = array_values(array_filter(array_map('strval', $entity->getTranslations()->getKeys())));

        return [] === $locales ? [$reader->defaultLocale()] : $locales;
    }

    private function renderer(CmsReader $reader, object $entity, string $locale): BlockRenderer
    {
        $helper = $reader->service(Helper::class);
        $twig = $reader->service('twig');
        $requestStack = $reader->service('request_stack');
        \assert($helper instanceof Helper && $twig instanceof Environment);

        // The block templates read the request (locale, URLs): a request on the URL of the content stands for it.
        $route = $entity instanceof CmsRoutableInterface ? $entity->getOnlineRoute($locale) : null;
        $request = Request::create($route instanceof Route ? $route->getPath() : '/');
        $request->setLocale($locale);
        $request->attributes->set('_locale', $locale);

        return new BlockRenderer($helper, $twig, $requestStack instanceof RequestStack ? $requestStack : null, $request);
    }
}
