<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Integration\Channel;

use Adeliom\SyliusHappyCMSPlugin\Entity\Channel\ChannelAwareInterface;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\Config;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\SharedBlock\SharedBlock;

final class ChannelAwareEntitiesTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        if ($this->entityManager->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            $this->entityManager->getConnection()->executeStatement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * @return iterable<string, array{class-string<ChannelAwareInterface>, callable(): ChannelAwareInterface}>
     */
    public static function channelAwareEntities(): iterable
    {
        yield 'menu' => [Menu::class, static function (): Menu {
            $menu = new Menu();
            $menu->setCode('main');
            $menu->setName('Main');

            return $menu;
        }];

        yield 'shared block' => [SharedBlock::class, static function (): SharedBlock {
            $block = new SharedBlock();
            $block->setKey('footer');
            $block->setType('text');
            $block->setName('Footer');

            return $block;
        }];

        yield 'config' => [Config::class, static function (): Config {
            $config = new Config();
            $config->setKey('site_title');
            $config->setName('Site title');
            $config->setType('text');

            return $config;
        }];
    }

    /**
     * @param class-string<ChannelAwareInterface> $class
     * @param callable(): ChannelAwareInterface $factory
     */
    #[DataProvider('channelAwareEntities')]
    public function testPersistWithoutChannelsIsAvailableForAllChannels(string $class, callable $factory): void
    {
        $entity = $factory();
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->getRepository($class)->findOneBy([]);

        self::assertInstanceOf($class, $reloaded);
        self::assertTrue($reloaded->isAvailableForAllChannels());
    }

    /**
     * @param class-string<ChannelAwareInterface> $class
     * @param callable(): ChannelAwareInterface $factory
     */
    #[DataProvider('channelAwareEntities')]
    public function testPersistWithTwoChannelsReloadsBoth(string $class, callable $factory): void
    {
        $entity = $factory();
        $entity->addChannel($this->createChannel('FR_WEB'));
        $entity->addChannel($this->createChannel('EN_WEB'));
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->getRepository($class)->findOneBy([]);

        self::assertInstanceOf($class, $reloaded);
        self::assertFalse($reloaded->isAvailableForAllChannels());
        $codes = $reloaded->getChannels()->map(static fn ($channel) => $channel->getCode())->toArray();
        sort($codes);
        self::assertSame(['EN_WEB', 'FR_WEB'], $codes);
    }

    /**
     * @param class-string<ChannelAwareInterface> $class
     * @param callable(): ChannelAwareInterface $factory
     */
    #[DataProvider('channelAwareEntities')]
    public function testDeletingAChannelKeepsTheEntity(string $class, callable $factory): void
    {
        $channel = $this->createChannel('FR_WEB');
        $entity = $factory();
        $entity->addChannel($channel);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->entityManager->remove($channel);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->getRepository($class)->findOneBy([]);

        self::assertInstanceOf($class, $reloaded);
        self::assertTrue($reloaded->isAvailableForAllChannels());
    }

    private function createChannel(string $code): Channel
    {
        $channel = new Channel();
        $channel->setCode($code);
        $channel->setName($code);
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setDefaultLocale($this->getLocale());
        $channel->setBaseCurrency($this->getCurrency());
        $this->entityManager->persist($channel);

        return $channel;
    }

    private function getLocale(): Locale
    {
        $locale = $this->entityManager->getRepository(Locale::class)->findOneBy(['code' => 'fr_FR']);
        if (!$locale instanceof Locale) {
            $locale = new Locale();
            $locale->setCode('fr_FR');
            $this->entityManager->persist($locale);
            $this->entityManager->flush();
        }

        return $locale;
    }

    private function getCurrency(): Currency
    {
        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        if (!$currency instanceof Currency) {
            $currency = new Currency();
            $currency->setCode('EUR');
            $this->entityManager->persist($currency);
            $this->entityManager->flush();
        }

        return $currency;
    }
}
