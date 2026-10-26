<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockAttributeMetadataTrait;

#[AsSharedBlock(name: 'Newsletter', icon: 'ti ti-mail', template: 'blocks/shared/newsletter.html.twig', position: 5, description: 'Newsletter subscription form')]
final class AttributeSharedBlock extends AbstractSharedBlockType
{
    use SharedBlockAttributeMetadataTrait;
}
