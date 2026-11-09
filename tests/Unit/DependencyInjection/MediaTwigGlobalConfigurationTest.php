<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MediaTwigGlobalConfigurationTest extends TestCase
{
    /** @var list<string> */
    private array $deprecations = [];

    public function testTheFlagIsNullByDefault(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        (new SyliusHappyCMSExtension())->load([], $container);

        self::assertNull($container->getParameter('sylius_happy_cms.media.twig_global_from_config'));
    }

    public function testWithoutTheFlagTheTwigGlobalHoldsThePluginDefaultsAndADeprecationIsTriggered(): void
    {
        $container = $this->prepend([['media' => ['base_url' => '/media/download', 'storage_name' => 'uploads.storage']]]);

        $media = $this->mediaGlobal($container);
        self::assertSame('/', $media['base_url']);
        self::assertSame('default.storage', $media['storage_name']);
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('Not setting the "sylius_happy_cms.media.twig_global_from_config" option is deprecated.', $this->deprecations[0]);
    }

    public function testWithTheFlagSetToFalseTheTwigGlobalHoldsThePluginDefaultsAndADeprecationIsTriggered(): void
    {
        $container = $this->prepend([['media' => ['base_url' => '/media/download', 'twig_global_from_config' => false]]]);

        self::assertSame('/', $this->mediaGlobal($container)['base_url']);
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('Setting the "sylius_happy_cms.media.twig_global_from_config" option to false is deprecated.', $this->deprecations[0]);
    }

    public function testWithTheFlagTheTwigGlobalHoldsTheApplicationConfigurationWithoutDeprecation(): void
    {
        $container = $this->prepend([
            ['media' => ['twig_global_from_config' => true, 'base_url' => '/media/download', 'storage_name' => 'uploads.storage']],
            ['media' => ['hide_files_ext' => false]],
        ]);

        $media = $this->mediaGlobal($container);
        self::assertSame('/media/download', $media['base_url']);
        self::assertSame('uploads.storage', $media['storage_name']);
        self::assertFalse($media['hide_files_ext']);
        // Defaults stay available.
        self::assertSame(['binary/octet-stream'], $media['extended_mimes']['image']);
        self::assertSame([], $this->deprecations);
        // Nothing is prepended to the unknown "media" extension any more.
        self::assertSame([], $container->getExtensionConfig('media'));
    }

    public function testTheTwigGlobalKeepsParametersForTheTwigConfigurationToResolve(): void
    {
        $container = $this->prepend([
            ['media' => ['twig_global_from_config' => true, 'base_url' => 'https://%front_url%/media/download']],
        ]);

        self::assertSame('https://%front_url%/media/download', $this->mediaGlobal($container)['base_url']);
    }

    public function testTheFlagCanBeAParameter(): void
    {
        $container = $this->prepend([
            ['media' => ['twig_global_from_config' => '%media_twig_global%', 'base_url' => '/media/download']],
        ], ['media_twig_global' => true]);

        self::assertSame('/media/download', $this->mediaGlobal($container)['base_url']);
        self::assertSame([], $this->deprecations);
    }

    public function testTheTwigGlobalFallsBackToTheDefaultsOnAValueOnlyValidOnceResolved(): void
    {
        $container = $this->prepend([
            ['media' => ['twig_global_from_config' => true, 'admin_path' => '%media_admin_path%']],
        ]);

        self::assertSame('happy-cms-media/medias', $this->mediaGlobal($container)['admin_path']);
    }

    /**
     * @param list<array<string, mixed>> $configs
     * @param array<string, mixed>       $parameters
     */
    private function prepend(array $configs, array $parameters = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        foreach ($parameters as $name => $value) {
            $container->setParameter($name, $value);
        }
        $extension = new SyliusHappyCMSExtension();
        $container->registerExtension($extension);
        foreach ($configs as $config) {
            $container->loadFromExtension('sylius_happy_cms', $config);
        }

        $this->deprecations = [];
        set_error_handler(function (int $level, string $message): bool {
            if (str_contains($message, 'twig_global_from_config')) {
                $this->deprecations[] = $message;
            }

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $extension->prepend($container);
        } finally {
            restore_error_handler();
        }

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
