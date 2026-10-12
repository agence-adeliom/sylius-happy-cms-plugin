<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Integration\Media;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * The `sylius_happy_cms.media` Twig global (read by the media manager templates) holds the
 * configuration of the application, not the defaults of the plugin.
 */
final class MediaTwigGlobalTest extends KernelTestCase
{
    public function testTheMediaGlobalHoldsTheApplicationConfiguration(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $globals = $twig->getGlobals();
        self::assertIsArray($globals['sylius_happy_cms'] ?? null);
        $media = $globals['sylius_happy_cms']['media'] ?? null;
        self::assertIsArray($media);

        // Values of tests/TestApplication/config/packages/sylius_happy_cms.yaml (defaults: "/" and "default.storage").
        self::assertSame('/media/download', $media['base_url']);
        self::assertSame('uploads.storage', $media['storage_name']);
        // Defaults stay available.
        self::assertTrue($media['hide_files_ext']);
        self::assertSame(['binary/octet-stream'], $media['extended_mimes']['image']);
    }

    public function testTheMediaGlobalRendersInATemplate(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $template = $twig->createTemplate('{{ sylius_happy_cms.media.base_url }}');

        self::assertSame('/media/download', $template->render());
    }
}
