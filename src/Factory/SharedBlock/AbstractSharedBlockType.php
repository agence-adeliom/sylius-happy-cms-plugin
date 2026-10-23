<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Attribute\BlockAttributeResolver;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base of the shared blocks. The metadata methods (getName(), getIcon(), getFrontEndTemplatePath(), getPosition(),
 * getDescription()) read #[AsSharedBlock] by default; a block overrides them when its metadata is not static.
 * With #[AsSharedBlock(block:)], the values left out of the attribute and the form (buildBlock()) come from that
 * content block, injected by CheckBlockAttributesPass.
 */
abstract class AbstractSharedBlockType extends AbstractType implements SharedBlockTypeInterface
{
    private ?AbstractBlock $sharedBlockSource = null;

    public function __construct(protected EntityManagerInterface $manager)
    {
    }

    /**
     * @internal called by the container for #[AsSharedBlock(block:)] (see CheckBlockAttributesPass)
     */
    public function setSharedBlockSource(AbstractBlock $block): void
    {
        $this->sharedBlockSource = $block;
    }

    public function getManager(): EntityManagerInterface
    {
        return $this->manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('block_type', HiddenType::class, ['data' => $this::class])
            ->add('block_published', HiddenType::class)
            ->add('position', HiddenType::class)
        ;

        $this->buildBlock($builder, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $this->sourceBlock()?->buildBlock($builder, $options);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = [];
        $attr['block-title'] = $this->getName();
        $attr['block-icon'] = is_iterable($this->getIcon()) ? $this->getIcon()[0] : $this->getIcon();
        $view->vars['attr'] = $attr;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'cascade_validation' => true,
        ]);
    }

    /**
     * Declare here the assets that make front working as expected
     *
     * @return array<string, string[]>
     */
    public function configureAssets(): array
    {
        return [
            'js' => [],
            'css' => [],
            'webpack' => [],
        ];
    }

    /**
     * Declare here the assets that make back-office working as expected
     *
     * @return array<string, string[]>
     */
    public function configureAdminAssets(): array
    {
        return [
            'js' => [],
            'css' => [],
        ];
    }

    /**
     * Declare here the form themes path that make back-office form display as expected
     *
     * @return string[]
     */
    public function configureAdminFormThemes(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function researchableProperties(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function getDefaultSettings(): array
    {
        return [];
    }

    public function getName(): string
    {
        return BlockAttributeResolver::get(static::class, AsSharedBlock::class, __FUNCTION__)->name
            ?? $this->sourceBlock()?->getName()
            ?? throw $this->missingMetadata(__FUNCTION__);
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
            ?? throw $this->missingMetadata(__FUNCTION__);
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
            ?? 100;
    }

    public function supports(?ContentEditableInterface $resource = null): bool
    {
        return true;
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

    private function missingMetadata(string $method): \LogicException
    {
        return new \LogicException(sprintf(
            'The shared block "%s" has no metadata for %s(): set it in #[AsSharedBlock], set #[AsSharedBlock(block:)] ' .
            'or override %2$s().',
            static::class,
            $method,
        ));
    }
}
