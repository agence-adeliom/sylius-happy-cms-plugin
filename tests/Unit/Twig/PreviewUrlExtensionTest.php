<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Twig;

use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewSigningDeniedException;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlGeneratorInterface;
use Adeliom\SyliusHappyCMSPlugin\Twig\PreviewUrlExtension;
use PHPUnit\Framework\TestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;

final class PreviewUrlExtensionTest extends TestCase
{
    public function testItReturnsTheSignedUrl(): void
    {
        $generator = $this->createStub(PreviewUrlGeneratorInterface::class);
        $generator->method('generate')->willReturn('https://front.test/p?_hash=x');

        self::assertSame('https://front.test/p?_hash=x', (new PreviewUrlExtension($generator))->getSignedPreviewUrl(new Page(), 'sylius_happy_cms.page', 'fr_FR'));
    }

    /**
     * The page builder page still opens without the right to sign: the iframe stays empty and the page builder
     * reports an unreachable preview, as the 403 iframe did before signed previews.
     */
    public function testItReturnsAnEmptyUrlWhenSigningIsDenied(): void
    {
        $generator = $this->createStub(PreviewUrlGeneratorInterface::class);
        $generator->method('generate')->willThrowException(new PreviewSigningDeniedException());

        self::assertSame('', (new PreviewUrlExtension($generator))->getSignedPreviewUrl(new Page(), 'sylius_happy_cms.page', 'fr_FR'));
    }
}
