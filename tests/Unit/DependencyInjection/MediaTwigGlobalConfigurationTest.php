<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MediaTwigGlobalConfigurationTest extends TestCase
{
    public function testTheTwigGlobalKeepsParametersForTheTwigConfigurationToResolve(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $extension = new SyliusHappyCMSExtension();
        $container->registerExtension($extension);
        $container->loadFromExtension('sylius_happy_cms', ['media' => ['base_url' => 'https://%front_url%/media/download']]);
        $container->loadFromExtension('sylius_happy_cms', ['media' => ['hide_files_ext' => false]]);

        $extension->prepend($container);

        $media = $this->mediaGlobal($container);
        self::assertSame('https://%front_url%/media/download', $media['base_url']);
        self::assertFalse($media['hide_files_ext']);
        self::assertSame('default.storage', $media['storage_name']);
    }

    public function testTheTwigGlobalFallsBackToTheDefaultsOnAValueOnlyValidOnceResolved(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $extension = new SyliusHappyCMSExtension();
        $container->registerExtension($extension);
        $container->loadFromExtension('sylius_happy_cms', ['media' => ['directory_check' => '%media_directory_check%']]);

        $extension->prepend($container);

        self::assertSame('public_dir', $this->mediaGlobal($container)['directory_check']);
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
