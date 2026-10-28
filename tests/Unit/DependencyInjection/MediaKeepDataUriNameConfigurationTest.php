<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MediaKeepDataUriNameConfigurationTest extends TestCase
{
    public function testTheFlagIsNotSetByDefaultAndTriggersADeprecation(): void
    {
        [$container, $deprecations] = $this->load([]);

        self::assertNull($container->getParameter('sylius_happy_cms.media.keep_data_uri_name'));
        self::assertCount(1, $deprecations);
        self::assertStringContainsString('Not setting the "sylius_happy_cms.media.keep_data_uri_name" option is deprecated.', $deprecations[0]);
    }

    public function testTheFlagSetToFalseTriggersADeprecation(): void
    {
        [$container, $deprecations] = $this->load(['keep_data_uri_name' => false]);

        self::assertFalse($container->getParameter('sylius_happy_cms.media.keep_data_uri_name'));
        self::assertCount(1, $deprecations);
        self::assertStringContainsString('Setting the "sylius_happy_cms.media.keep_data_uri_name" option to false is deprecated.', $deprecations[0]);
    }

    public function testTheFlagSetToTrueTriggersNoDeprecation(): void
    {
        [$container, $deprecations] = $this->load(['keep_data_uri_name' => true]);

        self::assertTrue($container->getParameter('sylius_happy_cms.media.keep_data_uri_name'));
        self::assertSame([], $deprecations);
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
            if (str_contains($message, 'keep_data_uri_name')) {
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
