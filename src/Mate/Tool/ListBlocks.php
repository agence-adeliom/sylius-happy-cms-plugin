<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Mate\Tool;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Mate\CmsReader;
use Sylius\MateExtension\Kernel\HostContainerProvider;
use Sylius\MateExtension\Output\Envelope;
use Symfony\AI\Mate\Attribute\MateTool;

final class ListBlocks
{
    private const KINDS = ['flex', 'shared'];

    public function __construct(
        private readonly HostContainerProvider $host,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[MateTool(
        name: 'happy_cms_list_blocks',
        description: 'List the registered Happy CMS blocks (flex and shared): block_type (the value stored in the content JSON), name, tab, position, front template, assets, AI-generatable. With "alias" (an easy-crud resource such as app.page), only the flex blocks allowed in its flexible content fields, with the fields allowing each one. Filter by "kind" (flex, shared).',
    )]
    public function __invoke(?string $alias = null, ?string $kind = null): array
    {
        if (null !== $kind && !\in_array($kind, self::KINDS, true)) {
            return Envelope::error('invalid_kind', sprintf('Unknown kind "%s", expected one of: %s.', $kind, implode(', ', self::KINDS)));
        }

        return Envelope::guard(fn (): array => $this->list($alias, $kind));
    }

    /**
     * @return array<string, mixed>
     */
    private function list(?string $alias, ?string $kind): array
    {
        $reader = new CmsReader($this->host->getContainer());

        if (null !== $alias) {
            return $this->allowedIn($reader, $alias);
        }

        $items = [];
        if ('shared' !== $kind) {
            foreach ($reader->blocks()->getBlocks() as $type => $block) {
                $items[] = $this->describe('flex', $type, $block);
            }
        }
        if ('flex' !== $kind) {
            foreach ($reader->sharedBlocks()->getBlocks() as $type => $block) {
                $items[] = $this->describe('shared', $type, $block);
            }
        }

        return [] === $items ? Envelope::empty('No block is registered (services tagged "sylius.happy_cms.block" or "sylius.happy_cms.shared_block").') : Envelope::items($items);
    }

    /**
     * The flex blocks the flexible content fields of a resource accept, filtered as FlexibleContentConfigurator does:
     * the block types declared by the field (every block when none), then BlockTypeInterface::supports().
     *
     * @return array<string, mixed>
     */
    private function allowedIn(CmsReader $reader, string $alias): array
    {
        $resource = $reader->resource($alias);
        if (null === $resource || null === $resource['admin']) {
            return Envelope::empty(sprintf('"%s" is not an easy-crud resource. Call easy_crud_list_admins to find it.', $alias));
        }

        $fields = $reader->flexibleContentFields($reader->admin($resource['admin']), $reader->defaultLocale());
        if ([] === $fields) {
            return Envelope::empty(sprintf('The Admin of "%s" has no FlexibleContentField on its update page.', $alias));
        }

        // supports() receives the edited resource: a new instance stands for it, as on the create page. It only takes
        // a content-editable resource (the relational page builder).
        $instance = $this->newInstance($resource['model']);

        $blocks = [];
        $allowingFields = [];
        foreach ($fields as $field) {
            foreach ($reader->blocks()->getAllowedBlocks($field['blocks'], $instance) as $type => $block) {
                $blocks[$type] = $block;
                $allowingFields[$type][] = $field['property'];
            }
        }

        $items = [];
        foreach ($blocks as $type => $block) {
            $items[] = $this->describe('flex', $type, $block) + ['fields' => $allowingFields[$type]];
        }

        return Envelope::items($items, note: null === $instance ? sprintf('"%s" is not content-editable or could not be instantiated: supports() was called without the resource.', $resource['model']) : null);
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(string $kind, string $type, BlockTypeInterface|SharedBlockTypeInterface $block): array
    {
        return array_filter([
            'kind' => $kind,
            'block_type' => $type,
            'name' => $this->guarded(static fn (): string => $block->getName()),
            'tab' => $block instanceof BlockTypeInterface ? $this->guarded(static fn (): string => $block->getTab()) : null,
            'position' => $block->getPosition(),
            'template' => $this->guarded(static fn (): string => $block->getFrontEndTemplatePath()),
            'assets' => $this->guarded(fn (): ?array => $this->assets($block->configureAssets())),
            'ai_generatable' => [] !== (new \ReflectionClass($block))->getAttributes(AIGeneratable::class) ?: null,
        ], static fn (mixed $value): bool => null !== $value);
    }

    /**
     * @param array<mixed> $assets
     *
     * @return array<string, list<string>>|null
     */
    private function assets(array $assets): ?array
    {
        $described = [];
        foreach ($assets as $kind => $list) {
            if (!\is_string($kind) || !\is_iterable($list)) {
                continue;
            }
            foreach ($list as $asset) {
                $described[$kind][] = \is_string($asset) ? $asset : (\is_object($asset) && method_exists($asset, '__toString') ? (string) $asset : get_debug_type($asset));
            }
        }

        return [] === $described ? null : $described;
    }

    /**
     * @param class-string $model
     */
    private function newInstance(string $model): ?ContentEditableInterface
    {
        if (!is_a($model, ContentEditableInterface::class, true)) {
            return null;
        }

        try {
            return new $model();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Runs one part of the description, reporting its failure in place: a block whose template or assets cannot be
     * resolved is often the bug being investigated.
     *
     * @template T
     *
     * @param callable(): T $part
     *
     * @return T|array{error: string}
     */
    private function guarded(callable $part): mixed
    {
        try {
            return $part();
        } catch (\Throwable $exception) {
            return ['error' => sprintf('%s: %s', $exception::class, $exception->getMessage())];
        }
    }
}
