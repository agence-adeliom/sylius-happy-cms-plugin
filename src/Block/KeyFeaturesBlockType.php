<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Form\IconType;
use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;
use Adeliom\SyliusHappyCMSPlugin\Block\SubType\KeyFeatureEmbeddableType;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

#[AIGeneratable(
    description: 'A features showcase block with icons, titles and descriptions for highlighting key product or service features',
    useCases: ['product features', 'service benefits', 'value propositions', 'why choose us sections', 'feature comparisons'],
    priority: 130,
)]
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

    /**
     * @return array<string, array<int,string|Asset>>
     */
    public function configureAdminAssets(): array
    {
        // Sub formType asset has to be declared manually
        return array_merge_recursive(parent::configureAdminAssets(), IconType::configureAdminAssets());
    }

    /**
     * @return array<string, array<int,string|Asset>>
     */
    public function configureAdminFormThemes(): array
    {
        // Sub formType asset has to be declared manually
        return array_merge(IconType::configureAdminFormThemes(), parent::configureAdminFormThemes());
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
        return '<img class="card-img-top" src="' . $this->getPackages()->getUrl('dist/placeholder-image.webp', AssetHappyCMSPackage::PACKAGE_NAME) . '" alt="">';
    }

    public function getFrontEndTemplatePath(): string
    {
        return '@SyliusHappyCMSPlugin/front/blocks/key_features_block.html.twig';
    }
}
