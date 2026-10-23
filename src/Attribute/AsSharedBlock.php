<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Attribute;

/**
 * Declares the metadata of a shared block on its class, instead of overriding the metadata methods.
 *
 * Read by AbstractSharedBlockType, which serves each value as the default of the matching method: getName(),
 * getIcon(), getFrontEndTemplatePath(), getPosition() and getDescription(). A block can still override any of these
 * methods: the method wins over the attribute.
 *
 * Requires "sylius_easy_crud.attributes.enabled: true", like #[AsAdmin]: the container compilation fails otherwise
 * (see CheckBlockAttributesPass).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsSharedBlock
{
    /**
     * @param string               $name        Name of the shared block type (feeds getName()).
     * @param string               $template    Front-end template of the block (feeds getFrontEndTemplatePath()).
     * @param string|mixed[]       $icon        Icon of the shared block type (feeds getIcon()). Empty by default.
     * @param int                  $position    Position in the list of shared block types (feeds getPosition()).
     * @param string|null          $description Description of the shared block type (feeds getDescription()).
     */
    public function __construct(
        public string $name,
        public string $template,
        public string|array $icon = '',
        public int $position = 100,
        public ?string $description = null,
    ) {
    }
}
