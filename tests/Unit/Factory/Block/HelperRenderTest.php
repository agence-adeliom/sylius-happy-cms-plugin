<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block;

use Adeliom\SyliusEasyCrudPlugin\Services\AssetRenderer;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use Adeliom\SyliusHappyCMSPlugin\Twig\Block\BlockExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Symfony\Component\Asset\Packages;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\ContextBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\FailingBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\OverriddenHelper;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\TitleBlock;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * Renders flex blocks stored as JSON (`{block_type, position, block_published, ...fields}`) through the
 * `happy_cms_block_render()` Twig function, as a page template does.
 */
final class HelperRenderTest extends TestCase
{
    private BufferingLogger $logger;

    protected function setUp(): void
    {
        $GLOBALS['blockLoopIndex'] = 0;
        $this->logger = new BufferingLogger();
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

    public function testABlockWithoutPublishedKeyIsHiddenByDefault(): void
    {
        $block = $this->block(TitleBlock::class, ['title' => 'Hello']);
        unset($block['block_published']);

        self::assertSame('', $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block]));
        self::assertSame('', $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block], inheritContext: true));
    }

    public function testABlockWithoutPublishedKeyIsRenderedWhenMissingPublishedMeansPublished(): void
    {
        $block = $this->block(TitleBlock::class, ['title' => 'Hello']);
        unset($block['block_published']);

        self::assertSame(
            '<h2 id="block-1">Hello</h2>',
            $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block], missingPublishedMeansPublished: true),
        );
        self::assertSame(
            '<h2 id="block-2">Hello</h2>',
            $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block], inheritContext: true, missingPublishedMeansPublished: true),
        );
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function unpublishedValues(): iterable
    {
        yield 'string "0"' => ['0'];
        yield 'empty string' => [''];
        yield 'false' => [false];
        yield 'null' => [null];
    }

    /**
     * @dataProvider unpublishedValues
     */
    public function testAnUnpublishedBlockIsHiddenWhateverMissingPublishedMeansPublished(mixed $published): void
    {
        $block = $this->block(TitleBlock::class, ['title' => 'Hello', 'block_published' => $published]);

        self::assertSame('', $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block]));
        self::assertSame('', $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block], missingPublishedMeansPublished: true));
    }

    public function testTheMissingPublishedOptionAlsoAppliesToThePhpApi(): void
    {
        $block = $this->block(TitleBlock::class, ['title' => 'Hello']);
        unset($block['block_published']);

        self::assertNull($this->helper(new Environment(new ArrayLoader()))->renderBlock($block));
        self::assertSame(
            '<h2 id="block-1">Hello</h2>',
            (string) $this->helper($this->twig([]), missingPublishedMeansPublished: true)->renderBlock($block),
        );
    }

    public function testAnUnpublishedBlockIsRenderedInPreview(): void
    {
        $block = $this->block(TitleBlock::class, ['title' => 'Hello', 'block_published' => '0']);

        self::assertSame('<h2 id="block-1">Hello</h2>', $this->renderPage('{{ happy_cms_block_render(block, true) }}', ['block' => $block]));
    }

    public function testARenderErrorIsThrownByDefault(): void
    {
        $this->expectException(RuntimeError::class);

        $this->renderPage(
            'before {{ happy_cms_block_render(broken) }} after',
            ['broken' => $this->block(FailingBlock::class)],
        );
    }

    public function testARenderErrorIsLoggedAndTheBlockSkippedWhenRenderErrorsAreSwallowed(): void
    {
        $html = $this->renderPage(
            'before {{ happy_cms_block_render(broken) }}{{ happy_cms_block_render(title) }} after',
            [
                'broken' => $this->block(FailingBlock::class, ['position' => 3]),
                'title' => $this->block(TitleBlock::class, ['title' => 'Still here']),
            ],
            swallowRenderErrors: true,
        );

        self::assertSame('before <h2 id="block-2">Still here</h2> after', $html);

        $logs = $this->logger->cleanLogs();
        self::assertCount(1, $logs);
        [$level, $message, $context] = $logs[0];
        self::assertSame(LogLevel::ERROR, $level);
        self::assertStringContainsString('has been skipped', $message);
        self::assertSame(FailingBlock::class, $context['block_type']);
        self::assertSame(3, $context['position']);
        self::assertInstanceOf(\Throwable::class, $context['exception']);
    }

    public function testRenderErrorsAreSwallowedWithInheritContextToo(): void
    {
        $html = $this->renderPage(
            '[{{ happy_cms_block_render(broken) }}]',
            ['broken' => $this->block(FailingBlock::class)],
            inheritContext: true,
            swallowRenderErrors: true,
        );

        self::assertSame('[]', $html);
        self::assertCount(1, $this->logger->cleanLogs());
    }

    public function testASwallowedErrorLeavesNoPartialOutput(): void
    {
        $html = $this->renderPage(
            '[{{ happy_cms_block_render(broken) }}]',
            ['broken' => $this->block(FailingBlock::class)],
            swallowRenderErrors: true,
            failingTemplate: '<section>partial {{ settings.missing.deep }}</section>',
        );

        self::assertSame('[]', $html);
        self::assertCount(1, $this->logger->cleanLogs());
    }

    public function testRenderErrorsAreSwallowedWithoutLogger(): void
    {
        $twig = $this->twig(['blocks/failing.html.twig' => '{{ settings.missing.deep }}']);
        $helper = new Helper(...$this->helperArguments($twig), swallowRenderErrors: true);

        self::assertNull($helper->renderBlock($this->block(FailingBlock::class)));
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

        self::assertSame('', $this->renderPage('{{ happy_cms_block_render(block) }}', ['block' => $block], swallowRenderErrors: true));
        self::assertSame([], $this->logger->cleanLogs());
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
        bool $missingPublishedMeansPublished = false,
        bool $swallowRenderErrors = false,
        string $blockTemplate = '<h2 id="{{ settings.attr_id }}">{{ settings.title }}</h2>',
        string $failingTemplate = '{{ settings.missing.deep }}',
    ): string {
        $twig = $this->twig([
            'page.html.twig' => $pageTemplate,
            'blocks/title.html.twig' => $blockTemplate,
            'blocks/failing.html.twig' => $failingTemplate,
        ]);

        $helper = $this->helper($twig, $inheritContext, $missingPublishedMeansPublished, $swallowRenderErrors);
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

    private function helper(
        Environment $twig,
        bool $inheritContext = false,
        bool $missingPublishedMeansPublished = false,
        bool $swallowRenderErrors = false,
    ): Helper {
        return new Helper(
            ...$this->helperArguments($twig),
            inheritContext: $inheritContext,
            missingPublishedMeansPublished: $missingPublishedMeansPublished,
            swallowRenderErrors: $swallowRenderErrors,
            logger: $this->logger,
        );
    }

    /**
     * @return array{Environment, EventDispatcher, BlockCollection, AssetRenderer}
     */
    private function helperArguments(Environment $twig): array
    {
        return [
            $twig,
            new EventDispatcher(),
            new BlockCollection([new TitleBlock(), new FailingBlock(), new ContextBlock()]),
            new AssetRenderer(new EventDispatcher(), $this->createStub(TagRenderer::class), new Packages()),
        ];
    }
}
