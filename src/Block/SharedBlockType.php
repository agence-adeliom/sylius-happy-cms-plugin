<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Form\ResourceChoiceType;
use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class SharedBlockType extends AbstractBlock implements ServiceSubscriberInterface, BlockTypeInterface
{
    public static function getSubscribedServices(): array
    {
        return [
            ParameterBagInterface::class,
        ];
    }

    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        global $allowedSharedBlockTypesForResource;
        $builder
            ->add('block', ResourceChoiceType::class, [
                'class' => SharedBlock::class,
                'resource' => 'sylius_happy_cms.shared_block',
                'persist_into_an_array' => true,
                'repositoryMethod' => 'findByTypes',
                'repositoryArguments' => ['types' => $allowedSharedBlockTypesForResource ?? []],
            ]);
    }

    public function getTab(): string
    {
        return 'sylius_happy_cms.blocks.tabs.shared_blocks';
    }

    public function getName(): string
    {
        return 'Shared block';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getIcon(): string
    {
        return '<img class="card-img-top" src="' . $this->getPackages()->getUrl('dist/placeholder-image.webp', AssetHappyCMSPackage::PACKAGE_NAME) . '" alt="">';
    }

    /**
     * Declare here the assets that make back-office working as expected
     *
     * @return array{js?: array<string|Asset>|null, css?: array<string|Asset>|null, webpack?: array<string|Asset>|null}
     */
    public function configureAdminAssets(): array
    {
        return [];
    }

    public function getFrontEndTemplatePath(): string
    {
        return '@SyliusHappyCMSPlugin/front/blocks/shared_block.html.twig';
    }
}
