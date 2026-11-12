<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MediaTwigGlobalConfigurationTest extends TestCase
{
    public function testTheTwigGlobalHoldsTheApplicationConfigurationWithoutOption(): void
    {
        $container = $this->prepend([
            ['media' => ['base_url' => '/media/download', 'storage_name' => 'uploads.storage']],
            ['media' => ['hide_files_ext' => false]],
        ]);

        $media = $this->mediaGlobal($container);
        self::assertSame('/media/download', $media['base_url']);
        self::assertSame('uploads.storage', $media['storage_name']);
        self::assertFalse($media['hide_files_ext']);
        // Defaults stay available.
        self::assertSame(['binary/octet-stream'], $media['extended_mimes']['image']);
        // Nothing is prepended to the unknown "media" extension.
        self::assertSame([], $container->getExtensionConfig('media'));
    }

    public function testTheTwigGlobalKeepsParametersForTheTwigConfigurationToResolve(): void
    {
        $container = $this->prepend([
            ['media' => ['base_url' => 'https://%front_url%/media/download']],
        ]);

        self::assertSame('https://%front_url%/media/download', $this->mediaGlobal($container)['base_url']);
    }

    public function testTheTwigGlobalFallsBackToTheDefaultsOnAValueOnlyValidOnceResolved(): void
    {
        $container = $this->prepend([
            ['media' => ['admin_path' => '%media_admin_path%']],
        ]);

        self::assertSame('happy-cms-media/medias', $this->mediaGlobal($container)['admin_path']);
    }

    /**
     * @param list<array<string, mixed>> $configs
     */
    private function prepend(array $configs): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $extension = new SyliusHappyCMSExtension();
        $container->registerExtension($extension);
        foreach ($configs as $config) {
            $container->loadFromExtension('sylius_happy_cms', $config);
        }

        $extension->prepend($container);

        return $container;
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaGlobal(ContainerBuilder $container): array
    {
        foreach ($container->getExtensionConfig('twig') as $config) {
            if (isset($config['globals']['sylius_happy_cms']['media'])) {
                return $config['globals']['sylius_happy_cms']['media'];
            }
        }

        self::fail('No sylius_happy_cms.media Twig global prepended.');
    }
}
