<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Services\AssetRenderer;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockTranslationInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Block\BlockRender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactory;
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
        private readonly Environment $twig,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SharedBlockCollection $collection,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $class,
        private readonly FormFactory $formFactory,
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
    private function startTracing(SharedBlockInterface $block): array
    {
        return [
            'id' => uniqid(),
            'name' => $block->getName(),
            'type' => $block->getType(),
            'key' => $block->getKey(),
            'defaultSettings' => [],
            'settings' => [],
            'extra' => [],
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
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     *
     * @throws LoaderError
     * @throws SyntaxError
     * @throws RuntimeError
     */
    public function renderBlock(Environment $env, array $context, array $data, bool $preview = false, array $extra =
    []): ?Markup
    {
        $block = null;
        if (!isset($data['block'])) {
            return null;
        }

        $sharedBlock = $this->entityManager->getRepository(SharedBlockInterface::class)->find($data['block']);
        if ($sharedBlock instanceof SharedBlockInterface) {
            /** @var ?SharedBlockTranslationInterface $translation */
            $translation = $sharedBlock->getTranslation($this->requestStack->getCurrentRequest()->getLocale());
            /** @var ?SharedBlockTranslationInterface $translation */
            $firstTranslation = $sharedBlock->getTranslations()->first();
            if (null === $translation && null !== $firstTranslation) {
                $translation = $firstTranslation;
            }
            if ($firstTranslation instanceof SharedBlockTranslationInterface && $translation instanceof SharedBlockTranslationInterface) {
                $block = array_merge(
                    $firstTranslation->getContent() ?? [],
                    $translation->getContent() ?? [],
                );
            }
        }

        if (null === $block) {
            return null;
        }

        $blockType = $this->collection->getBlocks()[$sharedBlock->getType()];

        $stats = $this->startTracing($sharedBlock);
        $defaultSetting = call_user_func([$blockType, 'getDefaultSettings']);
        $defaultAssets = call_user_func([$blockType, 'configureAssets']);

        // Tranform settings way 1 : use blockType form transformers
        $blockSettings = $this->transformSettingsWithBlockTypeFormBuild($blockType, $block, $defaultSetting);

        // Add a way to automatically set an ID (base on loop index when the page is rendered)
        if (empty($blockSettings['attr_id'])) {
            global $blockLoopIndex;
            if (empty($blockLoopIndex)) {
                $blockLoopIndex = 0;
            }

            ++$blockLoopIndex;
            $blockSettings['attr_id'] = 'block-' . $blockLoopIndex;
        }

        $event = $this->eventDispatcher->dispatch(new BlockRender($blockType, $blockSettings, $defaultAssets), 'happy_cms_block.render_block');

        $block = $event->getBlock();
        $blockData = $event->getData();

        // Stats
        if (isset($blockData['block_type'])) {
            unset($blockData['block_type']);
        }

        if (isset($blockData['position'])) {
            $stats['position'] = $blockData['position'];
            unset($blockData['position']);
        }

        $stats['defaultSettings'] = $defaultSetting;
        $stats['settings'] = $blockData;
        $stats['extra'] = $extra;
        $stats['type'] = $blockType::class;
        $stats['assets'] = $event->getAssets() ?: [];

        $this->assets = array_merge_recursive($this->assets, $stats['assets']);

        $this->stopTracing($stats['id'], $stats);

        // Render
        return new Markup($this->twig->render($blockType->getFrontEndTemplatePath(), array_merge([
                                                                                                     'block' => $block,
                                                                                                     'blockType' => $blockType,
                                                                                                     'preview' => $preview,
                                                                                                     'settings' => $blockData,
                                                                                                 ], $extra)), 'UTF-8');
    }

    /**
     * @param array<string, mixed> $defaultSetting
     * @param array<string, mixed> $block
     *
     * @return array<string, mixed>
     *
     * @throws LoaderError
     * @throws SyntaxError
     * @throws RuntimeError
     */
    public function transformSettingsWithBlockTypeFormBuild(
        SharedBlockTypeInterface $blockType,
        array $block,
        array $defaultSetting,
    ): array {
        // TODO : essayer de passer par le form buider pour utiliser les transformers
        //$formBuilder = $this->formFactory->createBuilder($block->getType(), null, ['csrf_protection' => false]);
        //
        //// init blockType form builder
        //$blockType->buildBlock($formBuilder, []);
        //
        //// Submit to use optionnal form transformers
        //$form = $formBuilder->getForm();
        //$form->setData(array_merge($defaultSetting, $block->getSettings()));
        //
        //// Put norm data into block settings
        //// norm data are transformed data
        //$blockSettings = $form->getNormData();
        //if (!empty($form->getNormData())) {
        //    foreach ($form->getNormData() as $field => $value) {
        //        /** @phpstan-ignore-next-line */
        //        if (!empty($form->get($field))) {
        //            $blockSettings[$field] = $form->get($field)->getNormData();
        //        }
        //    }
        //}
        //return $blockSettings;

        return array_merge($defaultSetting, $block);
    }
}
