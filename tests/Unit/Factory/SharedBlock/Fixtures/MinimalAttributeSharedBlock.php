<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;

#[AsSharedBlock(name: 'Minimal', template: 'blocks/shared/minimal.html.twig')]
final class MinimalAttributeSharedBlock extends AbstractSharedBlockType
{
}
