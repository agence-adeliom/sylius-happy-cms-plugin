<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Controller\PageBuilder;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewSigningDeniedException;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlGenerator;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Support\DatabaseSchema;
use Tests\Adeliom\SyliusHappyCMSPlugin\Support\PreviewPageFactory;

final class PreviewUrlActionTest extends WebTestCase
{
    private KernelBrowser $client;

    private PreviewPageFactory $factory;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        DatabaseSchema::reset($entityManager);
        $this->factory = new PreviewPageFactory(self::getContainer(), $entityManager);
    }

    public function testItReturnsASignedUrlForAnAuthorizedAdmin(): void
    {
        $page = $this->factory->page('ma-page');
        $this->client->loginUser($this->factory->admin(), 'admin');

        $this->client->request('GET', sprintf('/admin/happy-cms-builder/sylius_happy_cms.page/%d/preview-url?locale=fr_FR', $page->getId()), server: $this->adminServer());

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $url = $this->url();
        // The front host comes from the page channel, not from the admin host.
        self::assertSame('localhost', parse_url($url, \PHP_URL_HOST));
        self::assertStringEndsWith('/ma-page', (string) parse_url($url, \PHP_URL_PATH));
        self::assertStringContainsString('_hash=', $url);
        self::assertStringContainsString('_hcms_id=' . $page->getId(), $url);
        self::assertStringContainsString('_hcms_locale=fr_FR', $url);
    }

    public function testItReturns403WhenTheSignerRefuses(): void
    {
        $page = $this->factory->page('ma-page');
        $this->client->loginUser($this->factory->admin(), 'admin');

        self::getContainer()->set(PreviewUrlGenerator::class, new class() implements PreviewUrlGeneratorInterface {
            public function generate(CmsRoutableInterface $subject, string $resource, string $locale): string
            {
                throw new PreviewSigningDeniedException();
            }
        });

        $this->client->request('GET', sprintf('/admin/happy-cms-builder/sylius_happy_cms.page/%d/preview-url?locale=fr_FR', $page->getId()), server: $this->adminServer());

        self::assertResponseStatusCodeSame(403);
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertArrayHasKey('error', $this->json());
    }

    /**
     * The admin routes answer on ADMIN_HOST when it is set (see docs/contribution.md), on any host otherwise.
     *
     * @return array<string, string>
     */
    private function adminServer(): array
    {
        $adminHost = self::getContainer()->getParameter('admin_host');

        return [
            'HTTP_HOST' => is_string($adminHost) && '' !== $adminHost ? $adminHost : 'localhost',
            'HTTP_ACCEPT' => 'application/json',
        ];
    }

    private function url(): string
    {
        $url = $this->json()['url'] ?? null;
        self::assertIsString($url);

        return $url;
    }

    /**
     * @return array<string, mixed>
     */
    private function json(): array
    {
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);

        return $data;
    }
}
