<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\Block;

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
     * @var array<string, string[]>
     */
    private array $assets = [
        'js' => [],
        'css' => [],
        'webpack' => [],
    ];

    /** @var array<int, array<string, mixed>> */
    private array $traces = [];

    public function __construct(
        /**
         * @readonly
         */
        private Environment $twig,
        /**
         * @readonly
         */
        private EventDispatcherInterface $eventDispatcher,
        /**
         * @readonly
         */
        private BlockCollection $collection,
    ) {
    }

    /**
     * @return mixed[]|string
     */
    public function includeAssets(): array|string
    {
        $html = '';

        if (!empty($this->assets['css'])) {
            $html .= "<style media='all'>";
            foreach ($this->assets['css'] as $stylesheet) {
                $html .= "\n" . sprintf('@import url(%s);', $stylesheet);
            }

            $html .= "\n</style>";
        }

        if (!empty($this->assets['js'])) {
            foreach ($this->assets['js'] as $javascript) {
                $html .= "\n" . sprintf('<script src="%s" type="text/javascript"></script>', $javascript);
            }
        }

        if (!empty($this->assets['webpack'])) {
            foreach ($this->assets['webpack'] as $webpack) {
                try {
                    $html .= "\n" . $this->twig->createTemplate(sprintf("{{ encore_entry_link_tags('%s') }}", $webpack))->render();
                    $html .= "\n" . $this->twig->createTemplate(sprintf("{{ encore_entry_script_tags('%s') }}", $webpack))->render();
                } catch (LoaderError|SyntaxError) {
                    $html .= '';
                }
            }
        }

        return $html;
    }

    /**
     * Returns the rendering traces.
     *
     * @return array<int, array<string, mixed>>
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
     * @param array<string, array<string, mixed>> $stats
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
        if ((int) ($data['block_published'] ?? null) === 0 && $preview === false) {
            return null;
        }

        $block = $this->collection->getBlocks()[$data['block_type']];
        $stats = $this->startTracing($block);
        $blockType = $data['block_type'];
        $defaultAssets = $block->configureAssets();

        $event = $this->eventDispatcher->dispatch(new BlockRender($block, $data, $defaultAssets));

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

        $this->assets = array_merge_recursive($this->assets, $stats['assets']);

        $this->stopTracing($stats['id'], $stats);

        return new Markup($this->twig->render($block->getFrontEndTemplatePath(), array_merge([
            'block' => $data,
            'blockType' => $blockType,
            'settings' => $blockData,
        ], $extra)), 'UTF-8');
    }
}
