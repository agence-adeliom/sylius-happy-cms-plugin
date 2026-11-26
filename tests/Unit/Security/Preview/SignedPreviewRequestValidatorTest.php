<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Security\Preview;

use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlSigner;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewContext;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewRequestValidator;
use Adeliom\SyliusHappyCMSPlugin\Security\PreviewRequestMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;

final class SignedPreviewRequestValidatorTest extends TestCase
{
    private MockClock $clock;

    private UriSigner $uriSigner;

    private SignedPreviewRequestValidator $validator;

    protected function setUp(): void
    {
        $this->clock = new MockClock('2026-11-26 10:00:00', 'UTC');
        $this->uriSigner = new UriSigner('secret', clock: $this->clock);
        $this->validator = new SignedPreviewRequestValidator(new PreviewRequestMatcher(), $this->uriSigner);
    }

    public function testItReturnsTheContextOfAValidSignedRequest(): void
    {
        $context = $this->validator->validate(Request::create($this->signedUrl()));

        self::assertEquals(new SignedPreviewContext('sylius_happy_cms.page', 42, 'fr_FR', 'http://admin.localhost:8099'), $context);
    }

    public function testItAcceptsAnAdminOriginWithAPort(): void
    {
        $context = $this->validator->validate(Request::create($this->signedUrl()));

        self::assertSame('http://admin.localhost:8099', $context?->adminOrigin);
    }

    public function testItRejectsAnExpiredUrl(): void
    {
        $url = $this->signedUrl();
        $this->clock->sleep(301);

        self::assertNull($this->validator->validate(Request::create($url)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function alterations(): iterable
    {
        yield 'resource' => ['_hcms_resource=sylius_happy_cms.page', '_hcms_resource=app.post'];
        yield 'id' => ['_hcms_id=42', '_hcms_id=43'];
        yield 'locale' => ['_hcms_locale=fr_FR', '_hcms_locale=en_US'];
        yield 'admin host' => ['_hcms_admin_host=http%3A%2F%2Fadmin.localhost%3A8099', '_hcms_admin_host=https%3A%2F%2Fevil.test'];
        yield 'expiration' => ['_expiration=1795687500', '_expiration=1895687500'];
        yield 'path' => ['/ma-page?', '/une-autre-page?'];
    }

    #[DataProvider('alterations')]
    public function testItRejectsAnAlteredParameter(string $search, string $replace): void
    {
        $url = $this->signedUrl();
        self::assertStringContainsString($search, $url);

        self::assertNull($this->validator->validate(Request::create(str_replace($search, $replace, $url))));
    }

    public function testItRejectsAnUnsignedPreviewRequest(): void
    {
        self::assertNull($this->validator->validate(Request::create('http://localhost:8099/ma-page?happy_cms_preview=1')));
    }

    public function testItRejectsASignedRequestWithoutThePreviewFlag(): void
    {
        $url = $this->uriSigner->sign('http://localhost:8099/ma-page?_hcms_resource=sylius_happy_cms.page&_hcms_id=42&_hcms_locale=fr_FR&_hcms_admin_host=' . rawurlencode('http://admin.localhost:8099'), new \DateInterval('PT300S'));

        self::assertNull($this->validator->validate(Request::create($url)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidOrigins(): iterable
    {
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'path' => ['http://admin.test/path'];
        yield 'ftp' => ['ftp://admin.test'];
        yield 'empty' => [''];
        yield 'credentials' => ['http://user@admin.test'];
        yield 'query' => ['http://admin.test?x=1'];
    }

    #[DataProvider('invalidOrigins')]
    public function testItRejectsAnInvalidAdminOrigin(string $origin): void
    {
        $url = $this->uriSigner->sign(
            'http://localhost:8099/ma-page?' . http_build_query([
                'happy_cms_preview' => '1',
                '_hcms_resource' => 'sylius_happy_cms.page',
                '_hcms_id' => '42',
                '_hcms_locale' => 'fr_FR',
                '_hcms_admin_host' => $origin,
            ]),
            new \DateInterval('PT300S'),
        );

        self::assertNull($this->validator->validate(Request::create($url)));
    }

    public function testItRejectsANonNumericId(): void
    {
        $url = $this->uriSigner->sign(
            'http://localhost:8099/ma-page?' . http_build_query([
                'happy_cms_preview' => '1',
                '_hcms_resource' => 'sylius_happy_cms.page',
                '_hcms_id' => 'abc',
                '_hcms_locale' => 'fr_FR',
                '_hcms_admin_host' => 'http://admin.localhost:8099',
            ]),
            new \DateInterval('PT300S'),
        );

        self::assertNull($this->validator->validate(Request::create($url)));
    }

    public function testItCachesTheResultOnTheRequest(): void
    {
        $request = Request::create($this->signedUrl());
        $first = $this->validator->validate($request);

        // A second validation reads the cached result instead of checking the signature again.
        $this->clock->sleep(301);

        self::assertNotNull($first);
        self::assertSame($first, $this->validator->validate($request));
    }

    private function signedUrl(): string
    {
        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturn(true);

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('http://admin.localhost:8099/admin/pages'));

        $page = new Page();
        (new \ReflectionProperty($page, 'id'))->setValue($page, 42);

        $signer = new PreviewUrlSigner($checker, $this->uriSigner, $requestStack, $this->clock, 300);

        return $signer->sign('//localhost:8099/ma-page', $page, 'sylius_happy_cms.page', 'fr_FR');
    }
}
