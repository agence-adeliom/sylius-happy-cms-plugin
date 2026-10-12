<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Entity\Media;

use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\MediaReferenceTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\ReferencedFolder;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\ReferencedMedia;

/**
 * Mapping assertion only: SQLite does not enforce VARCHAR lengths, MySQL in strict mode does.
 */
final class SlugLengthTest extends MediaReferenceTestCase
{
    public function testMediaAndFolderSlugsAreAsLongAsTheNamesTheyAreDerivedFrom(): void
    {
        foreach ([ReferencedMedia::class, ReferencedFolder::class] as $class) {
            $metadata = $this->entityManager->getClassMetadata($class);

            self::assertSame(255, $metadata->getFieldMapping('name')->length, $class);
            self::assertSame(255, $metadata->getFieldMapping('slug')->length, $class);
        }
    }
}
