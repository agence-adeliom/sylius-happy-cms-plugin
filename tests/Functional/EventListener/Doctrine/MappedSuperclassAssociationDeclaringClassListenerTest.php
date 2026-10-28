<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\EventListener\Doctrine;

use Adeliom\SyliusHappyCMSPlugin\EventListener\Doctrine\MappedSuperclassAssociationDeclaringClassListener;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * The test application entities extend the plugin models without inheritance below them, like most projects:
 * their metadata and their schema are the same with and without the listener.
 */
final class MappedSuperclassAssociationDeclaringClassListenerTest extends KernelTestCase
{
    public function testTheMetadataOfTheTestApplicationEntitiesIsTheSameWithAndWithoutTheListener(): void
    {
        self::bootKernel();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $withListener = $this->createEntityManager($entityManager, true);
        $withoutListener = $this->createEntityManager($entityManager, false);

        $classes = $entityManager->getConfiguration()->getMetadataDriverImpl()?->getAllClassNames() ?? [];
        $classes = array_values(array_filter(
            $classes,
            static fn (string $class): bool => str_starts_with($class, 'Tests\\Adeliom\\SyliusHappyCMSPlugin\\Entity\\'),
        ));
        self::assertNotEmpty($classes);

        $metadataWith = [];
        $metadataWithout = [];
        foreach ($classes as $class) {
            $metadataWith[] = $with = $withListener->getClassMetadata($class);
            $metadataWithout[] = $without = $withoutListener->getClassMetadata($class);

            self::assertSame(serialize($without), serialize($with), $class);
        }

        self::assertSame(
            (new SchemaTool($withoutListener))->getCreateSchemaSql($metadataWithout),
            (new SchemaTool($withListener))->getCreateSchemaSql($metadataWith),
        );
    }

    /**
     * An entity manager with the listeners of the application one (Sylius subscribers, resolve_target_entities…),
     * with or without the plugin listener, and an empty metadata cache.
     */
    private function createEntityManager(EntityManagerInterface $entityManager, bool $withListener): EntityManager
    {
        $eventManager = new EventManager();
        $found = false;
        foreach ($entityManager->getEventManager()->getAllListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof MappedSuperclassAssociationDeclaringClassListener) {
                    $found = true;
                    if (!$withListener) {
                        continue;
                    }
                }
                $eventManager->addEventListener($event, $listener);
            }
        }
        self::assertTrue($found, 'The listener is registered in the application entity manager.');

        $configuration = clone $entityManager->getConfiguration();
        $configuration->setMetadataCache(new ArrayAdapter());

        return new EntityManager($entityManager->getConnection(), $configuration, $eventManager);
    }
}
