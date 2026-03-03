<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Form\Type;

use Adeliom\SyliusEasyCrudPlugin\Form\IconType;
use Adeliom\SyliusHappyCMSPlugin\Entity\Embeddable\ButtonEmbeddable;
use Adeliom\SyliusHappyCMSPlugin\Entity\Embeddable\ButtonEmbeddableInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ButtonEmbeddableType extends AbstractType implements FormTypeInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->addLabelField($options, $builder);
        $this->addLinkField($options, $builder);
        $this->addActionField($options, $builder);
        $this->addIconField($options, $builder);

        $builder->addModelTransformer(
            new CallbackTransformer(
                function (?array $data) {
                    return ButtonEmbeddable::new($data ?? []);
                },
                function (?ButtonEmbeddableInterface $buttonEmbeddable) {
                    return $buttonEmbeddable ? $buttonEmbeddable->toArray() : [];
                },
            ),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'fields' => ['label', 'link', 'action', 'icon'],
            'requiredFields' => [],
            'removeTransformer' => false,
            'toJson' => false,
            'data_class' => ButtonEmbeddable::class,
        ]);
    }

    private function addLabelField(array $options, FormBuilderInterface $builder): void
    {
        if (\in_array('label', $options['fields'], true)) {
            $attrs = [
                'label' => 'sylius_happy_cms.button_embeddable.label',
                'constraints' => [],
            ];

            if (isset($options['data']['label'])) {
                $attrs['data'] = $options['data']['label'];
            }

            if (in_array('label', $options['requiredFields'], true)) {
                $attrs['constraints'][] = new NotBlank();
            }

            $builder->add('label', TextType::class, $attrs);
        } else {
            $builder->add('label', HiddenType::class, ['required' => false]);
        }
    }

    private function addLinkField(array $options, FormBuilderInterface $builder): void
    {
        if (\in_array('link', $options['fields'], true)) {
            $attrs = [
                'label' => 'sylius_happy_cms.button_embeddable.link',
                'constraints' => [],
            ];

            if (isset($options['data']['link'])) {
                $attrs['data'] = $options['data']['link'];
            }

            if (in_array('link', $options['requiredFields'], true)) {
                $attrs['constraints'][] = new NotBlank();
            }

            $builder->add('link', TextType::class, $attrs);
        } else {
            $builder->add('link', HiddenType::class, ['required' => false]);
        }
    }

    private function addActionField(array $options, FormBuilderInterface $builder): void
    {
        if (\in_array('action', $options['fields'], true)) {
            $attrs = [
                'label' => 'sylius_happy_cms.button_embeddable.action',
                'required' => false,
                'choices' => [
                    'sylius_happy_cms.button_embeddable.action_default' => 'default',
                    'sylius_happy_cms.button_embeddable.action_blank' => 'blank',
                    'sylius_happy_cms.button_embeddable.action_none' => 'none',
                ],
            ];

            if (isset($options['data']['action'])) {
                $attrs['data'] = $options['data']['action'];
            }

            if (in_array('action', $options['requiredFields'], true)) {
                $attrs['constraints'][] = new NotBlank();
            }

            $builder->add('action', ChoiceType::class, $attrs);
        } else {
            $builder->add('action', HiddenType::class, ['required' => false]);
        }
    }

    private function addIconField(array $options, FormBuilderInterface $builder): void
    {
        if (\in_array('icon', $options['fields'], true)) {
            $attrs = [
                'label' => 'sylius_happy_cms.button_embeddable.icon',
                'required' => false,
                'select_button' => 'Choisir une icône',
                'search_placeholder' => 'Rechercher une icône',
                'cancel_button' => 'Annuler',
                'no_result_found' => 'Aucun résultat trouvé',
                'show_all_button' => 'Tout afficher',
                'json_url' => '/static/iconpicker/pro.json',
            ];

            if (in_array('icon', $options['requiredFields'], true)) {
                $attrs['constraints'][] = new NotBlank();
            }

            $builder->add('icon', IconType::class, $attrs);
        } else {
            $builder->add('icon', HiddenType::class, ['required' => false]);
        }
    }
}
