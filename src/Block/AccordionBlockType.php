<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\Block\SubType\AccordionItemEmbeddableType;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;
use Adeliom\SyliusHappyCMSPlugin\Form\Type\ButtonEmbeddableType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AccordionBlockType extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.accordion.fields.title',
            ])
            ->add('wysiwyg', TinymceBridgeType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.accordion.fields.wysiwyg',
            ])
            ->add('items', SortableCollectionType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.accordion.fields.items',
                'entry_type' => AccordionItemEmbeddableType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'allow_drag' => true,
            ])
            ->add('cta', ButtonEmbeddableType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.accordion.fields.cta',
                'fields' => ['label', 'link'],
            ]);
    }

    public function getName(): string
    {
        return 'sylius_happy_cms.blocks.accordion.name';
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
        return '@SyliusHappyCMSPlugin/front/blocks/accordion_block.html.twig';
    }

    /**
     * @return array{js?: array<string|Asset>|null, css?: array<string|Asset>|null, webpack?: array<string|Asset>|null}
     */
    public function configureAssets(): array
    {
        return [
            'js' => [
                (Asset::new('accordion-block-type.js'))->package(AssetHappyCMSPackage::PACKAGE_NAME),
            ],
        ];
    }
}
