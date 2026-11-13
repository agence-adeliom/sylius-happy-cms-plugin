<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\LegacyAbstractBaseBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\LegacyAbstractBaseSharedBlock;

/**
 * In 2.x, AbstractBlock / AbstractSharedBlockType stay as in 2.3.x: the attributes are read by opt-in traits. A project
 * base class redeclaring the metadata methods abstract still loads (it would be a fatal error if the abstract classes
 * implemented them).
 */
final class BackwardCompatibilityTest extends TestCase
{
    public function testAProjectBaseBlockRedeclaringTheMetadataMethodsAbstractStillLoads(): void
    {
        self::assertTrue(class_exists(LegacyAbstractBaseBlock::class));
        self::assertTrue(class_exists(LegacyAbstractBaseSharedBlock::class));
    }
}
