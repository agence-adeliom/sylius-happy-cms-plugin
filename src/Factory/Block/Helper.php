<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\Block;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Services\AssetRenderer;
use Adeliom\SyliusHappyCMSPlugin\Event\Block\BlockRender;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;

class Helper
{
    /**
     * This property is a state variable holdings all assets used by the block for the current PHP request
     * It is used to correctly render the javascripts and stylesheets tags on the main layout.
     *
     * @var array{js: array<string|Asset>|null, css: array<string|Asset>|null, webpack: array<string|Asset>|null}
     */
    private array $assets = [
        'js' => [],
        'css' => [],
        'webpack' => [],
    ];

    /** @var array<int|string, array<string, mixed>> */
    private array $traces = [];

    public function __construct(
        private Environment $twig,
        private EventDispatcherInterface $eventDispatcher,
        private readonly BlockCollection $collection,
        private readonly AssetRenderer $assetRenderer,
        /** Merge the Twig context of the calling template into the block template context */
        private readonly bool $inheritContext = false,
        /** Render a block whose data has no `block_published` key (data written outside the block form) */
        private readonly bool $missingPublishedMeansPublished = false,
    ) {
    }

    public function includeAssets(Environment $env, array $context, ?string $nonce = null): string
    {
        return $this->assetRenderer->renderAssets($this->assets, $nonce);
    }

    /**
     * Returns the rendering traces.
     *
     * @return array<int|string, array<string, mixed>>
     */
    public function getTraces(): array
    {
        return $this->traces;
    }

    /**
     * @return array<string, mixed>
     */
    private function startTracing(BlockTypeInterface $block): array
    {
        return [
            'id' => uniqid(),
            'name' => $block->getName(),
            'type' => $block::class,
            'position' => null,
            'datas' => [],
            'assets' => [
                'js' => [],
                'css' => [],
                'webpack' => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function stopTracing(string $id, array $stats): void
    {
        $this->traces[$id] = $stats;
    }

    /**
     * Renders a flex block stored as an array (`block_type`, `position`, `block_published` and the block fields).
     *
     * Returns null when the block is not published (unless in preview) or when its type is unknown. A block without
     * `block_published` key is not published, unless `missing_published_means_published` is enabled.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $extra variables added to the block template context
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderBlock(array $data, bool $preview = false, array $extra = []): ?Markup
    {
        return $this->doRenderBlock([], $data, $preview, $extra);
    }

    /**
     * Same as renderBlock(), called by the `happy_cms_block_render()` Twig function with the context of the calling
     * template. When `inherit_context` is enabled, the block template receives that context (`page`, `resource`,
     * loop variables…), overridden by the block variables (`block`, `preview`, `blockType`, `settings`), themselves
     * overridden by `$extra`. When it is disabled, renderBlock() is called, so an override of renderBlock() keeps
     * being used by the Twig function.
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     * @param array<string, mixed> $extra
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderBlockInContext(array $context, array $data, bool $preview = false, array $extra = []): ?Markup
    {
        if (!$this->inheritContext) {
            return $this->renderBlock($data, $preview, $extra);
        }

        return $this->doRenderBlock($context, $data, $preview, $extra);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function isPublished(array $data): bool
    {
        if (!array_key_exists('block_published', $data)) {
            return $this->missingPublishedMeansPublished;
        }

        return (bool) $data['block_published'];
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     * @param array<string, mixed> $extra
     */
    private function doRenderBlock(array $context, array $data, bool $preview, array $extra): ?Markup
    {
        if (!$this->isPublished($data) && $preview === false) {
            return null;
        }

        $blocks = $this->collection->getBlocks();
        /** @var string $blockType */
        $blockType = $data['block_type'] ?? '';
        if (isset($blocks[$blockType])) {
            $block = $blocks[$blockType];
        } else {
            return null;
        }

        $stats = $this->startTracing($block);
        $defaultAssets = $block->configureAssets();

        $event = $this->eventDispatcher->dispatch(new BlockRender($block, $data, $defaultAssets), 'happy_cms_block.render_block');

        $block = $event->getBlock();
        $blockData = $event->getData();

        if (isset($blockData['block_type'])) {
            unset($blockData['block_type']);
        }

        if (isset($blockData['position'])) {
            $stats['position'] = $blockData['position'];
            unset($blockData['position']);
        }

        // Add a way to automatically set an ID (base on loop index when the page is rendered)
        if (empty($blockData['attr_id'])) {
            global $blockLoopIndex;
            if (!is_int($blockLoopIndex)) {
                $blockLoopIndex = 0;
            }

            ++$blockLoopIndex;
            $blockData['attr_id'] = 'block-' . $blockLoopIndex;
        }

        $stats['settings'] = $blockData;
        $stats['assets'] = $event->getAssets();

        /** @var array{js: array<string|Asset>|null, css: array<string|Asset>|null, webpack: array<string|Asset>|null} $mergedAssets */
        $mergedAssets = array_merge_recursive($this->assets, $stats['assets']);

        $this->assets = $mergedAssets;

        if (is_string($stats['id'])) {
            $this->stopTracing($stats['id'], $stats);
        }

        return new Markup($this->twig->render($block->getFrontEndTemplatePath(), array_merge(
            $context,
            [
                'block' => $data,
                'preview' => $preview,
                'blockType' => $blockType,
                'settings' => $blockData,
            ],
            $extra,
        )), 'UTF-8');
    }
}
