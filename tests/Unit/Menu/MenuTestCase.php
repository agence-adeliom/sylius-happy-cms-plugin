<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo\Mapping\Driver\AttributeReader;
use Gedmo\Tree\TreeListener;
use PHPUnit\Framework\TestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\MenuItem;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\MenuItemTranslation;

/**
 * Menus of the test application against a real unit of work (SQLite in memory, foreign keys enforced, Gedmo tree).
 */
abstract class MenuTestCase extends TestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        if (!\extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }

        $root = dirname(__DIR__, 3);
        $config = ORMSetup::createAttributeMetadataConfig([], true);
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }
        $config->setMetadataDriverImpl(new AttributeDriver([
            $root . '/src/Entity/Menu',
            $root . '/tests/TestApplication/src/Entity/HappyCMS/Menu',
        ]));

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $this->entityManager = new EntityManager($connection, $config);

        $eventManager = $this->entityManager->getEventManager();

        $resolveTargetEntities = new ResolveTargetEntityListener();
        $resolveTargetEntities->addResolveTargetEntity(MenuInterface::class, Menu::class, []);
        $resolveTargetEntities->addResolveTargetEntity(MenuItemInterface::class, MenuItem::class, []);
        $eventManager->addEventSubscriber($resolveTargetEntities);

        // What Sylius' ORMTranslatableListener maps for a translatable resource.
        $eventManager->addEventListener([Events::loadClassMetadata], new class() {
            public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
            {
                $metadata = $args->getClassMetadata();
                if (MenuItem::class === $metadata->getName()) {
                    $metadata->mapOneToMany([
                        'fieldName' => 'translations',
                        'targetEntity' => MenuItemTranslation::class,
                        'mappedBy' => 'translatable',
                        'fetch' => $metadata::FETCH_EXTRA_LAZY,
                        'indexBy' => 'locale',
                        'cascade' => ['persist', 'remove'],
                        'orphanRemoval' => true,
                    ]);
                }
                if (MenuItemTranslation::class === $metadata->getName()) {
                    $metadata->mapManyToOne([
                        'fieldName' => 'translatable',
                        'targetEntity' => MenuItem::class,
                        'inversedBy' => 'translations',
                        'joinColumns' => [['name' => 'translatable_id', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE', 'nullable' => false]],
                    ]);
                    $metadata->mapField(['fieldName' => 'locale', 'type' => 'string', 'nullable' => false]);
                }
            }
        });

        $tree = new TreeListener();
        $tree->setAnnotationReader(new AttributeReader());
        $eventManager->addEventSubscriber($tree);

        $this->configureEntityManager($this->entityManager);

        (new SchemaTool($this->entityManager))->createSchema(array_map(
            fn (string $class) => $this->entityManager->getClassMetadata($class),
            [Menu::class, MenuItem::class, MenuItemTranslation::class],
        ));
    }

    /**
     * Hook for entity listeners, called before the schema (and so the metadata) is built.
     */
    protected function configureEntityManager(EntityManagerInterface $entityManager): void
    {
    }

    protected function newMenu(string $code): Menu
    {
        $menu = new Menu();
        $menu->setCode($code);
        $menu->setName($code);
        $menu->setStatus(true);

        return $menu;
    }

    protected function newItem(Menu $menu, string $name, ?MenuItem $parent = null): MenuItem
    {
        $item = new MenuItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->getTranslation()->setName($name);
        $item->setPublishState('published');
        $menu->addItem($item);
        if (null !== $parent) {
            $item->setParent($parent);
        }

        return $item;
    }

    protected function rowCount(string $table): int
    {
        return (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM ' . $table);
    }
}
