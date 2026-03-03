<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;
use Symfony\Component\Form\FormBuilderInterface;

#[AIGeneratable(
    description: 'A simple rich text content block with WYSIWYG editor',
    useCases: ['articles', 'blog posts', 'text content', 'descriptions', 'informational pages'],
    priority: 200,
)]
class WysiwygBlockType extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TinymceBridgeType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.wysiwyg.fields.wysiwyg',
            ]);
    }

    public function getName(): string
    {
        return 'sylius_happy_cms.blocks.wysiwyg.name';
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
        return '@SyliusHappyCMSPlugin/front/blocks/wysiwyg_block.html.twig';
    }
}
