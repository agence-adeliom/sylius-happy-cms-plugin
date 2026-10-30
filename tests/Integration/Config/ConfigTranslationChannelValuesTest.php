<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Integration\Config;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\Config;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\ConfigTranslation;
use Tests\Adeliom\SyliusHappyCMSPlugin\Support\DatabaseSchema;

final class ConfigTranslationChannelValuesTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        DatabaseSchema::reset($this->entityManager);
    }

    public function testEmptyByDefault(): void
    {
        $translation = new ConfigTranslation();

        self::assertSame([], $translation->getChannelValues());
        self::assertNull($translation->getChannelValue('FR_WEB'));
    }

    public function testRoundTripsJsonAndHtmlUnchanged(): void
    {
        $json = '{"a":"é\"<b>x</b>"}';
        $html = '<p class="x">é « test » & co</p>';

        $translation = $this->persistConfigWithTranslation(static function (ConfigTranslation $translation) use ($json, $html): void {
            $translation->setChannelValue('FR_WEB', $json);
            $translation->setChannelValue('EN_WEB', $html);
        });
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(ConfigTranslation::class, $translation->getId());

        self::assertInstanceOf(ConfigTranslation::class, $reloaded);
        self::assertSame($json, $reloaded->getChannelValue('FR_WEB'));
        self::assertSame($html, $reloaded->getChannelValue('EN_WEB'));
    }

    public function testSettingNullRemovesKey(): void
    {
        $translation = new ConfigTranslation();
        $translation->setChannelValue('FR_WEB', 'x');
        $translation->setChannelValue('EN_WEB', 'y');

        $translation->setChannelValue('FR_WEB', null);
        $translation->setChannelValue('EN_WEB', '');

        self::assertSame([], $translation->getChannelValues());
    }

    public function testSetChannelValuesReplacesAll(): void
    {
        $translation = new ConfigTranslation();
        $translation->setChannelValue('FR_WEB', 'x');

        $translation->setChannelValues(['EN_WEB' => 'y']);

        self::assertSame(['EN_WEB' => 'y'], $translation->getChannelValues());
    }

    public function testExistingTranslationWithoutOverrideIsUnchanged(): void
    {
        $translation = $this->persistConfigWithTranslation(static function (ConfigTranslation $translation): void {
            $translation->setValue('default');
        });
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(ConfigTranslation::class, $translation->getId());

        self::assertInstanceOf(ConfigTranslation::class, $reloaded);
        self::assertSame('default', $reloaded->getValue());
        self::assertSame([], $reloaded->getChannelValues());
    }

    /**
     * @param callable(ConfigTranslation): void $configure
     */
    private function persistConfigWithTranslation(callable $configure): ConfigTranslation
    {
        $config = new Config();
        $config->setKey('site_title');
        $config->setName('Site title');
        $config->setType('text');
        $config->setCurrentLocale('fr_FR');
        $config->setFallbackLocale('fr_FR');

        /** @var ConfigTranslation $translation */
        $translation = $config->getTranslation('fr_FR');
        $configure($translation);

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $translation;
    }
}
