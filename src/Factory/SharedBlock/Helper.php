<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\FormFactory;
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
        private readonly Environment $twig,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SharedBlockCollection $collection,
        private readonly EntityManagerInterface $em,
        private readonly string $class,
        private readonly FormFactory $formFactory,
    ) {
    }

    public function includeAssets(): string
    {
        $html = '';

        if (!empty($this->assets['css'])) {
            $html .= "<style media='all'>";

            foreach ($this->assets['css'] as $stylesheet) {
                $html .= "\n" . sprintf('@import url(%s);', $stylesheet);
            }

            $html .= "\n</style>";
        }

        foreach ($this->assets['js'] as $javascript) {
            $html .= "\n" . sprintf('<script src="%s" type="text/javascript"></script>', $javascript);
        }

        foreach ($this->assets['webpack'] as $webpack) {
            try {
                $html .= "\n" . $this->twig->createTemplate(sprintf("{{ encore_entry_link_tags('%s') }}", $webpack))->render();
                $html .= "\n" . $this->twig->createTemplate(sprintf("{{ encore_entry_script_tags('%s') }}", $webpack))->render();
            } catch (LoaderError|SyntaxError) {
                $html .= '';
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
    public function renderBlock(Environment $env, array $context, mixed $data, ?array $extra = []): ?Markup
    {
        $block = null;
        if (is_array($data)) {
            if (class_exists($data['class'])) {
                $block = $this->em->getRepository($data['class'])->find($data['id']);
            }
        }

        if (class_exists($this->class)) {
            if (is_numeric($data)) {
                $block = $this->em->getRepository($this->class)->findOneBy(['id' => $data]);
            } elseif (is_string($data)) {
                $block = $this->em->getRepository($this->class)->findOneBy(['key' => $data]);
            }
        }

        if (null === $block) {
            return null;
        }

        if (!$block instanceof SharedBlockInterface) {
            return null;
        }

        if (!$block->getStatus()) {
            return null;
        }

        $blockType = $this->collection->getBlocks()[$block->getType()];

        $stats = $this->startTracing($block);
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

        // Tranform settings way 2 : with dispatch / event listeners
        $event = new GenericEvent(null, [
            'data' => $data,
            'block' => $block,
            'blockType' => $blockType,
            'settings' => $blockSettings,
            'assets' => $defaultAssets,
        ]);

        /**
         * @var GenericEvent $result ;
         */
        $result = $this->eventDispatcher->dispatch($event, 'happy_cms_block.render_block');

        $block = $result->getArgument('block');
        $blockType = $result->getArgument('blockType');
        $blockData = $result->getArgument('settings');

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
        $stats['assets'] = $result->getArgument('assets') ?: [];

        $this->assets = array_merge_recursive($this->assets, $stats['assets']);

        $this->stopTracing($stats['id'], $stats);

        // Render
        return new Markup($this->twig->render($blockType->getTemplate(), array_merge($context, [
            'block' => $block,
            'blockType' => $blockType,
            'settings' => $blockData,
        ], $extra)), 'UTF-8');
    }

    /**
     * @param array<string, mixed> $defaultSetting
     *
     * @return array<string, mixed>
     *
     * @throws LoaderError
     * @throws SyntaxError
     * @throws RuntimeError
     */
    public function transformSettingsWithBlockTypeFormBuild(
        SharedBlockTypeInterface $blockType,
        SharedBlockInterface $block,
        array $defaultSetting,
    ): array {
        $formBuilder = $this->formFactory->createBuilder($block->getType(), null, ['csrf_protection' => false]);

        // init blockType form builder
        $blockType->buildBlock($formBuilder, []);

        // Submit to use optionnal form transformers
        $form = $formBuilder->getForm();
        $form->setData(array_merge($defaultSetting, $block->getSettings()));

        // Put norm data into block settings
        // norm data are transformed data
        $blockSettings = $form->getNormData();
        if (!empty($form->getNormData())) {
            foreach ($form->getNormData() as $field => $value) {
                /** @phpstan-ignore-next-line */
                if (!empty($form->get($field))) {
                    $blockSettings[$field] = $form->get($field)->getNormData();
                }
            }
        }

        return $blockSettings;
    }
}
