<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Services\Media;

use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaHelper;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MediaHelperTest extends KernelTestCase
{
    private MediaHelper $mediaHelper;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->mediaHelper = $container->get(MediaHelper::class);
    }

    public function testGetBaseUrlReturnsString(): void
    {
        $result = $this->mediaHelper->getBaseUrl();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetFolderClassNameReturnsString(): void
    {
        $result = $this->mediaHelper->getFolderClassName();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertTrue(class_exists($result), sprintf('Class "%s" should exist', $result));
    }

    public function testGetFolderRepositoryReturnsEntityRepository(): void
    {
        $result = $this->mediaHelper->getFolderRepository();

        $this->assertInstanceOf(EntityRepository::class, $result);
    }

    public function testGetBaseUrlReturnsExpectedValue(): void
    {
        $result = $this->mediaHelper->getBaseUrl();

        $this->assertSame('/media/download', $result);
    }

    public function testGetFolderClassNameReturnsExpectedValue(): void
    {
        $result = $this->mediaHelper->getFolderClassName();

        $this->assertSame('Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Media\Folder', $result);
    }
}
