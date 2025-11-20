<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Form\Block;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Form\AdminFormTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\EventListener\ResizeFormListener;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlockCollectionType extends CollectionType implements AdminFormTypeInterface
{
    /**
     * @param array{
     *     allow_extra_fields: ?bool,
     *     allow_add: bool,
     *     allow_delete: bool,
     *     allow_drag: bool,
     *     delete_empty: bool,
     *     entry_options: array,
     *     entry_type: class-string,
     *     prototype: ?string,
     *     prototype_name: string,
     *     prototype_data: array|null,
     *     required: bool,
     *     allow_extra_fields?: bool,
     *     blocks: FormTypeInterface[],
     * } $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['allow_add'] && $options['prototype']) {
            $prototypeOptions = array_replace([
                'required' => $options['required'],
                'label' => $options['prototype_name'] . 'label__',
            ], $options['entry_options']);

            if (null !== $options['prototype_data']) {
                $prototypeOptions['data'] = $options['prototype_data'];
            }

            $prototypeOptions['compound'] = true;
            $prototypeOptions['allow_extra_fields'] = true;

            $prototypes = [];

            foreach ($options['blocks'] as $type => $block) {
                $name = sprintf('__block_%s__', $block->getBlockPrefix());
                if (
                    !empty($prototypeOptions['label']) &&
                    str_contains('label__', (string) $prototypeOptions['label'])
                ) {
                    $prototypeOptions['label'] = $name . 'label__';
                }

                $form = $builder->create($name, $block::class, $prototypeOptions);
                foreach ($form as $child) {
                    if (!in_array($child->getName(), ['block_type', 'block_published', 'position'])) {
                        $form->remove($child->getName());
                    }
                }
                $prototypes[$type] = $form->getForm();
            }

            $builder->setAttribute('prototypes', $prototypes);
        }

        $resizeListener = new ResizeFormListener(
            $options['entry_type'],
            $options['entry_options'],
            $options['allow_add'],
            $options['allow_delete'],
            $options['delete_empty'],
        );

        $builder->addEventSubscriber($resizeListener);
    }

    /**
     * @param array{
     *     allow_extra_fields: ?bool,
     *     allow_add: bool,
     *     allow_delete: bool,
     *     allow_drag: bool,
     *     delete_empty: bool,
     *     entry_options: array,
     *     entry_type: class-string,
     *     prototype: ?string,
     *     prototype_name: string,
     *     prototype_data: array|null,
     *     required: bool,
     *     allow_extra_fields?: bool,
     *     blocks: FormTypeInterface[],
     * } $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars = array_replace($view->vars, [
            'allow_drag' => $options['allow_drag'],
            'allow_add' => $options['allow_add'],
            'allow_delete' => $options['allow_delete'],
            'blocks' => $options['blocks'],
        ]);
        if ($form->getConfig()->hasAttribute('prototypes')) {
            $prototypes = $form->getConfig()->getAttribute('prototypes');
            $view->vars['prototypes'] = [];
            if (is_array($prototypes)) {
                foreach ($prototypes as $type => $prototype) {
                    if ($prototype instanceof FormInterface) {
                        $view->vars['prototypes'][$type] = $prototype->setParent($form)->createView($view);
                    } else {
                        throw new \InvalidArgumentException('Prototype should be an instance of FormInterface');
                    }
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $entryOptionsNormalizer = static function (Options $options, $value) {
            $value['block_name'] = 'entry';

            return $value;
        };

        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'allow_drag' => false,
            'allow_add' => false,
            'allow_delete' => false,
            'prototype' => true,
            'prototypes' => [],
            'prototype_data' => null,
            'prototype_name' => '__name__',
            'entry_type' => TextType::class,
            'entry_options' => [],
            'delete_empty' => true,
            'by_reference' => false,
            'blocks' => [],
            'invalid_message' => static fn (
                Options $options,
                $previousValue,
            ) => ($options['legacy_error_messages'] ?? true)
                ? $previousValue
                : 'The collection is invalid.',
        ]);

        $resolver->setNormalizer('entry_options', $entryOptionsNormalizer);
        $resolver->setAllowedTypes('delete_empty', ['bool', 'callable']);
        $resolver->setAllowedTypes('blocks', 'array');
        $resolver->setAllowedTypes('allow_drag', 'bool');
    }

    /**
     * @inheritdoc
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $prefixOffset = -2;
        // check if the entry type also defines a block prefix
        /** @var FormInterface $entry */
        foreach ($form as $entry) {
            if ($entry->getConfig()->getOption('block_prefix')) {
                --$prefixOffset;
            }

            break;
        }

        foreach ($view as $entryView) {
            array_splice($entryView->vars['block_prefixes'], $prefixOffset, 0, 'editor_collection_entry');
        }

        /** @var FormInterface[] $prototypes */
        $prototypes = $form->getConfig()->getAttribute('prototypes');
        if ($prototypes) {
            foreach ($prototypes as $type => $prototype) {
                if ($view->vars['prototypes'][$type]->vars['multipart']) {
                    $view->vars['multipart'] = true;
                }

                if ($prefixOffset > -3 && $prototype->getConfig()->getOption('block_prefix')) {
                    --$prefixOffset;
                }

                array_splice($view->vars['prototypes'][$type]->vars['block_prefixes'], $prefixOffset, 0, 'editor_collection_entry');
            }
        }
    }

    public function getBlockPrefix(): string
    {
        return 'flexible_content_collection';
    }

    public static function configureAdminAssets(): array
    {
        return [
            'js' => [
                (Asset::new('flexible-content.js'))->package(AssetHappyCMSPackage::PACKAGE_NAME),
            ],
            'css' => [
                (Asset::new('flexible-content.css'))->package(AssetHappyCMSPackage::PACKAGE_NAME),
            ],
        ];
    }

    public static function configureAdminFormThemes(): array
    {
        return [
            '@SyliusHappyCMSPlugin/field/flexible_content/form.html.twig',
        ];
    }
}
