<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media;

use Adeliom\SyliusHappyCMSPlugin\Repository\Media\MediaRepositoryInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\ManagedMedia;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;

/**
 * A media entity on the plugin MediaRepository: the deprecated getMediaRepository() and getEntityRepository()
 * return the same repository.
 */
final class MediaRepositoryTest extends MediaManagerTestCase
{
    public function testBothMethodsReturnThePluginRepository(): void
    {
        $repository = @$this->manager->getHelper()->getMediaRepository();

        self::assertInstanceOf(MediaRepositoryInterface::class, $repository);
        self::assertSame($repository, $this->manager->getHelper()->getEntityRepository());
        self::assertSame(ManagedMedia::class, $this->manager->getHelper()->getEntityRepository()?->getClassName());
    }
}
