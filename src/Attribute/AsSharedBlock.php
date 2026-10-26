<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Attribute;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;

/**
 * Declares the metadata of a shared block on its class, instead of overriding the metadata methods.
 *
 * Read by SharedBlockAttributeMetadataTrait (to use in the block or its base class; in 3.0, by
 * AbstractSharedBlockType), which serves each value as the default of the matching method: getName(), getIcon(),
 * getFrontEndTemplatePath(), getPosition() and getDescription(). A block can still override any of these
 * methods: the method wins over the attribute.
 *
 * With $block, the shared block takes the metadata and the form (buildBlock()) of a content block: the values left
 * out come from that block, the values set here win over it.
 *
 * ```php
 * #[AsSharedBlock(block: TextMediaType::class, description: 'Text and media, shared between pages')]
 * class SharedTextMediaType extends AbstractSharedBlockType
 * {
 *     use SharedBlockAttributeMetadataTrait;
 * }
 * ```
 *
 * Requires "sylius_easy_crud.attributes.enabled: true", like #[AsAdmin]: the container compilation fails otherwise
 * (see CheckBlockAttributesPass).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsSharedBlock
{
    /**
     * @param string|null                      $name        Name of the shared block type (feeds getName()). Required without $block.
     * @param string|null                      $template    Front-end template of the block (feeds getFrontEndTemplatePath()). Required without $block.
     * @param string|mixed[]|null              $icon        Icon of the shared block type (feeds getIcon()). Empty by default.
     * @param int|null                         $position    Position in the list of shared block types (feeds getPosition()). 100 by default.
     * @param string|null                      $description Description of the shared block type (feeds getDescription()).
     * @param class-string<AbstractBlock>|null $block       Content block whose metadata and form the shared block takes.
     */
    public function __construct(
        public ?string $name = null,
        public ?string $template = null,
        public string|array|null $icon = null,
        public ?int $position = null,
        public ?string $description = null,
        public ?string $block = null,
    ) {
    }
}
