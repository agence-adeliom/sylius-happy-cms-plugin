<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Adapted from Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener
 *
 * Each entry of the collection is a block (`block_type`, `block_published`, `position` and the block fields) whose
 * form type is its `block_type`. Entries are displayed in `position` order. On submit, the collection is sorted by
 * the submitted positions, re-indexed as a list (so that it is stored as a JSON array, not as an object keyed by the
 * form names) and the positions are renumbered 0, 1, 2…
 *
 * @phpstan-type BlockData array{block_type?: string|null, position?: mixed}
 */
class ResizeFormListener implements EventSubscriberInterface
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $prototypeOptions
     */
    public function __construct(
        protected string $type,
        protected array $options = [],
        protected bool $allowAdd = false,
        protected bool $allowDelete = false,
        private bool|\Closure $deleteEmpty = false,
        protected array $prototypeOptions = [],
    ) {
        $this->deleteEmpty = \is_bool($deleteEmpty) ? $deleteEmpty : $deleteEmpty(...);
        $this->prototypeOptions = empty($prototypeOptions) ? $options : $prototypeOptions;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
            // (MergeCollectionListener, MergeDoctrineCollectionListener)
            FormEvents::SUBMIT => ['onSubmit', 50],
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var array<array-key, BlockData>|(\Traversable<array-key, BlockData>&\ArrayAccess<array-key, BlockData>) $data */
        $data = $event->getData() ?? [];

        if (!\is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // First remove all rows
        foreach ($form as $name => $child) {
            $form->remove((string) $name);
        }

        if (is_array($data)) {
            $data = self::sortByPosition($data);
        }

        // Then add all rows again in the correct order
        foreach ($data as $name => $value) {
            $blockType = self::blockType($value);
            if (null !== $blockType) {
                $this->addRow($form, (string) $name, $blockType);
            }
        }
    }

    public function preSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (!\is_array($data)) {
            $data = [];
        }

        // Remove all empty rows
        if ($this->allowDelete) {
            foreach ($form as $name => $child) {
                if (!isset($data[$name])) {
                    $form->remove((string) $name);
                }
            }
        }

        // Add all additional rows (a row without block type cannot be built: it is dropped on submit)
        if ($this->allowAdd) {
            foreach ($data as $name => $value) {
                $blockType = self::blockType($value);
                if (null !== $blockType && !$form->has((string) $name)) {
                    $this->addRow($form, (string) $name, $blockType);
                }
            }
        }
    }

    public function onSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var array<array-key, BlockData>|(\Traversable<array-key, BlockData>&\ArrayAccess<array-key, BlockData>) $data */
        $data = $event->getData() ?? [];

        // At this point, $data is an array or an array-like object that already contains the
        // new entries, which were added by the data mapper. The data mapper ignores existing
        // entries, so we need to manually unset removed entries in the collection.

        if (!\is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        if ($this->deleteEmpty) {
            /** @var array<string|int, mixed> $previousData */
            $previousData = $form->getData();
            /** @var FormInterface $child */
            foreach ($form as $name => $child) {
                if (!$child->isValid() || !$child->isSynchronized()) {
                    continue;
                }

                $isNew = !isset($previousData[$name]);
                $isEmpty = \is_callable($this->deleteEmpty) ? ($this->deleteEmpty)($child->getData()) : $child->isEmpty();

                // $isNew can only be true if allowAdd is true, so we don't
                // need to check allowAdd again
                if ($isEmpty && ($isNew || $this->allowDelete)) {
                    unset($data[$name]);
                    $form->remove((string) $name);
                }
            }
        }

        // The data mapper only adds, but does not remove items, so do this
        // here
        if ($this->allowDelete) {
            $toDelete = [];

            foreach ($data as $name => $child) {
                if (!$form->has((string) $name)) {
                    $toDelete[] = $name;
                }
            }

            foreach ($toDelete as $name) {
                unset($data[$name]);
            }
        }

        if (is_array($data)) {
            $data = array_values(self::sortByPosition($data));

            foreach ($data as $index => $value) {
                if (is_array($value)) {
                    $data[$index]['position'] = $index;
                }
            }
        }

        $event->setData($data);
    }

    private function addRow(FormInterface $form, string $name, string $blockType): void
    {
        $form->add($name, $blockType, array_replace([
            'property_path' => '[' . $name . ']',
        ], $this->prototypeOptions));
    }

    private static function blockType(mixed $value): ?string
    {
        if (!is_array($value) || !isset($value['block_type']) || !is_string($value['block_type']) || '' === $value['block_type']) {
            return null;
        }

        return $value['block_type'];
    }

    /**
     * Stable sort by numeric `position` (string or int); entries without a numeric position keep their relative
     * order, after the others. Keys are preserved.
     *
     * @template T
     *
     * @param array<array-key, T> $data
     *
     * @return array<array-key, T>
     */
    private static function sortByPosition(array $data): array
    {
        $order = 0;
        $keys = [];
        foreach ($data as $name => $value) {
            $position = is_array($value) ? ($value['position'] ?? null) : null;
            $keys[$name] = [is_numeric($position) ? 0 : 1, is_numeric($position) ? (float) $position : 0.0, $order++];
        }

        uksort($data, static fn (int|string $a, int|string $b): int => $keys[$a] <=> $keys[$b]);

        return $data;
    }
}
