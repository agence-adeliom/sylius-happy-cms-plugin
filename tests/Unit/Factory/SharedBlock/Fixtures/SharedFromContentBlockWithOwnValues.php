<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockAttributeMetadataTrait;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\TitleContentBlock;

#[AsSharedBlock(name: 'Shared title', icon: 'ti ti-share', position: 3, block: TitleContentBlock::class)]
final class SharedFromContentBlockWithOwnValues extends AbstractSharedBlockType
{
    use SharedBlockAttributeMetadataTrait;
}
