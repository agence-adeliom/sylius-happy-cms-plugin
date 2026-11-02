<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaReferenceType;
use Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaType;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use PHPUnit\Framework\TestCase;

/**
 * Media entities and an entity with media columns on a real Doctrine ORM (SQLite in memory).
 */
abstract class MediaReferenceTestCase extends TestCase
{
    protected EntityManagerInterface $entityManager;

    protected QueryLogger $queries;

    protected function setUp(): void
    {
        if (!\extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }

        foreach ([MediaType::TYPE => MediaType::class, MediaReferenceType::TYPE => MediaReferenceType::class] as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }

        $config = ORMSetup::createAttributeMetadataConfig([], true);
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }
        $this->queries = new QueryLogger();
        $config->setMiddlewares([new LoggingMiddleware($this->queries)]);

        $driver = new MappingDriverChain();
        $attributes = new AttributeDriver([__DIR__, dirname(__DIR__, 4) . '/src/Entity/Media']);
        $driver->addDriver($attributes, __NAMESPACE__);
        $driver->addDriver($attributes, 'Adeliom\SyliusHappyCMSPlugin\Entity\Media');
        $config->setMetadataDriverImpl($driver);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $resolveTargetEntities = new ResolveTargetEntityListener();
        $resolveTargetEntities->addResolveTargetEntity(MediaInterface::class, ReferencedMedia::class, []);
        $resolveTargetEntities->addResolveTargetEntity(FolderInterface::class, ReferencedFolder::class, []);
        $this->entityManager->getEventManager()->addEventSubscriber($resolveTargetEntities);

        (new SchemaTool($this->entityManager))->createSchema(array_map(
            fn (string $class) => $this->entityManager->getClassMetadata($class),
            [ReferencedFolder::class, ReferencedMedia::class, Article::class],
        ));

        MediaReferenceType::setReferenceFactory(fn (int $id): MediaInterface => $this->entityManager->getReference(ReferencedMedia::class, $id));
    }

    protected function tearDown(): void
    {
        MediaReferenceType::setReferenceFactory(null);
    }

    protected function saveMedia(string $name): ReferencedMedia
    {
        $media = new ReferencedMedia();
        $media->setName($name);
        $media->setMime('image/jpeg');
        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    /**
     * Saves the article, clears the unit of work and reloads it, as a new request would.
     */
    protected function saveAndReload(Article $article): Article
    {
        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->queries->queries = [];

        $reloaded = $this->entityManager->find(Article::class, $article->id);
        self::assertInstanceOf(Article::class, $reloaded);

        return $reloaded;
    }
}
