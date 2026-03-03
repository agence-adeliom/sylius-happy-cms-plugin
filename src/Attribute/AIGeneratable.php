<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Attribute;

use Attribute;

/**
 * Marks a block type as suitable for AI-powered content generation.
 *
 * Only blocks with this attribute will be included in the AI block schema
 * and available for AI-based content generation in the page builder.
 *
 * @example
 * ```php
 * #[AIGeneratable]
 * class TextCtaBlockType extends AbstractBlock
 * {
 *     // ...
 * }
 * ```
 *
 * You can optionally provide hints to help the AI understand when to use this block:
 * @example
 * ```php
 * #[AIGeneratable(
 *     description: 'A text block with call-to-action buttons',
 *     useCases: ['landing pages', 'promotional sections', 'feature highlights']
 * )]
 * class TextCtaBlockType extends AbstractBlock
 * {
 *     // ...
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AIGeneratable
{
    /**
     * @param string|null $description Optional description to help AI understand the block's purpose
     * @param array<string>|null $useCases Optional list of use cases for this block type
     * @param int $priority Priority for AI selection (higher = more likely to be selected). Default: 100
     */
    public function __construct(
        public readonly ?string $description = null,
        public readonly ?array $useCases = null,
        public readonly int $priority = 100,
    ) {
    }
}
