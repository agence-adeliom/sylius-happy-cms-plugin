<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MediaDirectoryCheckConfigurationTest extends TestCase
{
    public function testTheDirectoryCheckDefaultsToThePublicDirectory(): void
    {
        self::assertSame('public_dir', $this->load([])->getParameter('sylius_happy_cms.media.directory_check'));
    }

    public function testTheDirectoryCheckCanBeTheStorageOrTheDatabase(): void
    {
        self::assertSame('storage', $this->load(['directory_check' => 'storage'])->getParameter('sylius_happy_cms.media.directory_check'));
        self::assertSame('database', $this->load(['directory_check' => 'database'])->getParameter('sylius_happy_cms.media.directory_check'));
    }

    public function testAnUnknownDirectoryCheckIsRefused(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load(['directory_check' => 'cdn']);
    }

    /**
     * @param array<string, mixed> $media
     */
    private function load(array $media): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        (new SyliusHappyCMSExtension())->load([['media' => $media]], $container);

        return $container;
    }
}
