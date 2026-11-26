<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock;

/**
 * Builds the page builder preview markup around a rendered content block:
 * the empty block placeholder (shown by the preview agent when the block renders nothing visible),
 * the state overlay (deleted / unpublished) and the hover overlay with the edit button.
 *
 * Structural styles stay inline so overlays render correctly before the page builder injects its CSS.
 * Colors use the --hcms-* variables forwarded by the page builder, with the Sylius colors as fallbacks.
 */
final class PreviewOverlayRenderer
{
    private const ACCENT = 'var(--hcms-accent, #22b99a)';

    private const WARNING = 'var(--hcms-warning, #f59f00)';

    private const DANGER = 'var(--hcms-danger, #d63939)';

    private const ICON_ATTRIBUTES = 'xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';

    private const ICON_PENCIL = 'M4 20h4L18.5 9.5a2.828 2.828 0 1 0-4-4L4 16zm9.5-13.5l4 4';

    private const ICON_TRASH = 'M4 7h16m-10 4v6m4-6v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3';

    private const ICON_EXCLAMATION_CIRCLE = 'M3 12a9 9 0 1 0 18 0a9 9 0 1 0-18 0m9-3v4m0 3v.01';

    /**
     * @param string|mixed[]|null $blockIcon HTML of the block type icon (the first one when an array is given), inserted as is
     */
    public function wrap(
        string $content,
        int $blockId,
        ?string $layer,
        bool $isPreviewPublished,
        bool $isDeleted,
        ?string $blockName = null,
        string|array|null $blockIcon = null,
        ?PreviewLabels $labels = null,
    ): string {
        $labels ??= new PreviewLabels();
        $layerAttr = $layer ? sprintf(' data-block-layer="%s"', self::escape($layer)) : '';
        $nameAttr = null !== $blockName && '' !== $blockName ? sprintf(' data-block-name="%s"', self::escape($blockName)) : '';

        // Deleted state takes priority over the unpublished one
        $stateOverlay = '';
        if ($isDeleted) {
            $stateOverlay = $this->renderStateOverlay('deleted', self::DANGER, self::ICON_TRASH, $labels->deletedBlock, $blockId, $labels);
        } elseif (!$isPreviewPublished) {
            $stateOverlay = $this->renderStateOverlay('unpublished', self::WARNING, self::ICON_EXCLAMATION_CIRCLE, $labels->unpublishedBlock, $blockId, $labels);
        }

        // Hover overlay with edit button (always present but hidden until the page builder CSS shows it)
        $hoverOverlay = sprintf(
            '<div class="content-block-hover-overlay" style="position: absolute; inset: 0; z-index: 11; display: none; align-items: center; justify-content: center; background: color-mix(in srgb, %s 15%%, transparent);">%s</div>',
            self::ACCENT,
            $this->renderEditButton($blockId, $labels),
        );

        return sprintf(
            '<div data-block-id="%d"%s%s data-published="%s" data-deleted="%s" class="content-block-wrapper" style="position: relative;">%s%s%s%s</div>',
            $blockId,
            $layerAttr,
            $nameAttr,
            $isPreviewPublished ? 'true' : 'false',
            $isDeleted ? 'true' : 'false',
            $content,
            $this->renderEmptyPlaceholder($blockName, $blockIcon, $labels),
            $stateOverlay,
            $hoverOverlay,
        );
    }

    /**
     * Hidden by default: the preview agent measures the rendered content and shows it (class "is-empty" on the wrapper).
     * It is positioned over the block, so it never changes the height the agent measures.
     *
     * @param string|mixed[]|null $blockIcon
     */
    private function renderEmptyPlaceholder(?string $blockName, string|array|null $blockIcon, PreviewLabels $labels): string
    {
        $icon = is_array($blockIcon) ? reset($blockIcon) : $blockIcon;
        $iconHtml = is_string($icon) && '' !== $icon
            ? sprintf('<div class="content-block-empty-placeholder-icon" style="flex: none; display: flex; align-items: center; height: 40px; max-width: 64px; overflow: hidden;">%s</div>', $icon)
            : '';

        $nameHtml = null !== $blockName && '' !== $blockName
            ? sprintf('<strong style="color: var(--hcms-text, #182433); font-weight: 600;">%s</strong>', self::escape($blockName))
            : '';

        return sprintf(
            '<div class="content-block-empty-placeholder" style="position: absolute; inset: 0; z-index: 5; display: none; align-items: center; gap: 12px; padding: 0 var(--hcms-block-gap, 16px); box-sizing: border-box; overflow: hidden; border: 2px dashed var(--hcms-border, #dadfe5); background: var(--hcms-surface-muted, #f6f8fb); color: var(--hcms-text-muted, #667382); font-size: 14px; line-height: 20px; pointer-events: none;">%s<div class="content-block-empty-placeholder-text" style="display: flex; flex-direction: column; min-width: 0;">%s<span>%s</span></div></div>',
            $iconHtml,
            $nameHtml,
            self::escape($labels->emptyBlock),
        );
    }

    private function renderStateOverlay(string $state, string $color, string $iconPath, string $label, int $blockId, PreviewLabels $labels): string
    {
        return sprintf(
            '<div class="content-block-state-overlay content-block-state-overlay--%1$s content-block-%1$s-overlay" style="position: absolute; inset: 0; z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; background: color-mix(in srgb, %2$s 40%%, transparent);">'
            . '<div class="content-block-state-badge" style="display: flex; align-items: center; gap: 6px; height: 40px; box-sizing: border-box; line-height: 20px; padding: 0 20px; white-space: nowrap; border-radius: 6px; background: rgba(255, 255, 255, 0.95); color: %2$s; font-size: 14px; font-weight: 600; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); pointer-events: none;">%3$s%4$s</div>'
            . '%5$s'
            . '</div>',
            $state,
            $color,
            $this->renderIcon($iconPath),
            self::escape($label),
            $this->renderEditButton($blockId, $labels),
        );
    }

    private function renderEditButton(int $blockId, PreviewLabels $labels): string
    {
        return sprintf(
            '<button class="content-block-edit-button" data-block-id="%1$d" style="display: flex; align-items: center; gap: 8px; height: 44px; box-sizing: border-box; line-height: 20px; padding: 0 24px; white-space: nowrap; border: none; border-radius: 6px; background: %2$s; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px color-mix(in srgb, %2$s 40%%, transparent); transition: all 0.2s;">%3$s%4$s</button>',
            $blockId,
            self::ACCENT,
            $this->renderIcon(self::ICON_PENCIL),
            self::escape($labels->editBlock),
        );
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES, 'UTF-8');
    }

    private function renderIcon(string $path): string
    {
        return sprintf('<svg %s><path d="%s"/></svg>', self::ICON_ATTRIBUTES, $path);
    }
}
