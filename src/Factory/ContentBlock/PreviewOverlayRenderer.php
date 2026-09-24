<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock;

/**
 * Builds the page builder preview markup around a rendered content block:
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

    public function wrap(string $content, int $blockId, ?string $layer, bool $isPreviewPublished, bool $isDeleted): string
    {
        $layerAttr = $layer ? sprintf(' data-block-layer="%s"', htmlspecialchars($layer, \ENT_QUOTES, 'UTF-8')) : '';

        // Deleted state takes priority over the unpublished one
        $stateOverlay = '';
        if ($isDeleted) {
            $stateOverlay = $this->renderStateOverlay('deleted', self::DANGER, self::ICON_TRASH, 'Deleted Block', $blockId);
        } elseif (!$isPreviewPublished) {
            $stateOverlay = $this->renderStateOverlay('unpublished', self::WARNING, self::ICON_EXCLAMATION_CIRCLE, 'Unpublished Block', $blockId);
        }

        // Hover overlay with edit button (always present but hidden until the page builder CSS shows it)
        $hoverOverlay = sprintf(
            '<div class="content-block-hover-overlay" style="position: absolute; inset: 0; z-index: 11; display: none; align-items: center; justify-content: center; background: color-mix(in srgb, %s 15%%, transparent);">%s</div>',
            self::ACCENT,
            $this->renderEditButton($blockId),
        );

        return sprintf(
            '<div data-block-id="%d"%s data-published="%s" data-deleted="%s" class="content-block-wrapper" style="position: relative;">%s%s%s</div>',
            $blockId,
            $layerAttr,
            $isPreviewPublished ? 'true' : 'false',
            $isDeleted ? 'true' : 'false',
            $content,
            $stateOverlay,
            $hoverOverlay,
        );
    }

    private function renderStateOverlay(string $state, string $color, string $iconPath, string $label, int $blockId): string
    {
        return sprintf(
            '<div class="content-block-state-overlay content-block-state-overlay--%1$s content-block-%1$s-overlay" style="position: absolute; inset: 0; z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; background: color-mix(in srgb, %2$s 40%%, transparent);">'
            . '<div class="content-block-state-badge" style="display: flex; align-items: center; gap: 6px; padding: 12px 20px; border-radius: 6px; background: rgba(255, 255, 255, 0.95); color: %2$s; font-size: 14px; font-weight: 600; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); pointer-events: none;">%3$s%4$s</div>'
            . '%5$s'
            . '</div>',
            $state,
            $color,
            $this->renderIcon($iconPath),
            $label,
            $this->renderEditButton($blockId),
        );
    }

    private function renderEditButton(int $blockId): string
    {
        return sprintf(
            '<button class="content-block-edit-button" data-block-id="%1$d" style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; border: none; border-radius: 6px; background: %2$s; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px color-mix(in srgb, %2$s 40%%, transparent); transition: all 0.2s;">%3$sEdit Block</button>',
            $blockId,
            self::ACCENT,
            $this->renderIcon(self::ICON_PENCIL),
        );
    }

    private function renderIcon(string $path): string
    {
        return sprintf('<svg %s><path d="%s"/></svg>', self::ICON_ATTRIBUTES, $path);
    }
}
