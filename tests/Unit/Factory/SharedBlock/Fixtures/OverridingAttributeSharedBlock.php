<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockAttributeMetadataTrait;

#[AsSharedBlock(name: 'Overriding', icon: 'ti ti-mail', template: 'blocks/shared/overriding.html.twig', description: 'Static description')]
final class OverridingAttributeSharedBlock extends AbstractSharedBlockType
{
    use SharedBlockAttributeMetadataTrait;

    public function getDescription(): string
    {
        return 'Dynamic description';
    }
}
