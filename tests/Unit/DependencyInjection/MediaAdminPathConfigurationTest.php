<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class MediaAdminPathConfigurationTest extends TestCase
{
    public function testTheMediaManagerPathDefaultsToTheHistoricalPath(): void
    {
        self::assertSame('happy-cms-media/medias', $this->adminPath([]));
    }

    public function testTheMediaManagerPathCanBeChanged(): void
    {
        self::assertSame('mediatheque', $this->adminPath(['media' => ['admin_path' => 'mediatheque']]));
        self::assertSame('cms/medias', $this->adminPath(['media' => ['admin_path' => 'cms/medias']]));
    }

    /**
     * Leading and trailing slashes are removed: the routes add the separator themselves.
     */
    public function testTheSlashesAroundTheMediaManagerPathAreRemoved(): void
    {
        self::assertSame('mediatheque', $this->adminPath(['media' => ['admin_path' => '/mediatheque']]));
        self::assertSame('cms/medias', $this->adminPath(['media' => ['admin_path' => '/cms/medias/']]));
    }

    #[DataProvider('invalidPaths')]
    public function testAnInvalidMediaManagerPathIsRefused(string $path): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['media' => ['admin_path' => $path]]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPaths(): iterable
    {
        yield 'empty' => [''];
        yield 'root' => ['/'];
        yield 'empty segment' => ['cms//medias'];
        yield 'with a placeholder' => ['/{folder}'];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function adminPath(array $config): string
    {
        /** @var array{media: array{admin_path: string}} $processed */
        $processed = $this->process($config);

        return $processed['media']['admin_path'];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        /** @var array<string, mixed> $processed */
        $processed = (new Processor())->processConfiguration(new Configuration(), [$config]);

        return $processed;
    }
}
