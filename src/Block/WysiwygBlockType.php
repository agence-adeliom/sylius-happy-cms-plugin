<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;
use Symfony\Component\Form\FormBuilderInterface;

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
        return '<img class="card-img-top" src="/static/admin/blocks/WysiwygBlockType.jpg" alt="">';
    }

    public function getFrontEndTemplatePath(): string
    {
        return '@SyliusHappyCMSPlugin/front/blocks/wysiwyg_block.html.twig';
    }
}
