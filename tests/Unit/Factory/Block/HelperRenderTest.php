<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block;

use Adeliom\SyliusEasyCrudPlugin\Services\AssetRenderer;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use Adeliom\SyliusHappyCMSPlugin\Twig\Block\BlockExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\ContextBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\OverriddenHelper;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\TitleBlock;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * Renders flex blocks stored as JSON (`{block_type, position, block_published, ...fields}`) through the
 * `happy_cms_block_render()` Twig function, as a page template does.
 */
final class HelperRenderTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['blockLoopIndex'] = 0;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['blockLoopIndex']);
    }

    public function testTheBlockTemplateDoesNotSeeThePageContextByDefault(): void
    {
        $html = $this->renderPage(
            '{% set page = "Home" %}{{ happy_cms_block_render(block) }}',
            ['block' => $this->block(ContextBlock::class)],
        );

        self::assertSame('[no page]', $html);
    }

    public function testTheBlockTemplateSeesThePageContextWhenInheritContextIsEnabled(): void
    {
        $html = $this->renderPage(
            '{% set page = "Home" %}{{ happy_cms_block_render(block) }}',
            ['block' => $this->block(ContextBlock::class)],
            inheritContext: true,
        );

        self::assertSame('[page: Home]', $html);
    }

    public function testBlockVariablesAndExtraWinOverThePageContext(): void
    {
        $html = $this->renderPage(
            '{% set settings = {title: "page"} %}{% set page = "Home" %}{{ happy_cms_block_render(block, false, {page: "Extra"}) }}',
            ['block' => $this->block(TitleBlock::class, ['title' => 'Block title'])],
            inheritContext: true,
            blockTemplate: '{{ settings.title }} / {{ page }}',
        );

        self::assertSame('Block title / Extra', $html);
    }

    public function testTheContextIsAlsoPassedInsideLoopsAndIncludes(): void
    {
        $html = $this->renderPage(
            '{% set page = "Home" %}{% for item in blocks %}{{ happy_cms_block_render(item) }}{% endfor %}',
            ['blocks' => [$this->block(ContextBlock::class), $this->block(ContextBlock::class)]],
            inheritContext: true,
        );

        self::assertSame('[page: Home][page: Home]', $html);
    }

    public function testTheContextIsNotPassedByThePhpApi(): void
    {
        $helper = $this->helper($this->twig([]), inheritContext: true);

        self::assertSame('[no page]', (string) $helper->renderBlock($this->block(ContextBlock::class)));
        self::assertSame('[page: Home]', (string) $helper->renderBlockInContext(['page' => 'Home'], $this->block(ContextBlock::class)));
    }

    public function testAnOverrideOfRenderBlockIsStillUsedByTheTwigFunctionByDefault(): void
    {
        $twig = $this->twig(['page.html.twig' => '{{ happy_cms_block_render(block) }}']);
        $helper = new OverriddenHelper(...$this->helperArguments($twig));
        $twig->addExtension(new BlockExtension());
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([Helper::class => static fn () => $helper]));

        self::assertSame(
            '[overridden <h2 id="block-1">Hello</h2>]',
            $twig->render('page.html.twig', ['block' => $this->block(TitleBlock::class, ['title' => 'Hello'])]),
        );
    }

    public function testABlockWithoutPublishedKeyIsHidden(): void
    {
        $block = $this->block(TitleBlock::class, ['title' => 'Hello']);
        unset($block['block_published']);

        self::assertSame('', $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block]));
        self::assertSame('', $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block], inheritContext: true));
    }

    public function testAnUnpublishedBlockIsRenderedInPreview(): void
    {
        $block = $this->block(TitleBlock::class, ['title' => 'Hello', 'block_published' => '0']);

        self::assertSame('<h2 id="block-1">Hello</h2>', $this->renderPage('{{ happy_cms_block_render(block, true) }}', ['block' => $block]));
    }

    public function testBlocksGetSequentialAnchorIdsUnlessTheyHaveOne(): void
    {
        $html = $this->renderPage(
            '{% for item in blocks %}{{ happy_cms_block_render(item) }}{% endfor %}',
            ['blocks' => [
                $this->block(TitleBlock::class, ['title' => 'A']),
                $this->block(TitleBlock::class, ['title' => 'B', 'attr_id' => 'custom']),
                $this->block(TitleBlock::class, ['title' => 'C']),
            ]],
        );

        self::assertSame('<h2 id="block-1">A</h2><h2 id="custom">B</h2><h2 id="block-2">C</h2>', $html);
    }

    public function testAnUnknownBlockTypeRendersNothing(): void
    {
        $block = $this->block(TitleBlock::class, ['title' => 'Hello']);
        $block['block_type'] = 'App\Block\RemovedBlock';

        self::assertSame('', $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block]));
    }

    /**
     * @param class-string $type
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    private function block(string $type, array $fields = []): array
    {
        return array_merge(['block_type' => $type, 'position' => 0, 'block_published' => '1'], $fields);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderPage(
        string $pageTemplate,
        array $variables,
        bool $inheritContext = false,
        string $blockTemplate = '<h2 id="{{ settings.attr_id }}">{{ settings.title }}</h2>',
    ): string {
        $twig = $this->twig([
            'page.html.twig' => $pageTemplate,
            'blocks/title.html.twig' => $blockTemplate,
        ]);

        $helper = $this->helper($twig, $inheritContext);
        $twig->addExtension(new BlockExtension());
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([Helper::class => static fn () => $helper]));

        return $twig->render('page.html.twig', $variables);
    }

    /**
     * @param array<string, string> $templates
     */
    private function twig(array $templates): Environment
    {
        return new Environment(new ArrayLoader(array_merge([
            'blocks/title.html.twig' => '<h2 id="{{ settings.attr_id }}">{{ settings.title }}</h2>',
            'blocks/context.html.twig' => '{{ page is defined ? "[page: " ~ page ~ "]" : "[no page]" }}',
        ], $templates)), ['strict_variables' => true, 'autoescape' => false]);
    }

    private function helper(Environment $twig, bool $inheritContext = false): Helper
    {
        return new Helper(...$this->helperArguments($twig), inheritContext: $inheritContext);
    }

    /**
     * @return array{Environment, EventDispatcher, BlockCollection, AssetRenderer}
     */
    private function helperArguments(Environment $twig): array
    {
        return [
            $twig,
            new EventDispatcher(),
            new BlockCollection([new TitleBlock(), new ContextBlock()]),
            new AssetRenderer(new EventDispatcher(), $this->createStub(TagRenderer::class), new Packages()),
        ];
    }
}
