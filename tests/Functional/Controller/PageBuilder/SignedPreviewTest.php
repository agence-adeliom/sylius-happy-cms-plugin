<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Controller\PageBuilder;

use Adeliom\SyliusHappyCMSPlugin\Block\TextCtaBlockType;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Adeliom\SyliusHappyCMSPlugin\Support\DatabaseSchema;
use Tests\Adeliom\SyliusHappyCMSPlugin\Support\PreviewPageFactory;

/**
 * A signed preview URL opens the page builder preview without any admin session, for one page and one locale only.
 */
final class SignedPreviewTest extends WebTestCase
{
    private const string ADMIN_ORIGIN = 'http://admin.localhost';

    private KernelBrowser $client;

    private PreviewPageFactory $factory;

    private ?AdminUserInterface $admin = null;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        DatabaseSchema::reset($entityManager);
        $this->factory = new PreviewPageFactory(self::getContainer(), $entityManager);
    }

    public function testASignedPreviewIsServedWithoutAdminSession(): void
    {
        $url = $this->signedUrl($this->factory->page('ma-page'));

        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('page-builder-preview', $content);
        self::assertStringContainsString('data-admin-origin="' . self::ADMIN_ORIGIN . '"', $content);
        self::assertStringContainsString('private', (string) $this->client->getResponse()->headers->get('Cache-Control'));
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex');
        self::assertResponseHeaderSame('Content-Security-Policy', 'frame-ancestors ' . self::ADMIN_ORIGIN);
        self::assertResponseNotHasHeader('X-Frame-Options');
    }

    public function testAnExpiredSignedPreviewIsRefused(): void
    {
        $page = $this->factory->page('ma-page');
        $path = (string) parse_url($this->signedUrl($page), \PHP_URL_PATH);

        $uriSigner = self::getContainer()->get('uri_signer');
        self::assertInstanceOf(UriSigner::class, $uriSigner);

        $url = $uriSigner->sign('http://localhost' . $path . '?' . http_build_query([
            'happy_cms_preview' => '1',
            '_hcms_resource' => 'sylius_happy_cms.page',
            '_hcms_id' => (string) $page->getId(),
            '_hcms_locale' => 'fr_FR',
            '_hcms_admin_host' => self::ADMIN_ORIGIN,
        ]), new \DateTimeImmutable('-1 minute'));

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(403);
    }

    public function testAnAlteredSignedPreviewIsRefused(): void
    {
        $page = $this->factory->page('ma-page');
        $url = $this->signedUrl($page);
        $altered = str_replace('_hcms_id=' . $page->getId(), '_hcms_id=' . ($page->getId() + 1), $url);
        self::assertNotSame($url, $altered);

        $this->client->request('GET', $altered);

        self::assertResponseStatusCodeSame(403);
    }

    public function testASignatureForAnotherPageIsRefused(): void
    {
        $pageA = $this->factory->page('page-a');
        $pageB = $this->factory->page('page-b');

        $url = $this->signedUrl($pageA);
        $pathA = (string) parse_url($url, \PHP_URL_PATH);
        $pathB = (string) parse_url($this->signedUrl($pageB), \PHP_URL_PATH);

        $this->client->request('GET', str_replace($pathA, $pathB, $url));

        self::assertResponseStatusCodeSame(403);
    }

    public function testASlugWithAccentsKeepsAValidSignature(): void
    {
        $this->client->request('GET', $this->signedUrl($this->factory->page('été-à-paris')));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('page-builder-preview', (string) $this->client->getResponse()->getContent());
    }

    public function testTheOverlayIsInTheAdminLocale(): void
    {
        $page = $this->factory->pageWithBlock('my-page', 'en_US', TextCtaBlockType::class);

        // The admin edits in French, the page is in English
        $this->client->request('GET', $this->signedUrl($page, 'en_US', 'fr_FR'));

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Éditer le bloc', $content);
        self::assertStringNotContainsString('Edit Block', $content);
        self::assertStringContainsString('data-block-name="', $content);
        self::assertStringContainsString('content-block-empty-placeholder', $content);
    }

    /**
     * Signs as an admin would in the page builder, then forgets the admin: the preview request carries no session.
     */
    private function signedUrl(Page $page, string $locale = 'fr_FR', string $adminLocale = 'fr_FR'): string
    {
        $container = self::getContainer();
        $admin = $this->admin ??= $this->factory->admin();

        $tokenStorage = $container->get('security.token_storage');
        self::assertInstanceOf(TokenStorageInterface::class, $tokenStorage);
        $tokenStorage->setToken(new UsernamePasswordToken($admin, 'admin', $admin->getRoles()));

        $requestStack = $container->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $adminRequest = Request::create(self::ADMIN_ORIGIN . '/admin/');
        $adminRequest->setLocale($adminLocale);
        $requestStack->push($adminRequest);

        // As in a real admin request, the router generates URLs from the admin host
        $router = $container->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        $previousContext = clone $router->getContext();
        $router->getContext()->fromRequest($adminRequest);

        try {
            $generator = $container->get(PreviewUrlGeneratorInterface::class);
            self::assertInstanceOf(PreviewUrlGeneratorInterface::class, $generator);
            $url = $generator->generate($page, 'sylius_happy_cms.page', $locale);
        } finally {
            $requestStack->pop();
            $router->setContext($previousContext);
            $tokenStorage->setToken(null);
        }

        self::assertNotSame('', $url);

        return $url;
    }
}
