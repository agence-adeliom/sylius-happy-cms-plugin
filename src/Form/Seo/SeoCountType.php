<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Form\Seo;

use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\Seo;
use Adeliom\SyliusHappyCMSPlugin\Form\MediaType;
use Adeliom\SyliusHappyCMSPlugin\Form\Seo\Fields\TextareaCounterType;
use Adeliom\SyliusHappyCMSPlugin\Form\Seo\Fields\TextCounterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeoCountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextCounterType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.title',
            ])
            ->add('cover', MediaType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.cover',
                'restrictions_uploadTypes' => ['image/*'],
            ])
            ->add('canonical', UrlType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.canonical',
            ])
            ->add('description', TextareaCounterType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.description',
            ])
            ->add('keywords', TextType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.keywords',
            ])
            ->add('key', TextType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.key',
            ])
            ->add('robots', ChoiceType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.robots',
                'multiple' => true,
                'attr' => [
                    'data-ea-widget' => 'ea-autocomplete',
                ],
                'choices' => [
                    'noindex' => 'noindex',
                    'nofollow' => 'nofollow',
                    'noarchive' => 'noarchive',
                    'nosnippet' => 'nosnippet',
                    'notranslate' => 'notranslate',
                    'noimageindex' => 'noimageindex',
                ],
            ])
            ->add('sitemap', CheckboxType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.sitemap',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'data_class' => SEO::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'happy_cms_seo';
    }
}
