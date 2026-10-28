<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;

/**
 * Minimal flex block rendering the given template.
 */
abstract class TemplateBlock implements BlockTypeInterface
{
    abstract public function getFrontEndTemplatePath(): string;

    public function getName(): string
    {
        return static::class;
    }

    public function getIcon(): string
    {
        return '';
    }

    public function getPosition(): int
    {
        return 0;
    }

    public function configureAssets(): array
    {
        return ['js' => [], 'css' => [], 'webpack' => []];
    }

    public function configureAdminAssets(): array
    {
        return [];
    }

    public function configureAdminFormThemes(): array
    {
        return [];
    }

    public static function researchableProperties(): array
    {
        return [];
    }

    public function supports(?ContentEditableInterface $resource = null): bool
    {
        return true;
    }

    public function getTab(): string
    {
        return 'default';
    }
}
