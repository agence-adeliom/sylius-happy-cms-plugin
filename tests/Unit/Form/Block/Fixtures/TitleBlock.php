<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Form\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class TitleBlock extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, ['required' => false]);
    }

    public function getName(): string
    {
        return 'Title';
    }

    public function getIcon(): string
    {
        return '';
    }

    public function getFrontEndTemplatePath(): string
    {
        return 'blocks/title.html.twig';
    }
}
