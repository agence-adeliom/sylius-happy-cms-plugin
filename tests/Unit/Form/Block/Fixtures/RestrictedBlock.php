<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Form\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Text block that refuses every content-editable resource.
 */
final class RestrictedBlock extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', TextareaType::class, ['required' => false]);
    }

    public function getName(): string
    {
        return 'Restricted text';
    }

    public function getIcon(): string
    {
        return '';
    }

    public function getFrontEndTemplatePath(): string
    {
        return 'blocks/text.html.twig';
    }

    public function supports(?ContentEditableInterface $resource = null): bool
    {
        return null === $resource;
    }
}
