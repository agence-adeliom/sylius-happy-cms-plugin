<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Form\MediaType;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

#[AIGeneratable(
    description: 'An image gallery block with title, description and multiple images displayed in a grid layout',
    useCases: ['portfolios', 'photo galleries', 'product showcases', 'before/after comparisons', 'team photos'],
    priority: 110,
)]
class GalleryBlockType extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.gallery.fields.title',
            ])
            ->add('wysiwyg', TinymceBridgeType::class, [
                'required' => true,
                'label' => 'sylius_happy_cms.blocks.gallery.fields.wysiwyg',
            ])
            ->add('images', SortableCollectionType::class, [
                'required' => false,
                'label' => 'sylius_happy_cms.blocks.gallery.fields.images',
                'entry_type' => MediaType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'allow_drag' => true,
            ]);
    }

    /**
     * @return array<string, array<int,string|Asset>>
     */
    public function configureAdminAssets(): array
    {
        // Sub formType asset has to be declared manually
        return array_merge_recursive(parent::configureAdminAssets(), MediaType::configureAdminAssets());
    }

    /**
     * @return array<string, array<int,string|Asset>>
     */
    public function configureAdminFormThemes(): array
    {
        // Sub formType asset has to be declared manually
        return array_merge(MediaType::configureAdminFormThemes(), parent::configureAdminFormThemes());
    }

    public function getName(): string
    {
        return 'sylius_happy_cms.blocks.gallery.name';
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
        return '@SyliusHappyCMSPlugin/front/blocks/gallery_block.html.twig';
    }
}
