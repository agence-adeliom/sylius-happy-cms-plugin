<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\FlexibleContentField;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Doctrine\ORM\PersistentCollection;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use function Symfony\Component\String\u;

/**
 * Inspired by EasyAdmin Symfony Bundle
 */
final class FlexibleContentConfigurator implements FieldConfiguratorInterface
{
    public function __construct(protected BlockCollection $collection)
    {
    }

    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return FlexibleContentField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null): void
    {
        if (null !== $entryTypeFunction = $field->getCustomOptions()->get(FlexibleContentField::OPTION_ENTRY_TYPE)) {
            $field->setFormTypeOption('entry_type', $entryTypeFunction);
        }

        $autocompleteFormTypes = [
            CountryType::class,
            CurrencyType::class,
            LanguageType::class,
            LocaleType::class,
            TimezoneType::class,
        ];
        if (\in_array($entryTypeFunction, $autocompleteFormTypes, true)) {
            $field->setFormTypeOption('entry_options.attr.data-ea-widget', 'ea-autocomplete');
        }

        $field->setFormTypeOption(
            'allow_drag',
            $field->getCustomOptions()->get(FlexibleContentField::OPTION_ALLOW_DRAG),
        );
        $field->setFormTypeOption(
            'allow_add',
            $field->getCustomOptions()->get(FlexibleContentField::OPTION_ALLOW_ADD),
        );
        $field->setFormTypeOption(
            'allow_delete',
            $field->getCustomOptions()->get(FlexibleContentField::OPTION_ALLOW_DELETE),
        );
        $field->setFormTypeOptionIfNotSet('by_reference', false);
        $field->setFormTypeOptionIfNotSet('delete_empty', true);

        $blocksCollection = $this->collection->enabledSupportFilter();
        $blocks = $blocksCollection->getAllowedBlocks(
            $field->getCustomOptions()->get(FlexibleContentField::OPTION_BLOCKS),
        );

        foreach ($blocks as $blockType => $block) {
            if ($block instanceof BlockTypeInterface && method_exists($blockType, 'configureAdminAssets')) {
                $field->addAssets($block->configureAdminAssets());
            }
            if ($block instanceof BlockTypeInterface && method_exists($blockType, 'configureAdminFormThemes')) {
                $field->addFormThemes($block->configureAdminFormThemes());
            }
        }

        $field->setFormTypeOption('blocks', $blocks);

        // (generated values are always the same for all elements)
        $field->setFormTypeOptionIfNotSet(
            'entry_options.label',
            $field->getCustomOptions()->get(FlexibleContentField::OPTION_SHOW_ENTRY_LABEL),
        );

        // collection items range from a simple <input text> to a complex multi-field form
        // the 'entryIsComplex' setting tells if the collection item is so complex that needs a special
        // rendering not applied to simple collection items
        if (null === $field->getCustomOption(FlexibleContentField::OPTION_ENTRY_IS_COMPLEX)) {
            $definesEntryType = null !== $entryTypeFunction = $field->getCustomOption(
                FlexibleContentField::OPTION_ENTRY_TYPE,
            );
            $isSymfonyCoreFormType = null !== u($entryTypeFunction ?? '')
                ->indexOf('Symfony\Component\Form\Extension\Core\Type');

            $isComplexEntry = $definesEntryType && !$isSymfonyCoreFormType;

            $field->setCustomOption(FlexibleContentField::OPTION_ENTRY_IS_COMPLEX, $isComplexEntry);
        }

        $field->setValue($this->formatCollection($field));
    }

    private function formatCollection(FieldDto $field): int|string
    {
        if (
            'array' !== $field->getDoctrineMetadata()->get('type') &&
            !$field->getValue() instanceof PersistentCollection
        ) {
            return $this->countNumElements($field->getValue());
        }

        $collectionItemsAsText = [];
        foreach ($field->getValue() ?? [] as $item) {
            if (!\is_string($item) && !(\is_object($item) && method_exists($item, '__toString'))) {
                return $this->countNumElements($field->getValue());
            }

            $collectionItemsAsText[] = (string) $item;
        }

        return u(', ')->join($collectionItemsAsText)->truncate(512, '…')->toString();
    }

    private function countNumElements(mixed $collection): int
    {
        if (is_countable($collection)) {
            return \count($collection);
        }

        if ($collection instanceof \Traversable) {
            return iterator_count($collection);
        }

        return 0;
    }

    public function formatValue(FieldDto $field, mixed $value): mixed
    {
        return $value;
    }
}
