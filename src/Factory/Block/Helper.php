<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\Block;

use _PHPStan_d06f792a9\React\Http\Message\Request;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Services\AssetRenderer;
use Adeliom\SyliusHappyCMSPlugin\Event\Block\BlockRender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
        private FormFactoryInterface $formFactory,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private AssetRenderer $assetRenderer,
    ) {
    }

    public function includeAssets(): string
    {
        return $this->assetRenderer->renderAssets($this->assets);
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
    private function startTracing(BlockTypeInterface|Bl $block): array
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
    public function renderBlock(array $data, ?bool $preview = false, ?array $extra = []): ?Markup
    {
        if ((int) ($data['block_published'] ?? null) === 0 && !$preview) {
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

        $this->assets = array_merge_recursive($this->assets, $stats['assets']);

        $this->stopTracing($stats['id'], $stats);

        return new Markup($this->twig->render($block->getFrontEndTemplatePath(), array_merge([
                                                                                                 'block' => $data,
                                                                                                 'preview' => $preview ?: false,
                                                                                                 'blockType' => $blockType,
                                                                                                 'settings' => $blockData,
                                                                                             ], $extra)), 'UTF-8');
    }
}
