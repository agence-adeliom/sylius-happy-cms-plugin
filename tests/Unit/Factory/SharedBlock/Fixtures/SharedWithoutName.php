<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockAttributeMetadataTrait;

#[AsSharedBlock(template: 'blocks/shared/without_name.html.twig')]
final class SharedWithoutName extends AbstractSharedBlockType
{
    use SharedBlockAttributeMetadataTrait;
}
