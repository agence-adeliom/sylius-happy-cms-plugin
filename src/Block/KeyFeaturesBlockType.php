<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;
use Adeliom\SyliusHappyCMSPlugin\Block\SubType\KeyFeatureEmbeddableType;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class KeyFeaturesBlockType extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('headline', TextType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.key_features.fields.headline',
            ])
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.key_features.fields.title',
            ])
            ->add('wysiwyg', TinymceBridgeType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.key_features.fields.wysiwyg',
            ])
            ->add('features', SortableCollectionType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.key_features.fields.features',
                'entry_type' => KeyFeatureEmbeddableType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'allow_drag' => true,
            ]);
    }

    public function getName(): string
    {
        return 'sylius_happy_cms.blocks.key_features.name';
    }

    public function getTab(): string
    {
        return 'sylius_happy_cms.blocks.tabs.text_blocks';
    }

    public function getIcon(): string
    {
        return '<img class="card-img-top" src="/static/admin/blocks/KeyFeaturesBlockType.jpg" alt="">';
    }

    public function getFrontEndTemplatePath(): string
    {
        return '@SyliusHappyCMSPlugin/front/blocks/key_features_block.html.twig';
    }
}
