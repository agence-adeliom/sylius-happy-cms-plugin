<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockAttributeMetadataTrait;

#[AsSharedBlock(name: 'Minimal', template: 'blocks/shared/minimal.html.twig')]
final class MinimalAttributeSharedBlock extends AbstractSharedBlockType
{
    use SharedBlockAttributeMetadataTrait;
}
