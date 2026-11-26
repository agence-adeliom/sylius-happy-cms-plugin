<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\ContentBlock;

use Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock\PreviewLabels;
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

    /**
     * @test
     */
    public function it_sets_the_block_name_attribute(): void
    {
        $html = $this->renderer->wrap('', 1, null, true, false, 'Hero');

        self::assertStringContainsString('data-block-name="Hero"', $html);
    }

    /**
     * @test
     */
    public function it_escapes_the_block_name(): void
    {
        $html = $this->renderer->wrap('', 1, null, true, false, 'A "B" <C> & D');

        self::assertStringContainsString('data-block-name="A &quot;B&quot; &lt;C&gt; &amp; D"', $html);
        self::assertStringContainsString('<strong', $html);
        self::assertStringContainsString('A &quot;B&quot; &lt;C&gt; &amp; D</strong>', $html);
        self::assertStringNotContainsString('<C>', $html);
    }

    /**
     * @test
     */
    public function it_omits_the_block_name_attribute_without_name(): void
    {
        $html = $this->renderer->wrap('', 1, null, true, false);

        self::assertStringNotContainsString('data-block-name', $html);
    }

    /**
     * @test
     */
    public function it_renders_a_hidden_empty_placeholder(): void
    {
        $html = $this->renderer->wrap('<p>Hello</p>', 1, null, true, false, 'Hero');

        self::assertSame(1, substr_count($html, 'class="content-block-empty-placeholder"'));
        self::assertMatchesRegularExpression('/class="content-block-empty-placeholder" style="[^"]*display: none/', $html);

        $content = strpos($html, '<p>Hello</p>');
        $placeholder = strpos($html, 'content-block-empty-placeholder');
        $hover = strpos($html, 'content-block-hover-overlay');
        self::assertTrue($content < $placeholder && $placeholder < $hover);
    }

    /**
     * @test
     */
    public function it_renders_the_placeholder_before_the_state_overlay(): void
    {
        $html = $this->renderer->wrap('', 1, null, false, false, 'Hero');

        self::assertLessThan(strpos($html, 'content-block-state-overlay '), strpos($html, 'content-block-empty-placeholder'));
    }

    /**
     * @test
     */
    public function it_renders_the_icon_in_the_placeholder(): void
    {
        $html = $this->renderer->wrap('', 1, null, true, false, 'Hero', '<img src="x.webp">');
        self::assertStringContainsString('<img src="x.webp">', $html);

        $html = $this->renderer->wrap('', 1, null, true, false, 'Hero', ['<img src="a.webp">', '<img src="b.webp">']);
        self::assertStringContainsString('a.webp', $html);
        self::assertStringNotContainsString('b.webp', $html);

        foreach ([null, '', []] as $icon) {
            $html = $this->renderer->wrap('', 1, null, true, false, 'Hero', $icon);
            self::assertStringNotContainsString('content-block-empty-placeholder-icon', $html);
        }
    }

    /**
     * @test
     */
    public function it_uses_the_given_labels(): void
    {
        $labels = new PreviewLabels('Éditer le bloc', 'Bloc non publié', 'Bloc supprimé', 'Aucun rendu');
        $html = $this->renderer->wrap('', 1, null, false, false, 'Hero', null, $labels);

        self::assertStringContainsString('Éditer le bloc', $html);
        self::assertStringContainsString('Bloc non publié', $html);
        self::assertStringContainsString('Aucun rendu', $html);
        self::assertStringNotContainsString('Edit Block', $html);
        self::assertStringNotContainsString('Unpublished Block', $html);

        $html = $this->renderer->wrap('', 1, null, true, true, 'Hero', null, $labels);
        self::assertStringContainsString('Bloc supprimé', $html);
        self::assertStringNotContainsString('Deleted Block', $html);
    }

    /**
     * @test
     */
    public function it_escapes_the_labels(): void
    {
        $labels = new PreviewLabels('<b>Edit</b>', 'U', 'D', '"><script>');
        $html = $this->renderer->wrap('', 1, null, true, false, 'Hero', null, $labels);

        self::assertStringNotContainsString('<b>Edit</b>', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    /**
     * @test
     */
    public function it_keeps_the_english_labels_by_default(): void
    {
        $html = $this->renderer->wrap('', 1, null, false, false);

        self::assertStringContainsString('Edit Block', $html);
        self::assertStringContainsString('Unpublished Block', $html);
    }

    /**
     * @test
     */
    public function it_gives_the_edit_button_a_fixed_height(): void
    {
        $html = $this->renderer->wrap('', 1, null, true, false);

        self::assertMatchesRegularExpression('/class="content-block-edit-button"[^>]*style="[^"]*height: 44px/', $html);
        self::assertMatchesRegularExpression('/class="content-block-edit-button"[^>]*style="[^"]*box-sizing: border-box/', $html);
    }

    /**
     * @test
     */
    public function it_gives_the_state_badge_a_fixed_height(): void
    {
        $html = $this->renderer->wrap('', 1, null, false, false);

        self::assertMatchesRegularExpression('/class="content-block-state-badge" style="[^"]*height: 40px; box-sizing: border-box/', $html);
    }
}
