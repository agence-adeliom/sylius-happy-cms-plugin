<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;
use Adeliom\SyliusHappyCMSPlugin\Form\Type\ButtonEmbeddableType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

#[AIGeneratable(
    description: 'A text block with optional headline, title, rich content and up to two call-to-action buttons',
    useCases: ['landing pages', 'promotional sections', 'service descriptions', 'feature highlights'],
    priority: 150,
)]
class TextCtaBlockType extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('headline', TextType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.text_cta.fields.headline',
            ])
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.text_cta.fields.title',
            ])
            ->add('wysiwyg', TinymceBridgeType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.text_cta.fields.wysiwyg',
            ])
            ->add('cta_one', ButtonEmbeddableType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.text_cta.fields.cta_one',
                'fields' => ['label', 'link'],
            ])
            ->add('cta_two', ButtonEmbeddableType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.text_cta.fields.cta_two',
                'fields' => ['label', 'link'],
            ]);
    }

    public function getName(): string
    {
        return 'sylius_happy_cms.blocks.text_cta.name';
    }

    public function getTab(): string
    {
        return 'sylius_happy_cms.blocks.tabs.text_blocks';
    }

    public function getIcon(): string
    {
        return '<img class="card-img-top" src="' . $this->getPackages()->getUrl('dist/placeholder-image.webp', AssetHappyCMSPackage::PACKAGE_NAME) . '" alt="">';
    }

    public function getFrontEndTemplatePath(): string
    {
        return '@SyliusHappyCMSPlugin/front/blocks/text_cta_block.html.twig';
    }
}
