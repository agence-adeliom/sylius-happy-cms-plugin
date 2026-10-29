<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\FileValidator;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaHelper;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\UrlValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Yaml\Yaml;

/**
 * A real MediaManager on a real Doctrine ORM (SQLite in memory) and a Flysystem in-memory storage,
 * configured through the plugin extension like in an application.
 */
abstract class MediaManagerTestCase extends TestCase
{
    protected EntityManagerInterface $entityManager;

    protected FilesystemOperator $storage;

    protected MediaManager $manager;

    protected QueryLogger $queries;

    protected string $projectDir;

    /** @var array<string, mixed> */
    protected array $parameters = [];

    /**
     * @return class-string<MediaInterface>
     */
    protected function mediaClass(): string
    {
        return ManagedMedia::class;
    }

    /**
     * The `sylius_happy_cms.media` configuration of the application under test.
     *
     * @return array<string, mixed>
     */
    protected function mediaConfig(): array
    {
        return [];
    }

    protected function setUp(): void
    {
        if (!\extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }

        $config = ORMSetup::createAttributeMetadataConfig([], true);
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }
        $this->queries = new QueryLogger();
        $config->setMiddlewares([new LoggingMiddleware($this->queries)]);

        $driver = new MappingDriverChain();
        $attributes = new AttributeDriver([__DIR__, dirname(__DIR__, 5) . '/src/Entity/Media']);
        $driver->addDriver($attributes, __NAMESPACE__);
        $driver->addDriver($attributes, 'Adeliom\SyliusHappyCMSPlugin\Entity\Media');
        $config->setMetadataDriverImpl($driver);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $resolveTargetEntities = new ResolveTargetEntityListener();
        $resolveTargetEntities->addResolveTargetEntity(MediaInterface::class, $this->mediaClass(), []);
        $resolveTargetEntities->addResolveTargetEntity(FolderInterface::class, ManagedFolder::class, []);
        $this->entityManager->getEventManager()->addEventSubscriber($resolveTargetEntities);

        (new SchemaTool($this->entityManager))->createSchema(array_map(
            fn (string $class) => $this->entityManager->getClassMetadata($class),
            [ManagedFolder::class, $this->mediaClass()],
        ));

        $this->projectDir = sys_get_temp_dir() . '/happy-cms-media-' . bin2hex(random_bytes(4));
        $this->storage = new Filesystem(new InMemoryFilesystemAdapter());
        $this->manager = $this->createManager($this->mediaConfig());
    }

    /**
     * @param array<string, mixed> $mediaConfig
     */
    protected function createManager(array $mediaConfig): MediaManager
    {
        $pluginConfig = Yaml::parseFile(dirname(__DIR__, 5) . '/config/packages/sylius_happy_cms.yaml');
        self::assertIsArray($pluginConfig);

        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', false);
        (new SyliusHappyCMSExtension())->load([$pluginConfig['sylius_happy_cms'], ['media' => array_merge([
            'media_entity' => $this->mediaClass(),
            'folder_entity' => ManagedFolder::class,
        ], $mediaConfig)]], $builder);

        $this->parameters = ['kernel.project_dir' => $this->projectDir];
        foreach ($builder->getParameterBag()->all() as $name => $value) {
            if (str_starts_with($name, 'sylius_happy_cms.media.')) {
                $this->parameters[$name] = $value;
            }
        }

        $parameters = new ContainerBag(new Container(new ParameterBag($this->parameters)));
        $helper = new MediaHelper($parameters, $this->entityManager, $this->createStub(RouterInterface::class));

        return new MediaManager(
            $this->storage,
            $helper,
            $this->entityManager,
            $parameters,
            new IdentityTranslator(),
            new EventDispatcher(),
            new FileValidator(),
            new UrlValidator(),
        );
    }

    protected function saveFolder(string $name, ?ManagedFolder $parent = null): ManagedFolder
    {
        $folder = new ManagedFolder();
        $folder->setName($name);
        $folder->setParent($parent);
        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        return $folder;
    }

    protected function saveMedia(string $slug, ?ManagedFolder $folder = null, string $mime = 'image/jpeg'): MediaInterface
    {
        $class = $this->mediaClass();
        $media = new $class();
        $media->setName(pathinfo($slug, \PATHINFO_FILENAME));
        $media->setSlug($slug);
        $media->setMime($mime);
        $media->setFolder($folder);
        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    /**
     * A PNG of 1x1 pixel written to a temporary file.
     */
    protected function pngFile(string $basename = 'image.png'): string
    {
        $dir = $this->projectDir . '/sources';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = $dir . '/' . $basename;
        file_put_contents($path, (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true));

        return $path;
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->projectDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->projectDir);
        }
    }

    /**
     * @return list<string>
     */
    protected function folderPaths(): array
    {
        $paths = array_map(
            static fn (ManagedFolder $folder): string => $folder->getPath(),
            $this->entityManager->getRepository(ManagedFolder::class)->findAll(),
        );
        sort($paths);

        return $paths;
    }
}
