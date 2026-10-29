<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Media\LongSlugsListener;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use PHPUnit\Framework\TestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Media\Fixtures\OverriddenSlugMedia;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Media\Fixtures\SlugFolder;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Media\Fixtures\SlugMedia;

/**
 * Mapping and schema assertions on a real Doctrine ORM: SQLite does not enforce VARCHAR lengths, MySQL in strict
 * mode does.
 */
final class LongSlugsListenerTest extends TestCase
{
    public function testTheSlugsStayAt100CharactersByDefault(): void
    {
        $entityManager = $this->entityManager(false);

        foreach ([SlugMedia::class, SlugFolder::class] as $class) {
            self::assertSame(100, $entityManager->getClassMetadata($class)->getFieldMapping('slug')->length, $class);
            self::assertSame(255, $entityManager->getClassMetadata($class)->getFieldMapping('name')->length, $class);
        }
        self::assertSame(100, $this->slugColumnLength($entityManager, SlugMedia::class));
        self::assertSame(100, $this->slugColumnLength($entityManager, SlugFolder::class));
    }

    public function testTheListenerWidensTheMediaAndFolderSlugsTo255Characters(): void
    {
        $entityManager = $this->entityManager(true);

        foreach ([SlugMedia::class, SlugFolder::class] as $class) {
            self::assertSame(255, $entityManager->getClassMetadata($class)->getFieldMapping('slug')->length, $class);
        }
        self::assertSame(255, $this->slugColumnLength($entityManager, SlugMedia::class));
        self::assertSame(255, $this->slugColumnLength($entityManager, SlugFolder::class));
    }

    public function testTheListenerKeepsASlugLengthOverriddenByTheProject(): void
    {
        $entityManager = $this->entityManager(true);

        self::assertSame(150, $entityManager->getClassMetadata(OverriddenSlugMedia::class)->getFieldMapping('slug')->length);
    }

    private function entityManager(bool $longSlugs): EntityManagerInterface
    {
        if (!\extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }

        $config = ORMSetup::createAttributeMetadataConfig([], true);
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }

        $driver = new MappingDriverChain();
        $attributes = new AttributeDriver([__DIR__ . '/Fixtures', dirname(__DIR__, 4) . '/src/Entity/Media']);
        $driver->addDriver($attributes, __NAMESPACE__ . '\Fixtures');
        $driver->addDriver($attributes, 'Adeliom\SyliusHappyCMSPlugin\Entity\Media');
        $config->setMetadataDriverImpl($driver);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);

        $resolveTargetEntities = new ResolveTargetEntityListener();
        $resolveTargetEntities->addResolveTargetEntity(MediaInterface::class, SlugMedia::class, []);
        $resolveTargetEntities->addResolveTargetEntity(FolderInterface::class, SlugFolder::class, []);
        $entityManager->getEventManager()->addEventSubscriber($resolveTargetEntities);

        if ($longSlugs) {
            $entityManager->getEventManager()->addEventListener(Events::loadClassMetadata, new LongSlugsListener());
        }

        return $entityManager;
    }

    /**
     * @param class-string $class
     */
    private function slugColumnLength(EntityManagerInterface $entityManager, string $class): ?int
    {
        $metadata = $entityManager->getClassMetadata($class);
        $schema = (new SchemaTool($entityManager))->getSchemaFromMetadata([$metadata]);

        return $schema->getTable($metadata->getTableName())->getColumn('slug')->getLength();
    }
}
