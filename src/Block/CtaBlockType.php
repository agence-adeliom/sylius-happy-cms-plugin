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
    description: 'A call-to-action block with title, rich text content and a primary action button',
    useCases: ['conversions', 'sign-up forms', 'download sections', 'contact prompts'],
    priority: 140,
)]
class CtaBlockType extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.cta.fields.title',
            ])
            ->add('wysiwyg', TinymceBridgeType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.cta.fields.wysiwyg',
            ])
            ->add('cta_one', ButtonEmbeddableType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.cta.fields.cta_one',
                'fields' => ['label', 'link'],
            ])
            ->add('cta_two', ButtonEmbeddableType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.cta.fields.cta_two',
                'fields' => ['label', 'link'],
            ]);
    }

    public function getName(): string
    {
        return 'sylius_happy_cms.blocks.cta.name';
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
        return '@SyliusHappyCMSPlugin/front/blocks/cta_block.html.twig';
    }
}
