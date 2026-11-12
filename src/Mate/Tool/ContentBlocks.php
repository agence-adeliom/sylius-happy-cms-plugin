<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Mate\Tool;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock\Helper as ContentBlockHelper;
use Adeliom\SyliusHappyCMSPlugin\Mate\CmsReader;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\MateExtension\Kernel\HostContainerProvider;
use Sylius\MateExtension\Output\Envelope;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

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
        description: 'Read the flex blocks stored in the page builder (one row per block) of an easy-crud resource (alias such as app.page). With "id": the blocks of that entity in the locale (id, position, block_type, registered in the block registry, publish state) and, with render=true, each block rendered on its own like the front does: rendered (HTML length), empty, not_rendered or error (the exception). "index" (a block id) restricts to one block and adds its draft settings. Without "id": scans up to "limit" entities and counts the block types stored, flagging those missing from the registry. Rendering runs the block code (queries, HTTP clients).',
    )]
    public function __invoke(string $alias, ?string $id = null, ?string $locale = null, bool $render = false, ?int $index = null, int $limit = 1000): array
    {
        return Envelope::guard(function () use ($alias, $id, $locale, $render, $index, $limit): array {
            $reader = new CmsReader($this->host->getContainer());
            $resource = $reader->resource($alias);
            if (null === $resource || null === $resource['admin']) {
                return Envelope::empty(sprintf('"%s" is not an easy-crud resource. Call easy_crud_list_admins to find it.', $alias));
            }

            if (!is_a($resource['model'], ContentEditableInterface::class, true)) {
                return Envelope::empty(sprintf('The model of "%s" is not content-editable: it has no page builder blocks.', $alias));
            }

            if (null === $id) {
                return $this->scan($reader, $resource['model'], max(1, $limit));
            }

            $entity = $reader->find($resource['model'], $id);
            if (null === $entity) {
                return Envelope::empty(sprintf('No %s has the id "%s".', $resource['model'], $id));
            }

            return Envelope::items([$this->describeEntity($reader, $entity, $id, $locale ?? $reader->defaultLocale(), $render, $index)]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function describeEntity(CmsReader $reader, object $entity, string $id, string $locale, bool $render, ?int $index): array
    {
        return array_filter([
            'class' => $entity::class,
            'id' => $id,
            'locale' => $locale,
            'online' => $entity instanceof CmsRoutableInterface ? $entity->isOnline() : null,
            'page_builder' => $entity instanceof ContentEditableInterface && $reader->hasPageBuilder($entity) ? $this->describePageBuilder($reader, $entity, $locale, $reader->blocks()->getBlocks(), $render, $index) : null,
        ], static fn (mixed $value): bool => null !== $value);
    }

    /**
     * The blocks of the relational page builder (one row per block), in the given locale.
     *
     * @param array<string, BlockTypeInterface> $registered
     *
     * @return list<array<string, mixed>>
     */
    private function describePageBuilder(CmsReader $reader, ContentEditableInterface $entity, string $locale, array $registered, bool $render, ?int $index): array
    {
        $helper = $render ? $reader->service(ContentBlockHelper::class) : null;
        $helper = $helper instanceof ContentBlockHelper ? $helper : null;
        $requestStack = $render ? $reader->service('request_stack') : null;
        $requestStack = $requestStack instanceof RequestStack ? $requestStack : null;
        // The block templates read the request (locale, URLs): a request on the URL of the content stands for it.
        $requestStack?->push($this->request($entity, $locale));

        try {
            $blocks = [];
            foreach ($entity->getContentBlocks() as $contentBlock) {
                if (null !== $contentBlock->getLocale() && $contentBlock->getLocale() !== $locale) {
                    continue;
                }
                if (null !== $index && $contentBlock->getId() !== $index) {
                    continue;
                }

                $blocks[] = $this->describeContentBlock($contentBlock, $registered, $index === $contentBlock->getId(), $helper);
            }
        } finally {
            $requestStack?->pop();
        }

        usort($blocks, static fn (array $a, array $b): int => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        return $blocks;
    }

    /**
     * @param array<string, BlockTypeInterface> $registered
     *
     * @return array<string, mixed>
     */
    private function describeContentBlock(ContentBlockInterface $contentBlock, array $registered, bool $withSettings, ?ContentBlockHelper $helper): array
    {
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
            'settings' => $withSettings ? $contentBlock->getDraftData() : null,
        ], static fn (mixed $value): bool => null !== $value);

        if (null !== $helper) {
            $description['render'] = $this->renderContentBlock($helper, $contentBlock);
        }

        return $description;
    }

    private function request(object $entity, string $locale): Request
    {
        $route = $entity instanceof CmsRoutableInterface ? $entity->getOnlineRoute($locale) : null;
        $request = Request::create($route instanceof Route ? $route->getPath() : '/');
        $request->setLocale($locale);
        $request->attributes->set('_locale', $locale);

        return $request;
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
     * Counts the block types stored by every entity of the model, flagging the types missing from the registry: data
     * written before a block was renamed or removed, rendered as nothing by the front.
     *
     * @param class-string $model
     *
     * @return array<string, mixed>
     */
    private function scan(CmsReader $reader, string $model, int $limit): array
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
                        $unknown[] = ['id' => $id, 'locale' => $contentBlock->getLocale(), 'block_id' => $contentBlock->getId(), 'block_type' => $type];
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
}
