<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\ContentBlock;

use Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock\PreviewOverlayRenderer;
use PHPUnit\Framework\TestCase;

class PreviewOverlayRendererTest extends TestCase
{
    private PreviewOverlayRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new PreviewOverlayRenderer();
    }

    /**
     * @test
     */
    public function it_wraps_the_content_with_the_block_attributes(): void
    {
        $html = $this->renderer->wrap('<p>Hello</p>', 42, 'hero', true, false);

        self::assertStringStartsWith('<div data-block-id="42" data-block-layer="hero" data-published="true" data-deleted="false" class="content-block-wrapper" style="position: relative;">', $html);
        self::assertStringContainsString('<p>Hello</p>', $html);
        self::assertStringEndsWith('</div>', $html);
    }

    /**
     * @test
     */
    public function it_omits_the_layer_attribute_without_layer(): void
    {
        $html = $this->renderer->wrap('', 42, null, true, false);

        self::assertStringNotContainsString('data-block-layer', $html);
    }

    /**
     * @test
     */
    public function it_escapes_the_layer_attribute(): void
    {
        $html = $this->renderer->wrap('', 42, '"><script>', true, false);

        self::assertStringContainsString('data-block-layer="&quot;&gt;&lt;script&gt;"', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    /**
     * @test
     */
    public function it_renders_only_the_hover_overlay_for_a_published_block(): void
    {
        $html = $this->renderer->wrap('', 42, null, true, false);

        self::assertStringNotContainsString('content-block-state-overlay', $html);
        self::assertStringContainsString('class="content-block-hover-overlay"', $html);
        self::assertSame(1, substr_count($html, 'class="content-block-edit-button" data-block-id="42"'));
    }

    /**
     * @test
     */
    public function it_renders_the_unpublished_overlay_with_the_warning_color(): void
    {
        $html = $this->renderer->wrap('', 42, null, false, false);

        self::assertStringContainsString('content-block-state-overlay content-block-state-overlay--unpublished content-block-unpublished-overlay', $html);
        self::assertStringContainsString('color: var(--hcms-warning, #f59f00)', $html);
        self::assertStringContainsString('Unpublished Block', $html);
        self::assertSame(2, substr_count($html, 'class="content-block-edit-button" data-block-id="42"'));
    }

    /**
     * @test
     */
    public function it_gives_priority_to_the_deleted_overlay(): void
    {
        $html = $this->renderer->wrap('', 42, null, false, true);

        self::assertStringContainsString('content-block-state-overlay content-block-state-overlay--deleted content-block-deleted-overlay', $html);
        self::assertStringContainsString('color: var(--hcms-danger, #d63939)', $html);
        self::assertStringContainsString('Deleted Block', $html);
        self::assertStringNotContainsString('--unpublished', $html);
        self::assertSame(1, substr_count($html, 'content-block-state-overlay '));
    }

    /**
     * @test
     */
    public function it_uses_the_sylius_accent_instead_of_the_legacy_blue(): void
    {
        $html = $this->renderer->wrap('', 42, null, false, true);

        self::assertStringContainsString('background: var(--hcms-accent, #22b99a)', $html);
        self::assertStringContainsString('background: color-mix(in srgb, var(--hcms-accent, #22b99a) 15%, transparent)', $html);
        self::assertStringNotContainsString('#1e74fd', $html);
        self::assertStringNotContainsString('30, 116, 253', $html);
    }
}
