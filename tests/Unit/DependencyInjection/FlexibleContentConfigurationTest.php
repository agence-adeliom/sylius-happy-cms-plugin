<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Form\Block\BlockCollectionType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final class FlexibleContentConfigurationTest extends TestCase
{
    public function testNotSettingSaveAsListKeepsTheLegacySaveAndIsDeprecated(): void
    {
        [$container, $deprecations] = $this->load([]);

        self::assertFalse($container->getParameter('sylius_happy_cms.flexible_content.save_as_list'));
        self::assertSame(
            ['Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Not setting the "sylius_happy_cms.flexible_content.save_as_list" option is deprecated. It will be removed in 3.0, where flexible contents are always saved as a JSON list in display order. Set it to true to opt in now (see UPGRADE.md).'],
            $deprecations,
        );
    }

    public function testSettingSaveAsListToFalseKeepsTheLegacySaveAndIsDeprecated(): void
    {
        [$container, $deprecations] = $this->load(['flexible_content' => ['save_as_list' => false]]);

        self::assertFalse($container->getParameter('sylius_happy_cms.flexible_content.save_as_list'));
        self::assertCount(1, $deprecations);
        self::assertStringStartsWith('Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Setting the "sylius_happy_cms.flexible_content.save_as_list" option to false is deprecated.', $deprecations[0]);
    }

    public function testSaveAsListCanBeEnabledWithoutDeprecation(): void
    {
        [$container, $deprecations] = $this->load(['flexible_content' => ['save_as_list' => true]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.flexible_content.save_as_list'));
        self::assertSame([], $deprecations);
    }

    public function testTheBlockCollectionTypeReceivesTheOption(): void
    {
        [$container] = $this->load(['flexible_content' => ['save_as_list' => true]]);

        $definition = $container->getDefinition(BlockCollectionType::class);
        self::assertSame('%sylius_happy_cms.flexible_content.save_as_list%', $definition->getArgument('$saveAsList'));
        self::assertTrue($definition->hasTag('form.type'));
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
            if (str_contains($message, 'flexible_content')) {
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
