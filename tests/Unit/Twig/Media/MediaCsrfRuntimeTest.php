<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Twig\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\CsrfProtection;
use Adeliom\SyliusHappyCMSPlugin\Twig\Media\MediaCsrfRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MediaCsrfRuntimeTest extends TestCase
{
    use CsrfProtection;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createStub(TranslatorInterface::class);
    }

    public function testTheTokenIsAcceptedByTheMediaRoutes(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $token = (new MediaCsrfRuntime($requestStack, $this->translator))->csrfToken();

        $request->headers->set('X-CSRF-Token', $token);
        self::assertTrue($this->validateCsrfToken($request));
    }

    public function testThereIsNoTokenOutsideARequest(): void
    {
        self::assertSame('', (new MediaCsrfRuntime(new RequestStack(), $this->translator))->csrfToken());
    }
}
