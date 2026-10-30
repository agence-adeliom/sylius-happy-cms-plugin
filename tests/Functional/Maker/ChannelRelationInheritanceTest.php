<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Maker;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\Config;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\SharedBlock\SharedBlock;

/**
 * Les entités générées par `make:happy-cms:install` étendent les classes de base du plugin :
 * la relation `channels` est héritée, sans changement de squelette.
 */
final class ChannelRelationInheritanceTest extends KernelTestCase
{
    public function testSkeletonEntitiesExtendTheBaseClasses(): void
    {
        $skeleton = file_get_contents(dirname(__DIR__, 3) . '/src/Resources/skeleton/default/entity.tpl.php');

        self::assertIsString($skeleton);
        self::assertStringContainsString('extends Base<?= $classNameDetail->getShortName() ?>', $skeleton);
        self::assertStringNotContainsString('channels', $skeleton, 'The relation must come from the base class, not from the skeleton.');
    }

    /**
     * @param class-string $entityClass
     */
    #[DataProvider('entities')]
    public function testChannelsRelationIsInheritedWithAnExplicitJoinTable(string $entityClass, string $joinTable): void
    {
        $entityManager = $this->getEntityManager();

        $mapping = $entityManager->getClassMetadata($entityClass)->getAssociationMapping('channels');

        self::assertSame($joinTable, $mapping->joinTable->name);
        self::assertSame([], (new SchemaValidator($entityManager))->validateClass($entityManager->getClassMetadata($entityClass)));
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function entities(): iterable
    {
        yield 'menu' => [Menu::class, 'sylius_happy_cms__menu_channels'];
        yield 'shared block' => [SharedBlock::class, 'sylius_happy_cms__shared_block_channels'];
        yield 'config' => [Config::class, 'sylius_happy_cms__config_channels'];
    }

    private function getEntityManager(): EntityManagerInterface
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
