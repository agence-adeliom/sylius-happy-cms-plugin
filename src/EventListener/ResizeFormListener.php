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
        $data = $event->getData() ?? [];

        if (!\is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // First remove all rows
        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        if (is_array($data)) {
            uasort($data, static fn ($a, $b) => $a['position'] <=> $b['position']);
        }

        // Then add all rows again in the correct order
        foreach ($data as $name => $value) {
            if (!empty($value['block_type'])) {
                $form->add(
                    $name,
                    $value['block_type'],
                    array_replace([
                      'property_path' => '[' . $name . ']',
                    ], $this->prototypeOptions),
                );
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
                    $form->remove($name);
                }
            }
        }

        // Add all additional rows
        if ($this->allowAdd) {
            foreach ($data as $name => $value) {
                if (!$form->has($name)) {
                    $form->add($name, $value['block_type'], array_replace([
                      'property_path' => '[' . $name . ']',
                  ], $this->prototypeOptions));
                }
            }
        }
    }

    public function onSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
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
                    $form->remove($name);
                }
            }
        }

        // The data mapper only adds, but does not remove items, so do this
        // here
        if ($this->allowDelete) {
            $toDelete = [];

            foreach ($data as $name => $child) {
                if (!$form->has($name)) {
                    $toDelete[] = $name;
                }
            }

            foreach ($toDelete as $name) {
                unset($data[$name]);
            }
        }

        foreach ($data as $name => $value) {
            if (!$value['position']) {
                $data[$name]['position'] = 999;
            }
        }

        if (is_array($data)) {
            uasort($data, static fn ($a, $b) => $a['position'] <=> $b['position']);
        }

        $event->setData($data);
    }
}
