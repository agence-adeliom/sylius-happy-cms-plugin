<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Form\Block\BlockCollectionType;

/**
 * Inspired by EasyAdmin Symfony Bundle
 */
class FlexibleContentField implements FieldInterface
{
    use FieldTrait;

    /**
     * @var string
     */
    public const OPTION_ALLOW_DRAG = 'allowDrag';

    /**
     * @var string
     */
    public const OPTION_ALLOW_ADD = 'allowAdd';

    /**
     * @var string
     */
    public const OPTION_ALLOW_DELETE = 'allowDelete';

    /**
     * @var string
     */
    public const OPTION_ENTRY_IS_COMPLEX = 'entryIsComplex';

    /**
     * @var string
     */
    public const OPTION_ENTRY_TYPE = 'entryType';

    /**
     * @var string
     */
    public const OPTION_SHOW_ENTRY_LABEL = 'showEntryLabel';

    /**
     * @var string
     */
    public const OPTION_RENDER_EXPANDED = 'renderExpanded';

    /**
     * @var string
     */
    public const OPTION_BLOCKS = 'blocks';

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(BlockCollectionType::class)
            ->addFormThemes(BlockCollectionType::configureAdminFormThemes())
            ->addAssets(BlockCollectionType::configureAdminAssets())
            ->setCustomOption(self::OPTION_ALLOW_DRAG, true)
            ->setCustomOption(self::OPTION_ALLOW_ADD, true)
            ->setCustomOption(self::OPTION_ALLOW_DELETE, true)
            ->setCustomOption(self::OPTION_ENTRY_IS_COMPLEX, null)
            ->setCustomOption(self::OPTION_SHOW_ENTRY_LABEL, false)
            ->setCustomOption(self::OPTION_RENDER_EXPANDED, false);
    }

    public function allowDrag(bool $allow = true): self
    {
        $this->setCustomOption(self::OPTION_ALLOW_DRAG, $allow);

        return $this;
    }

    public function allowAdd(bool $allow = true): self
    {
        $this->setCustomOption(self::OPTION_ALLOW_ADD, $allow);

        return $this;
    }

    public function allowDelete(bool $allow = true): self
    {
        $this->setCustomOption(self::OPTION_ALLOW_DELETE, $allow);

        return $this;
    }

    /**
     * Set this option to TRUE if the collection items are complex form types
     * composed of several form fields (EasyAdmin applies a special rendering to make them look better).
     */
    public function setEntryIsComplex(bool $isComplex): self
    {
        $this->setCustomOption(self::OPTION_ENTRY_IS_COMPLEX, $isComplex);

        return $this;
    }

    public function showEntryLabel(bool $showLabel = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_ENTRY_LABEL, $showLabel);

        return $this;
    }

    public function renderExpanded(bool $renderExpanded = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_EXPANDED, $renderExpanded);

        return $this;
    }

    /**
     * @param array<int, class-string> $blocks
     */
    public function allowedBlocks(array $blocks): self
    {
        $this->setCustomOption(self::OPTION_BLOCKS, $blocks);

        return $this;
    }
}
