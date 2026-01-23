<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Form\MediaType;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;
use Adeliom\SyliusHappyCMSPlugin\Form\Type\ButtonEmbeddableType;
use Adeliom\SyliusHappyCMSPlugin\Form\Type\CheckboxJsonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

#[AIGeneratable(
    description: 'A combined text and image block with headline, title, rich content, image and call-to-action buttons',
    useCases: ['about sections', 'service presentations', 'product highlights', 'team introductions', 'company values'],
    priority: 135,
)]
class TextImageCtaBlockType extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', MediaType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.text_image.fields.image',
            ])
            ->add('img_right', CheckboxJsonType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.text_image.fields.img_right',
            ])
            ->add('headline', TextType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.text_image.fields.headline',
            ])
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.text_image.fields.title',
            ])
            ->add('wysiwyg', TinymceBridgeType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.text_image.fields.wysiwyg',
            ])
            ->add('cta_one', ButtonEmbeddableType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.text_image.fields.cta_one',
                'fields' => ['label', 'link'],
            ])
            ->add('cta_two', ButtonEmbeddableType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.text_image.fields.cta_two',
                'fields' => ['label', 'link'],
            ]);
    }

    public function getName(): string
    {
        return 'sylius_happy_cms.blocks.text_image.name';
    }

    public function getTab(): string
    {
        return 'sylius_happy_cms.blocks.tabs.media_blocks';
    }

    public function getIcon(): string
    {
        return '<img class="card-img-top" src="' . $this->getPackages()->getUrl('dist/placeholder-image.webp', AssetHappyCMSPackage::PACKAGE_NAME) . '" alt="">';
    }

    public function getFrontEndTemplatePath(): string
    {
        return '@SyliusHappyCMSPlugin/front/blocks/text_image_cta_block.html.twig';
    }
}
