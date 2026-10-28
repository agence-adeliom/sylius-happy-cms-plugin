<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Menu;

use Adeliom\SyliusHappyCMSPlugin\EventListener\Menu\MenuCreationListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\MenuItem;

final class MenuCreationListenerTest extends MenuTestCase
{
    /** @var array<string, mixed> */
    private array $resources = [
        'sylius_happy_cms.menu_item' => ['classes' => ['model' => MenuItem::class]],
    ];

    /** `sylius_happy_cms.menu.root_item.assign` */
    private bool $assignRootItem = true;

    private bool $withManagerRegistry = true;

    public function testANewMenuGetsItsRootItem(): void
    {
        $menu = $this->saveMenu('main');

        $root = $menu->getRootItem();
        self::assertInstanceOf(MenuItem::class, $root);
        self::assertNull($root->getParent());
        self::assertSame($menu, $root->getMenu());
        self::assertSame('published', $root->getPublishState());
        self::assertSame(1, $this->rowCount('sylius_happy_cms__menu_item'));
        self::assertSame(
            $root->getId(),
            (int) $this->entityManager->getConnection()->fetchOne('SELECT root_item_id FROM sylius_happy_cms__menu WHERE id = ?', [$menu->getId()]),
        );
    }

    public function testTheRootItemIsNamedAfterTheTranslationOfEachLocaleByDefault(): void
    {
        $root = $this->saveMenu('main')->getRootItem();

        self::assertNotNull($root);
        self::assertSame('Root item', $root->getTranslation('en_US')->getName());
        self::assertSame('Élément racine', $root->getTranslation('fr_FR')->getName());
    }

    public function testAMenuCreatedWithItsOwnRootItemKeepsIt(): void
    {
        $this->registerListener();
        $menu = $this->newMenu('main');
        $root = $this->newItem($menu, 'Custom root');
        $menu->setRootItem($root);
        $this->entityManager->persist($menu);
        $this->entityManager->flush();

        self::assertSame($root, $menu->getRootItem());
        self::assertSame(1, $this->rowCount('sylius_happy_cms__menu_item'));
    }

    public function testTheRootItemClassComesFromTheMappingWithoutTheMenuItemResource(): void
    {
        $this->resources = ['app.menu_link' => ['classes' => ['model' => MenuItem::class]]];

        $menu = $this->saveMenu('main');

        self::assertSame(1, $this->rowCount('sylius_happy_cms__menu_item'));
        self::assertInstanceOf(MenuItem::class, $menu->getRootItem());
    }

    public function testTheMenuItemResourceComesFirst(): void
    {
        $this->resources = ['sylius_happy_cms.menu_item' => ['classes' => ['model' => 'App\\Entity\\Missing']]];

        $menu = $this->saveMenu('main');

        self::assertNull($menu->getRootItem());
        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu_item'));
    }

    public function testNoRootItemWithoutTheMenuItemResourceNorTheManagerRegistry(): void
    {
        $this->resources = [];
        $this->withManagerRegistry = false;

        $menu = $this->saveMenu('main');

        self::assertNull($menu->getRootItem());
        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu_item'));
    }

    public function testTheRootItemIsNotAssignedWhenTheOptionIsOff(): void
    {
        $this->assignRootItem = false;

        $menu = $this->saveMenu('main');

        self::assertNull($menu->getRootItem());
        self::assertSame(1, $this->rowCount('sylius_happy_cms__menu_item'));
        self::assertNull($this->entityManager->getConnection()->fetchOne('SELECT root_item_id FROM sylius_happy_cms__menu WHERE id = ?', [$menu->getId()]));
        $root = $menu->getItems()->first();
        self::assertInstanceOf(MenuItem::class, $root);
        self::assertNull($root->getParent());
        self::assertSame('Root item', $root->getTranslation('en_US')->getName());
    }

    public function testAMenuCreatedWithItsOwnRootItemGetsASecondRootWhenTheOptionIsOff(): void
    {
        $this->assignRootItem = false;
        $this->registerListener();
        $menu = $this->newMenu('main');
        $root = $this->newItem($menu, 'Custom root');
        $menu->setRootItem($root);
        $this->entityManager->persist($menu);
        $this->entityManager->flush();

        self::assertSame($root, $menu->getRootItem());
        self::assertSame(2, $this->rowCount('sylius_happy_cms__menu_item'));
    }

    public function testTheRootItemClassComesFromTheMappingWithoutTheMenuItemResourceWhenTheOptionIsOff(): void
    {
        $this->assignRootItem = false;
        $this->resources = ['app.menu_link' => ['classes' => ['model' => MenuItem::class]]];

        $menu = $this->saveMenu('main');

        self::assertNull($menu->getRootItem());
        self::assertSame(1, $this->rowCount('sylius_happy_cms__menu_item'));
    }

    protected function configureEntityManager(EntityManagerInterface $entityManager): void
    {
        $entityManager->getEventManager()->addEventListener([Events::loadClassMetadata], new class() {
            public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
            {
                // What DoctrineBundle does for the `doctrine.orm.entity_listener` tag.
                if (Menu::class === $args->getClassMetadata()->getName()) {
                    $args->getClassMetadata()->addEntityListener(Events::prePersist, MenuCreationListener::class, 'prePersist');
                }
            }
        });
    }

    private function saveMenu(string $code): Menu
    {
        $this->registerListener();

        $menu = $this->newMenu($code);
        $this->entityManager->persist($menu);
        $this->entityManager->flush();

        return $menu;
    }

    private function registerListener(): void
    {
        $localeProvider = $this->createStub(LocaleProviderInterface::class);
        $localeProvider->method('getAvailableLocalesCodes')->willReturn(['en_US', 'fr_FR']);
        $localeProvider->method('getDefaultLocaleCode')->willReturn('en_US');

        $root = dirname(__DIR__, 3);
        $translator = new Translator('en_US');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource('yaml', $root . '/translations/messages.en.yaml', 'en');
        $translator->addResource('yaml', $root . '/translations/messages.fr.yaml', 'fr');

        $listener = new MenuCreationListener(
            $localeProvider,
            $translator,
            new ParameterBag(['sylius.resources' => $this->resources]),
            $this->assignRootItem,
            $this->withManagerRegistry ? $this->managerRegistry() : null,
        );

        $this->entityManager->getConfiguration()->getEntityListenerResolver()->register($listener);
    }

    private function managerRegistry(): ManagerRegistry
    {
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($this->entityManager);

        return $managerRegistry;
    }
}
