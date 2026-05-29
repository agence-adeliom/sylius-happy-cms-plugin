<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Form\Seo;

use Adeliom\SyliusEasyCrudPlugin\Form\AdminFormTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\Seo;
use Adeliom\SyliusHappyCMSPlugin\Form\MediaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class SeoType extends AbstractType implements AdminFormTypeInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.title',
            ])
            ->add('cover', MediaType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.cover',
                'restrictions_uploadTypes' => ['image/*'],
            ])
            ->add('canonical', UrlType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.canonical',
                'constraints' => [
//                    new NotBlank(),
//                    new Url(),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.description',
            ])
            ->add('keywords', TextType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.keywords',
            ])
            // Todo : remove and put key into cms routing behavior
            //->add('key', TextType::class, [
            //    'label' => 'sylius_happy_cms.seo.admin.field.key',
            //])
            ->add('sitemap', CheckboxType::class, [
                'label' => 'sylius_happy_cms.seo.admin.field.sitemap',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'data_class' => Seo::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'happy_cms_seo';
    }

    public static function configureAdminAssets(): array
    {
        return MediaType::configureAdminAssets();
    }

    public static function configureAdminFormThemes(): array
    {
        return array_merge(MediaType::configureAdminFormThemes(), ['@SyliusHappyCMSPlugin/field/seo/form.html.twig']);
    }
}
