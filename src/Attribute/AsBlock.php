<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Attribute;

/**
 * Declares the metadata of a content block on its class, instead of overriding the metadata methods.
 *
 * Read by AbstractBlock, which serves each value as the default of the matching method: getName(), getIcon(),
 * getFrontEndTemplatePath(), getPosition() and getTab(). A block can still override any of these methods (dynamic
 * icon, metadata delegated to another block…): the method wins over the attribute.
 *
 * Requires "sylius_easy_crud.attributes.enabled: true", like #[AsAdmin]: the container compilation fails otherwise
 * (see CheckBlockAttributesPass).
 *
 * ```php
 * #[AsBlock(name: 'My Block', template: '@App/blocks/my.html.twig', icon: 'ti ti-star', position: 10)]
 * class MyBlockType extends AbstractBlock
 * {
 *     public function buildBlock(FormBuilderInterface $builder, array $options): void { … }
 * }
 * ```
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsBlock
{
    /**
     * @param string               $name     Name shown in the block picker (feeds getName()), a translation key or a label.
     * @param string               $template Front-end template of the block (feeds getFrontEndTemplatePath()).
     * @param string|mixed[]       $icon     Icon shown in the block picker (feeds getIcon()). Empty by default.
     * @param int                  $position Position in the block picker (feeds getPosition()).
     * @param string|null          $tab      Tab of the block picker (feeds getTab()). Defaults to the generic blocks tab.
     */
    public function __construct(
        public string $name,
        public string $template,
        public string|array $icon = '',
        public int $position = 100,
        public ?string $tab = null,
    ) {
    }
}
