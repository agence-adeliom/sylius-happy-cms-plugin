<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\Block;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Adeliom\SyliusHappyCMSPlugin\Attribute\BlockAttributeResolver;

/**
 * Serves the metadata methods of a content block from its #[AsBlock] attribute: getName(), getIcon(),
 * getFrontEndTemplatePath(), getPosition() and getTab(). A block overriding one of these methods keeps its method.
 *
 * Opt-in in 2.x, to keep AbstractBlock unchanged: use it in the block, or once in the project's base block class.
 * In 3.0, AbstractBlock reads the attribute itself and this trait goes away.
 *
 * ```php
 * #[AsBlock(name: 'My Block', template: '@App/blocks/my.html.twig', icon: 'ti ti-star')]
 * class MyBlockType extends AbstractBlock
 * {
 *     use BlockAttributeMetadataTrait;
 *
 *     public function buildBlock(FormBuilderInterface $builder, array $options): void { … }
 * }
 * ```
 *
 * @phpstan-require-extends AbstractBlock
 */
trait BlockAttributeMetadataTrait
{
    public function getName(): string
    {
        return BlockAttributeResolver::get(static::class, AsBlock::class, __FUNCTION__)->name;
    }

    /**
     * @return string|mixed[]
     */
    public function getIcon(): string|array
    {
        return BlockAttributeResolver::get(static::class, AsBlock::class, __FUNCTION__)->icon;
    }

    public function getFrontEndTemplatePath(): string
    {
        return BlockAttributeResolver::get(static::class, AsBlock::class, __FUNCTION__)->template;
    }

    public function getTab(): string
    {
        return BlockAttributeResolver::find(static::class, AsBlock::class)->tab ?? parent::getTab();
    }

    public function getPosition(): int
    {
        return BlockAttributeResolver::find(static::class, AsBlock::class)->position ?? parent::getPosition();
    }
}
