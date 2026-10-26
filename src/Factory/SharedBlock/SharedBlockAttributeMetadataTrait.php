<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Attribute\BlockAttributeResolver;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Serves the metadata methods of a shared block from its #[AsSharedBlock] attribute: getName(), getIcon(),
 * getFrontEndTemplatePath(), getPosition() and getDescription(). A block overriding one of these methods keeps its
 * method.
 *
 * With #[AsSharedBlock(block:)], the values left out of the attribute and the form (buildBlock()) come from that
 * content block, injected by the container (setSharedBlockSource(), see CheckBlockAttributesPass).
 *
 * Opt-in in 2.x, to keep AbstractSharedBlockType unchanged: use it in the block, or once in the project's base shared
 * block class. Temporary: in 3.0, AbstractSharedBlockType reads the attribute itself and this trait is removed.
 *
 * @phpstan-require-extends AbstractSharedBlockType
 *
 * @deprecated since 2.3.3, to be removed in 3.0, where AbstractSharedBlockType reads #[AsSharedBlock] itself: only remove "use SharedBlockAttributeMetadataTrait;" when upgrading.
 */
trait SharedBlockAttributeMetadataTrait
{
    private ?AbstractBlock $sharedBlockSource = null;

    /**
     * @internal called by the container for #[AsSharedBlock(block:)] (see CheckBlockAttributesPass)
     */
    public function setSharedBlockSource(AbstractBlock $block): void
    {
        $this->sharedBlockSource = $block;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $source = $this->sourceBlock();
        if (null === $source) {
            parent::buildBlock($builder, $options);

            return;
        }

        $source->buildBlock($builder, $options);
    }

    public function getName(): string
    {
        return BlockAttributeResolver::get(static::class, AsSharedBlock::class, __FUNCTION__)->name
            ?? $this->sourceBlock()?->getName()
            ?? throw $this->missingSharedBlockMetadata(__FUNCTION__);
    }

    /**
     * @return string|mixed[]
     */
    public function getIcon(): string|array
    {
        return BlockAttributeResolver::get(static::class, AsSharedBlock::class, __FUNCTION__)->icon
            ?? $this->sourceBlock()?->getIcon()
            ?? '';
    }

    public function getFrontEndTemplatePath(): string
    {
        return BlockAttributeResolver::get(static::class, AsSharedBlock::class, __FUNCTION__)->template
            ?? $this->sourceBlock()?->getFrontEndTemplatePath()
            ?? throw $this->missingSharedBlockMetadata(__FUNCTION__);
    }

    /**
     * Not part of SharedBlockTypeInterface: shown in the shared block type picker (shared_block/crud/select/blocks.html.twig).
     */
    public function getDescription(): string
    {
        return BlockAttributeResolver::find(static::class, AsSharedBlock::class)->description ?? '';
    }

    public function getPosition(): int
    {
        return BlockAttributeResolver::find(static::class, AsSharedBlock::class)->position
            ?? $this->sourceBlock()?->getPosition()
            ?? parent::getPosition();
    }

    /**
     * The content block of #[AsSharedBlock(block:)], null without it.
     */
    private function sourceBlock(): ?AbstractBlock
    {
        $source = BlockAttributeResolver::find(static::class, AsSharedBlock::class)->block ?? null;
        if (null === $source) {
            return null;
        }

        return $this->sharedBlockSource ?? throw new \LogicException(sprintf(
            'The shared block "%s" takes the metadata and the form of "%s" (#[AsSharedBlock(block:)]) but this block ' .
            'was not injected: get the shared block from the container, or call setSharedBlockSource().',
            static::class,
            $source,
        ));
    }

    private function missingSharedBlockMetadata(string $method): \LogicException
    {
        return new \LogicException(sprintf(
            'The shared block "%s" has no metadata for %s(): set it in #[AsSharedBlock], set #[AsSharedBlock(block:)] ' .
            'or override %2$s().',
            static::class,
            $method,
        ));
    }
}
