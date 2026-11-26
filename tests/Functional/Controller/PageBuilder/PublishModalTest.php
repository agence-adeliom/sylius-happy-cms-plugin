<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Controller\PageBuilder;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Adeliom\SyliusHappyCMSPlugin\Support\DatabaseSchema;
use Tests\Adeliom\SyliusHappyCMSPlugin\Support\PreviewPageFactory;

/**
 * The publish modal lets the admin pick the locales to publish, unless the store has a single locale:
 * it then only asks for confirmation and publishes that locale.
 */
final class PublishModalTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        DatabaseSchema::reset($entityManager);
        $this->entityManager = $entityManager;
    }

    public function testSeveralLocalesCanBeChosen(): void
    {
        $modal = $this->openModal(['fr_FR', 'en_US']);

        self::assertCount(1, $modal->filter('#selectAllLocales'));
        self::assertCount(2, $modal->filter('.locale-checkbox:not([hidden])'));
        self::assertStringContainsString('Sélectionnez les langues', $modal->filter('.modal-body')->text());
    }

    public function testASingleLocaleOnlyAsksForConfirmation(): void
    {
        $modal = $this->openModal(['fr_FR']);

        self::assertCount(0, $modal->filter('#selectAllLocales'));
        self::assertCount(0, $modal->filter('.form-check'));
        self::assertStringContainsString('Voulez-vous publier le contenu ?', $modal->filter('.modal-body')->text());

        // The page builder script still reads the locale to publish from the checked locale checkboxes
        $locale = $modal->filter('.locale-checkbox');
        self::assertCount(1, $locale);
        self::assertSame('fr_FR', $locale->attr('value'));
        self::assertNotNull($locale->attr('checked'));
        self::assertNotNull($locale->attr('hidden'));
    }

    /**
     * @param non-empty-list<string> $localeCodes
     */
    private function openModal(array $localeCodes): Crawler
    {
        $factory = new PreviewPageFactory(self::getContainer(), $this->entityManager, $localeCodes);
        $page = $factory->page('ma-page');
        $this->client->loginUser($factory->admin(), 'admin');

        $crawler = $this->client->request('GET', sprintf('/admin/happy-cms-builder/sylius_happy_cms.page/%d?locale=fr_FR', $page->getId()), server: [
            'HTTP_HOST' => $this->adminHost(),
        ]);

        self::assertResponseIsSuccessful();

        return $crawler->filter('#publishModal');
    }

    /**
     * The admin routes answer on ADMIN_HOST when it is set (see docs/contribution.md), on any host otherwise.
     */
    private function adminHost(): string
    {
        $adminHost = self::getContainer()->getParameter('admin_host');

        return is_string($adminHost) && '' !== $adminHost ? $adminHost : 'localhost';
    }
}
