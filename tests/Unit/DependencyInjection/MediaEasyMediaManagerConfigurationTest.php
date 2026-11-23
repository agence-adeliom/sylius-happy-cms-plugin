<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Yaml\Yaml;

final class MediaEasyMediaManagerConfigurationTest extends TestCase
{
    public function testEasyMediaManagerIsDisabledByDefault(): void
    {
        $container = $this->load([]);

        self::assertFalse($container->getParameter('sylius_happy_cms.media.easy_media_manager.enabled'));
        self::assertSame(['enabled' => false, 'theme' => [], 'features' => []], $container->getParameter('sylius_happy_cms.media.easy_media_manager'));
        self::assertSame('%sylius_happy_cms.media.easy_media_manager.enabled%', $container->getDefinition('Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage')->getArgument('$easyMediaManager'));
    }

    public function testTheThemeAndTheFeaturesAreKeptAsGiven(): void
    {
        $theme = ['accent' => 'var(--tblr-primary)', 'mode' => 'light', 'tokens' => ['accent-700' => '#0b5e4e', 'surface' => '#fff']];
        $container = $this->load(['media' => ['easy_media_manager' => [
            'enabled' => true,
            'theme' => $theme,
            'features' => ['enableDelete' => false, 'enableGeneratingAlts' => true],
        ]]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.media.easy_media_manager.enabled'));
        self::assertSame(
            ['enabled' => true, 'theme' => $theme, 'features' => ['enableDelete' => false, 'enableGeneratingAlts' => true]],
            $container->getParameter('sylius_happy_cms.media.easy_media_manager'),
        );
    }

    public function testTheThemeMustBeAMap(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The easy-media-manager theme must be a map of options');

        $this->load(['media' => ['easy_media_manager' => ['theme' => 'var(--tblr-primary)']]]);
    }

    public function testNoAdminHookIsAddedWhenTheOptionIsOff(): void
    {
        $container = $this->prepend([]);

        self::assertSame([], $container->getExtensionConfig('sylius_twig_hooks'));
        $globals = $this->twigGlobals($container);
        self::assertFalse($globals['media']['easy_media_manager']['enabled']);
    }

    public function testTheConfigurationIsRenderedOnEveryAdminPageWhenTheOptionIsOn(): void
    {
        $container = $this->prepend(['media' => ['easy_media_manager' => ['enabled' => true, 'theme' => ['accent' => '#22b99a']]]]);

        self::assertSame([[
            'hooks' => [
                'sylius_admin.base#javascripts' => [
                    SyliusHappyCMSExtension::EASY_MEDIA_CONFIG_HOOKABLE => [
                        'template' => '@SyliusHappyCMSPlugin/media/easy_media/config.html.twig',
                        'priority' => -100,
                    ],
                ],
            ],
        ]], $container->getExtensionConfig('sylius_twig_hooks'));

        $globals = $this->twigGlobals($container);
        self::assertTrue($globals['media']['easy_media_manager']['enabled']);
        self::assertSame(['accent' => '#22b99a'], $globals['media']['easy_media_manager']['theme']);
    }

    /**
     * @param array<string, mixed> $projectConfig
     */
    private function load(array $projectConfig): ContainerBuilder
    {
        $pluginConfig = Yaml::parseFile(__DIR__ . '/../../../config/packages/sylius_happy_cms.yaml');
        self::assertIsArray($pluginConfig);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        (new SyliusHappyCMSExtension())->load([$pluginConfig['sylius_happy_cms'], $projectConfig], $container);

        return $container;
    }

    /**
     * @param array<string, mixed> $projectConfig
     */
    private function prepend(array $projectConfig): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->registerExtension(new class() extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'sylius_twig_hooks';
            }
        });
        $extension = new SyliusHappyCMSExtension();
        $container->registerExtension($extension);
        $container->loadFromExtension('sylius_happy_cms', $projectConfig);

        $extension->prepend($container);

        return $container;
    }

    /**
     * @return array{media: array{easy_media_manager: array{enabled: bool, theme: array<string, mixed>}}}
     */
    private function twigGlobals(ContainerBuilder $container): array
    {
        foreach ($container->getExtensionConfig('twig') as $config) {
            if (isset($config['globals']['sylius_happy_cms'])) {
                /** @var array{media: array{easy_media_manager: array{enabled: bool, theme: array<string, mixed>}}} $globals */
                $globals = $config['globals']['sylius_happy_cms'];

                return $globals;
            }
        }

        self::fail('No sylius_happy_cms Twig global prepended.');
    }
}
