<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MediaRawMetaValuesConfigurationTest extends TestCase
{
    public function testTheFlagIsNotSetByDefaultAndTriggersADeprecation(): void
    {
        [$container, $deprecations] = $this->load([]);

        self::assertFalse($container->getParameter('sylius_happy_cms.media.raw_meta_values'));
        self::assertCount(1, $deprecations);
        self::assertStringContainsString('Not setting the "sylius_happy_cms.media.raw_meta_values" option is deprecated.', $deprecations[0]);
    }

    public function testTheFlagSetToFalseTriggersADeprecation(): void
    {
        [$container, $deprecations] = $this->load(['raw_meta_values' => false]);

        self::assertFalse($container->getParameter('sylius_happy_cms.media.raw_meta_values'));
        self::assertCount(1, $deprecations);
        self::assertStringContainsString('Setting the "sylius_happy_cms.media.raw_meta_values" option to false is deprecated.', $deprecations[0]);
    }

    public function testTheFlagSetToTrueTriggersNoDeprecation(): void
    {
        [$container, $deprecations] = $this->load(['raw_meta_values' => true]);

        self::assertTrue($container->getParameter('sylius_happy_cms.media.raw_meta_values'));
        self::assertSame([], $deprecations);
    }

    public function testTheTwigExtensionReceivesTheFlag(): void
    {
        [$container] = $this->load([]);

        self::assertSame(
            '%sylius_happy_cms.media.raw_meta_values%',
            $container->getDefinition('happy.cms.media.twig.happy.cms.media_extension')->getArgument('$rawMetaValues'),
        );
    }

    /**
     * @param array<string, mixed> $media
     *
     * @return array{ContainerBuilder, list<string>}
     */
    private function load(array $media): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            if (str_contains($message, 'raw_meta_values')) {
                $deprecations[] = $message;
            }

            return true;
        }, \E_USER_DEPRECATED);

        try {
            (new SyliusHappyCMSExtension())->load([['media' => $media]], $container);
        } finally {
            restore_error_handler();
        }

        return [$container, $deprecations];
    }
}
