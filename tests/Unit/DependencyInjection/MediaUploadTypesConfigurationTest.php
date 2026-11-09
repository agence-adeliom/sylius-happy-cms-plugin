<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\FileValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MediaUploadTypesConfigurationTest extends TestCase
{
    public function testTheDefaultsAreTheCurrentBehaviour(): void
    {
        $container = $this->load([]);

        self::assertSame(FileValidator::DEFAULT_ALLOWED_MIME_TYPES, $container->getParameter('sylius_happy_cms.media.allowed_mime_types'));
        self::assertSame(FileValidator::DEFAULT_ALLOWED_EXTENSIONS, $container->getParameter('sylius_happy_cms.media.allowed_extensions'));
        self::assertSame('content', $container->getParameter('sylius_happy_cms.media.mime_type_source'));
        self::assertFalse($container->getParameter('sylius_happy_cms.media.keep_original_extension'));
    }

    public function testTheOptionsCanBeSet(): void
    {
        $container = $this->load([
            'allowed_mime_types' => ['image/avif'],
            'allowed_extensions' => ['avif'],
            'mime_type_source' => 'client',
            'keep_original_extension' => true,
        ]);

        self::assertSame(['image/avif'], $container->getParameter('sylius_happy_cms.media.allowed_mime_types'));
        self::assertSame(['avif'], $container->getParameter('sylius_happy_cms.media.allowed_extensions'));
        self::assertSame('client', $container->getParameter('sylius_happy_cms.media.mime_type_source'));
        self::assertTrue($container->getParameter('sylius_happy_cms.media.keep_original_extension'));
    }

    public function testTheFileValidatorReceivesTheLists(): void
    {
        $definition = $this->load([])->getDefinition('happy.cms.media.file_validator');

        self::assertSame('%sylius_happy_cms.media.allowed_mime_types%', $definition->getArgument('$allowedMimeTypes'));
        self::assertSame('%sylius_happy_cms.media.allowed_extensions%', $definition->getArgument('$allowedExtensions'));
    }

    public function testAnUnknownMimeTypeSourceIsRefused(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load(['mime_type_source' => 'extension']);
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
