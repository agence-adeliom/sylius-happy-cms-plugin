<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\TitleContentBlock;

#[AsSharedBlock(block: TitleContentBlock::class, description: 'Taken from a content block')]
final class SharedFromContentBlock extends AbstractSharedBlockType
{
}
