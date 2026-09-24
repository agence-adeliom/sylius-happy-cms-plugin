<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\SharedBlock;

use Adeliom\SyliusEasyCrudPlugin\Form\IconType;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Form\MediaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ExampleType extends AbstractSharedBlockType implements SharedBlockTypeInterface
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [])
        ;

        $builder->add('image', MediaType::class, [
            'label' => 'Media',
        ]);

        $builder->add('icon', IconType::class, [
            'label' => 'Icon',
        ]);
    }

    public function getName(): string
    {
        return 'Example block';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getIcon(): string | array
    {
        return '';
    }

    public function getFrontEndTemplatePath(): string
    {
        return '@SyliusHappyCMSPlugin/front/shared_blocks/example.html.twig';
    }

    public function configureAdminAssets(): array
    {
        /** @var array<string, array<string>> $assets */
        $assets = array_merge_recursive(
            MediaType::configureAdminAssets(),
            IconType::configureAdminAssets(),
        );

        return $assets;
    }

    public function configureAdminFormThemes(): array
    {
        /** @var array<string> $formThemes */
        $formThemes = array_merge_recursive(
            MediaType::configureAdminFormThemes(),
            IconType::configureAdminFormThemes(),
        );

        return $formThemes;
    }
}
