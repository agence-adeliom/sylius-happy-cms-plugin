<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Media\LongSlugsListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final class MediaLongSlugsConfigurationTest extends TestCase
{
    public function testNotSettingTheOptionKeepsThe100CharacterSlugsAndIsDeprecated(): void
    {
        [$container, $deprecations] = $this->load([]);

        self::assertFalse($container->getParameter('sylius_happy_cms.media.long_slugs'));
        self::assertFalse($container->hasDefinition(SyliusHappyCMSExtension::LONG_SLUGS_LISTENER));
        self::assertSame(['Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Not setting the "sylius_happy_cms.media.long_slugs" option is deprecated. It will be removed in 3.0, where a 255-character slug for medias and folders is the only behaviour. Set it to true to opt in now (see UPGRADE.md).'], $deprecations);
    }

    public function testSettingTheOptionToFalseKeepsThe100CharacterSlugsAndIsDeprecated(): void
    {
        [$container, $deprecations] = $this->load(['media' => ['long_slugs' => false]]);

        self::assertFalse($container->getParameter('sylius_happy_cms.media.long_slugs'));
        self::assertFalse($container->hasDefinition(SyliusHappyCMSExtension::LONG_SLUGS_LISTENER));
        self::assertSame(['Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Setting the "sylius_happy_cms.media.long_slugs" option to false is deprecated. It will be removed in 3.0, where a 255-character slug for medias and folders is the only behaviour. Set it to true to opt in now (see UPGRADE.md).'], $deprecations);
    }

    public function testOptingInRegistersTheListenerWithoutDeprecation(): void
    {
        [$container, $deprecations] = $this->load(['media' => ['long_slugs' => true]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.media.long_slugs'));
        $listener = $container->getDefinition(SyliusHappyCMSExtension::LONG_SLUGS_LISTENER);
        self::assertSame(LongSlugsListener::class, $listener->getClass());
        self::assertSame([['event' => 'loadClassMetadata']], $listener->getTag('doctrine.event_listener'));
        self::assertSame([], $deprecations);
    }

    /**
     * @param array<string, mixed> $projectConfig
     *
     * @return array{ContainerBuilder, list<string>}
     */
    private function load(array $projectConfig): array
    {
        $pluginConfig = Yaml::parseFile(__DIR__ . '/../../../config/packages/sylius_happy_cms.yaml');
        self::assertIsArray($pluginConfig);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            if (str_contains($message, 'sylius_happy_cms.media.long_slugs')) {
                $deprecations[] = $message;
            }

            return true;
        }, \E_USER_DEPRECATED);

        try {
            (new SyliusHappyCMSExtension())->load([$pluginConfig['sylius_happy_cms'], $projectConfig], $container);
        } finally {
            restore_error_handler();
        }

        return [$container, $deprecations];
    }
}
