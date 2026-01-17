<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap\SitemapDumperInterface;
use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SyliusHappyCMSExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /** @psalm-suppress UnusedVariable */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->processPageConfiguration($config['page'], $container);
        $this->processPageBuilderConfiguration($config['page_builder'], $container);
        $this->processSeoConfiguration($config['seo'], $container);
        $this->processConfigConfiguration($config['config'], $container);
        $this->processMenuConfiguration($config['menu'], $container);
        $this->processBlockConfiguration($config['shared_block'], $container);
        $this->processMediaConfiguration($config['media'], $container);

        $container->registerForAutoconfiguration(BlockTypeInterface::class)
            ->addTag('sylius.happy_cms.block')
        ;

        $container->registerForAutoconfiguration(SharedBlockTypeInterface::class)
            ->addTag('sylius.happy_cms.shared_block')
        ;

        $container->registerForAutoconfiguration(SitemapDumperInterface::class)
            ->addTag('sylius_happy_cms.seo.sitemap_dumpable')
        ;

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config/media'));
        $loader->load('services.xml');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processPageConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $value) {
            /** @phpstan-ignore-next-line */
            $container->setParameter('sylius_happy_cms.page.' . $key, $value);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processPageBuilderConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $value) {
            /** @phpstan-ignore-next-line */
            $container->setParameter('sylius_happy_cms.page_builder.' . $key, $value);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processSeoConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $value) {
            /** @phpstan-ignore-next-line */
            $container->setParameter('sylius_happy_cms.seo.' . $key, $value);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processConfigConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $value) {
            /** @phpstan-ignore-next-line */
            $container->setParameter('sylius_happy_cms.config.' . $key, $value);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processMenuConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $type => $class) {
                    /** @phpstan-ignore-next-line */
                    $container->setParameter(sprintf('sylius_happy_cms.menu.%s.%s', $key, $type), $class);
                }
            }
            /** @phpstan-ignore-next-line */
            $container->setParameter(sprintf('sylius_happy_cms.menu.%s', $key), $value);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processBlockConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $type => $class) {
                    /** @phpstan-ignore-next-line */
                    $container->setParameter(sprintf('sylius_happy_cms.shared_block.%s.%s', $key, $type), $class);
                }
            }
            /** @phpstan-ignore-next-line */
            $container->setParameter(sprintf('sylius_happy_cms.shared_block.%s', $key), $value);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processMediaConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $value) {
            /** @phpstan-ignore-next-line */
            $container->setParameter('sylius_happy_cms.media.' . $key, $value);
        }

        $storageName = $container->getParameter('sylius_happy_cms.media.storage_name');
        if (is_string($storageName) || $storageName instanceof Alias) {
            $container->setAlias('happy.cms.media.storage', $storageName);
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);

        $configs = $container->getExtensionConfig('media');
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $config = $config['media'];

        $container->prependExtensionConfig('media', $config);
        $twigConfig = [];
        $twigConfig['paths'][__DIR__ . '/../../templates/media'] = 'media';
        $twigConfig['globals']['sylius_happy_cms'] = [];
        $twigConfig['globals']['sylius_happy_cms']['media'] = [];
        foreach ($config as $k => $v) {
            $twigConfig['globals']['sylius_happy_cms']['media'][$k] = $v;
        }

        $container->prependExtensionConfig('twig', $twigConfig);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'DoctrineMigrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@SyliusHappyCMSPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }

    public function getAlias(): string
    {
        return 'sylius_happy_cms';
    }
}
