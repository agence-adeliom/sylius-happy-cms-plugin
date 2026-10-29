<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\Config;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\ConfigTranslation;
use Twig\Environment;

final class ConfigExtensionTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $config = new Config();
        $config->setKey('site_title');
        $config->setName('Site title');
        $config->setType('text');
        $translation = new ConfigTranslation();
        $translation->setLocale('en_US');
        $translation->setValue('Default title');
        $translation->setChannelValues(['FR_WEB' => 'Titre FR']);
        $config->addTranslation($translation);
        $entityManager->persist($config);
        $entityManager->flush();
        $entityManager->clear();
    }

    public function testReadsDefaultValueWithoutChannel(): void
    {
        self::assertSame('Default title', $this->render("{{ happy_cms_config('site_title') }}"));
    }

    public function testReadsChannelOverrideFromCode(): void
    {
        self::assertSame('Titre FR', $this->render("{{ happy_cms_config('site_title', 'FR_WEB') }}"));
    }

    public function testUnknownChannelAndKeyAreSafe(): void
    {
        self::assertSame('Default title', $this->render("{{ happy_cms_config('site_title', 'NOPE') }}"));
        self::assertSame('', $this->render("{{ happy_cms_config('missing', 'FR_WEB') }}"));
    }

    private function render(string $template): string
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig->createTemplate($template)->render();
    }
}
