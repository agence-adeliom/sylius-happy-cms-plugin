<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockAttributeMetadataTrait;
use Tests\Adeliom\SyliusHappyCMSPlugin\Block\DemoBlockType;

/**
 * Demo of a shared block taking the metadata and the form of a content block (#[AsSharedBlock(block:)]).
 */
#[AsSharedBlock(block: DemoBlockType::class, description: 'The demo content block, shared')]
final class DemoFromContentSharedBlockType extends AbstractSharedBlockType
{
    use SharedBlockAttributeMetadataTrait;
}
