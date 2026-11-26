<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Security\Preview;

use Adeliom\SyliusHappyCMSPlugin\Security\ContentDocumentVoter;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewSigningDeniedException;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlSigner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;

final class PreviewUrlSignerTest extends TestCase
{
    private MockClock $clock;

    private UriSigner $uriSigner;

    protected function setUp(): void
    {
        $this->clock = new MockClock('2026-11-26 10:00:00', 'UTC');
        $this->uriSigner = new UriSigner('secret', clock: $this->clock);
    }

    public function testItRefusesToSignWithoutPageBuilderRight(): void
    {
        $signer = $this->signer(granted: false);

        $this->expectException(PreviewSigningDeniedException::class);

        $signer->sign('/ma-page', self::page(), 'sylius_happy_cms.page', 'fr_FR');
    }

    public function testItChecksThePageBuilderRightOnTheSubject(): void
    {
        $page = self::page();
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->expects(self::once())
            ->method('isGranted')
            ->with(ContentDocumentVoter::PAGE_BUILDER, $page)
            ->willReturn(true);

        $signer = new PreviewUrlSigner($checker, $this->uriSigner, self::requestStack('http://admin.localhost:8099/admin/'), $this->clock, 300);

        $signer->sign('/ma-page', $page, 'sylius_happy_cms.page', 'fr_FR');
    }

    public function testItCompletesASchemeRelativeUrlWithTheAdminScheme(): void
    {
        $url = $this->signer()->sign('//localhost:8099/ma-page?happy_cms_preview=1', self::page(), 'sylius_happy_cms.page', 'fr_FR');

        self::assertStringStartsWith('http://localhost:8099/ma-page?', $url);
    }

    public function testItKeepsHttpsWhenTheAdminIsServedOverHttps(): void
    {
        $url = $this->signer(adminUrl: 'https://admin.example.test/admin/')
            ->sign('//front.example.test/p', self::page(), 'sylius_happy_cms.page', 'fr_FR');

        self::assertStringStartsWith('https://front.example.test/p?', $url);
    }

    public function testItCompletesARelativePathWithTheAdminHost(): void
    {
        $url = $this->signer()->sign('/ma-page', self::page(), 'sylius_happy_cms.page', 'fr_FR');

        self::assertStringStartsWith('http://admin.localhost:8099/ma-page?', $url);
    }

    public function testItAddsThePreviewAndSignedParameters(): void
    {
        $url = $this->signer()->sign('/ma-page?happy_cms_preview=1&foo=bar', self::page(), 'sylius_happy_cms.page', 'fr_FR');

        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);

        self::assertSame('1', $query['happy_cms_preview']);
        self::assertSame('bar', $query['foo']);
        self::assertSame('sylius_happy_cms.page', $query['_hcms_resource']);
        self::assertSame('42', $query['_hcms_id']);
        self::assertSame('fr_FR', $query['_hcms_locale']);
        self::assertSame('http://admin.localhost:8099', $query['_hcms_admin_host']);
        self::assertArrayHasKey('_hash', $query);
        self::assertSame(1, substr_count($url, 'happy_cms_preview='));
    }

    public function testItAddsTheAdminLocale(): void
    {
        $url = $this->signer()->sign('/ma-page', self::page(), 'sylius_happy_cms.page', 'en_US');

        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);

        // The admin request is in French, the previewed page in English.
        self::assertSame('fr_FR', $query['_hcms_admin_locale']);
        self::assertTrue($this->uriSigner->check($url));
    }

    public function testItExpiresAfterTheConfiguredTtl(): void
    {
        $url = $this->signer()->sign('/ma-page', self::page(), 'sylius_happy_cms.page', 'fr_FR');

        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);

        self::assertSame('1795687500', $query['_expiration']);
        self::assertTrue($this->uriSigner->check($url));

        $this->clock->sleep(301);

        self::assertFalse($this->uriSigner->check($url));
    }

    private function signer(bool $granted = true, string $adminUrl = 'http://admin.localhost:8099/admin/pages'): PreviewUrlSigner
    {
        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturn($granted);

        return new PreviewUrlSigner($checker, $this->uriSigner, self::requestStack($adminUrl), $this->clock, 300);
    }

    private static function requestStack(string $adminUrl): RequestStack
    {
        $request = Request::create($adminUrl);
        $request->setLocale('fr_FR');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    private static function page(): Page
    {
        $page = new Page();
        (new \ReflectionProperty($page, 'id'))->setValue($page, 42);

        return $page;
    }
}
