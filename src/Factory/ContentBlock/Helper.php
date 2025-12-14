<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Services\AssetRenderer;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Block\BlockRender;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
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
    private function startTracing(string $blockType, int $blockId): array
    {
        return [
            'id' => uniqid(),
            'block_id' => $blockId,
            'name' => $blockType,
            'type' => $blockType,
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
     * Render a ContentBlock entity.
     *
     * @param array<string, mixed> $extra
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderContentBlock(ContentBlockInterface $contentBlock, bool $preview = false, array $extra = []): ?Markup
    {
        // Check if block is published (unless in preview mode where we show all blocks)
        if (!$contentBlock->isPublished() && !$preview) {
            return null;
        }

        $isPreviewPublished = $contentBlock->isPreviewPublished();

        // Get the block type
        $blockType = $contentBlock->getType();
        if (null === $blockType) {
            return null;
        }

        $blocks = $this->collection->getBlocks();
        if (!isset($blocks[$blockType])) {
            return null;
        }

        $block = $blocks[$blockType];

        // Get block ID for unique identification
        $blockId = $contentBlock->getId();
        if (null === $blockId) {
            return null;
        }

        // Use draft data in preview mode, published data otherwise
        $blockData = $preview ? $contentBlock->getDraftData() : $contentBlock->getPublishedData();
        if (null === $blockData) {
            $blockData = [];
        }

        $stats = $this->startTracing($blockType, $blockId);
        $defaultAssets = $block->configureAssets();

        // Prepare event data with block type and published status
        $eventData = array_merge($blockData, [
            'block_type' => $blockType,
            'block_published' => $contentBlock->isPublished(),
        ]);

        $event = $this->eventDispatcher->dispatch(
            new BlockRender($block, $eventData, $defaultAssets),
            'happy_cms_block.render_block',
        );

        $block = $event->getBlock();
        $blockData = $event->getData();

        // Clean up internal data
        if (isset($blockData['block_type'])) {
            unset($blockData['block_type']);
        }

        if (isset($blockData['position'])) {
            $stats['position'] = $blockData['position'];
            unset($blockData['position']);
        }

        // Use the ContentBlock database ID as the unique identifier
        $blockData['attr_id'] = 'content-block-' . $blockId;
        $blockData['data_block_id'] = $blockId;

        $stats['settings'] = $blockData;
        $stats['assets'] = $event->getAssets();

        /** @var array{js: array<string|Asset>|null, css: array<string|Asset>|null, webpack: array<string|Asset>|null} $mergedAssets */
        $mergedAssets = array_merge_recursive($this->assets, $stats['assets']);

        $this->assets = $mergedAssets;

        if (is_string($stats['id'])) {
            $this->stopTracing($stats['id'], $stats);
        }

        $renderedContent = $this->twig->render($block->getFrontEndTemplatePath(), array_merge([
            'contentBlock' => $contentBlock,
            'block' => $eventData,
            'preview' => $preview,
            'blockType' => $blockType,
            'settings' => $blockData,
        ], $extra));

        // In preview mode, wrap the content with a container that has data-block-id, data-block-layer, and data-published attributes
        if ($preview) {
            $layer = $contentBlock->getLayer();
            $layerAttr = $layer ? sprintf(' data-block-layer="%s"', htmlspecialchars($layer, \ENT_QUOTES, 'UTF-8')) : '';
            $publishedAttr = sprintf(' data-published="%s"', $isPreviewPublished ? 'true' : 'false');

            // Add overlay for unpublished blocks with inline styles
            $overlayHtml = '';
            if (!$isPreviewPublished) {
                $overlayHtml = '<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(200, 200, 200, 0.5); pointer-events: none; z-index: 10; display: flex; align-items: center; justify-content: center;">'
                    . '<div style="background: rgba(255, 255, 255, 0.95); color: #6c757d; padding: 12px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">'
                    . '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="vertical-align: text-bottom; margin-right: 6px;">'
                    . '<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>'
                    . '<path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>'
                    . '</svg>'
                    . 'Unpublished Block'
                    . '</div>'
                    . '</div>';
            }

            $wrappedContent = sprintf(
                '<div data-block-id="%d"%s%s class="content-block-wrapper" style="position: relative;">%s%s</div>',
                $blockId,
                $layerAttr,
                $publishedAttr,
                $renderedContent,
                $overlayHtml,
            );

            return new Markup($wrappedContent, 'UTF-8');
        }

        return new Markup($renderedContent, 'UTF-8');
    }
}
