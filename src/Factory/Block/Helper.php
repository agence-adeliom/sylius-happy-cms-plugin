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
        //private readonly FormFactoryInterface $formFactory,
        //private readonly EntityManagerInterface $entityManager,
        //private readonly RequestStack $requestStack
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
     * @param array<string, mixed> $data
     * @param array<string, mixed> $extra
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderBlock(array $data, bool $preview = false, array $extra = []): ?Markup
    {
        $blockPublished = $data['block_published'] ?? null;

        if (!$blockPublished && $preview === false) {
            return null;
        }

        $blocks = $this->collection->getBlocks();
        if (isset($blocks[$data['block_type']])) {
            $block = $blocks[$data['block_type']];
        } else {
            return null;
        }

        $stats = $this->startTracing($block);
        $blockType = $data['block_type'];
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
            if (empty($blockLoopIndex)) {
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

        return new Markup($this->twig->render($block->getFrontEndTemplatePath(), array_merge([
                                                                                                 'block' => $data,
                                                                                                 'preview' => $preview,
                                                                                                 'blockType' => $blockType,
                                                                                                 'settings' => $blockData,
                                                                                             ], $extra)), 'UTF-8');
    }
}
