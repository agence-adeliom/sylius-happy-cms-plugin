<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap\SitemapDumperInterface;
use Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension;
use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SyliusHappyCMSExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public const NOT_FOUND_LISTENER = 'sylius.happy_cms.exception_listener';

    public const REDIRECTION_MANAGER = 'sylius.happy_cms.plugin.listener.redirection_manager';

    /** @psalm-suppress UnusedVariable */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        /** @var array<string, array<string, mixed>> $config */
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

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config/media'));
        $loader->load('services.php');

        $this->disableOptionalServices($config, $container);
    }

    /**
     * @param array<string, array<string, mixed>> $config
     */
    private function disableOptionalServices(array $config, ContainerBuilder $container): void
    {
        if (false === ($config['seo']['enable_twig_extension'] ?? true)) {
            $container->getDefinition(SeoExtension::class)->clearTag('twig.extension');
        }

        $redirection = (bool) ($config['redirect_route']['enable_redirection'] ?? true);
        $recording = (bool) ($config['redirect_route']['enable_not_found_recording'] ?? true);

        if (!$redirection && !$recording) {
            // Nothing left to do on a 404: do not even register the listener.
            $container->removeDefinition(self::NOT_FOUND_LISTENER);

            return;
        }

        $container->getDefinition(self::REDIRECTION_MANAGER)
            ->setArgument('$redirect', $redirection)
            ->setArgument('$recordNotFound', $recording);
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

        $twigGlobalFromConfig = $this->getMediaTwigGlobalFromConfig($container);
        if (true === $twigGlobalFromConfig) {
            $mediaGlobals = $this->getMediaConfiguration($container);
        } else {
            trigger_deprecation(
                'agence-adeliom/sylius-happy-cms-plugin',
                '2.3.3',
                (null === $twigGlobalFromConfig ? 'Not setting the "%s" option is deprecated.' : 'Setting the "%s" option to false is deprecated.') . ' It will be removed in 3.0, where the "sylius_happy_cms.media" Twig global holding the media configuration of the application is the only behaviour. Set it to true to opt in now (see UPGRADE.md).',
                'sylius_happy_cms.media.twig_global_from_config',
            );

            // Legacy: the configuration of the unknown "media" extension, i.e. the plugin defaults.
            $configs = $container->getExtensionConfig('media');
            $configuration = $this->getConfiguration($configs, $container);
            /** @var array<string, array<string, mixed>> $config */
            $config = $this->processConfiguration($configuration, $configs);
            $config = $config['media'];

            $container->prependExtensionConfig('media', $config);
            $mediaGlobals = [];
            foreach ($config as $k => $v) {
                $mediaGlobals[$k] = $v;
            }
        }
        $twigConfig = [
            'paths' => [__DIR__ . '/../../templates/media' => 'media'],
            'globals' => ['sylius_happy_cms' => ['media' => $mediaGlobals]],
        ];

        $container->prependExtensionConfig('twig', $twigConfig);
    }

    /**
     * The value of `sylius_happy_cms.media.twig_global_from_config` set by the application (the last one wins), null
     * when not set. Read before the configuration is processed, since the Twig global is prepended.
     */
    private function getMediaTwigGlobalFromConfig(ContainerBuilder $container): ?bool
    {
        $value = null;
        foreach ($container->getExtensionConfig($this->getAlias()) as $config) {
            if (isset($config['media']) && is_array($config['media']) && array_key_exists('twig_global_from_config', $config['media'])) {
                $value = $config['media']['twig_global_from_config'];
            }
        }

        if (is_string($value)) {
            try {
                $value = $container->getParameterBag()->resolveValue($value);
            } catch (\Throwable) {
                $value = null;
            }
        }

        return null === $value ? null : filter_var($value, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);
    }

    /**
     * The `sylius_happy_cms.media` configuration of the application (with the defaults), for the Twig global
     * `sylius_happy_cms.media` read by the media manager templates. Parameters (`%front_url%`) are resolved
     * later, with the Twig configuration.
     *
     * @return array<string, mixed>
     */
    private function getMediaConfiguration(ContainerBuilder $container): array
    {
        $configs = [];
        foreach ($container->getExtensionConfig($this->getAlias()) as $config) {
            if (isset($config['media']) && is_array($config['media'])) {
                // Already read by getMediaTwigGlobalFromConfig(), possibly as a parameter.
                unset($config['media']['twig_global_from_config']);
                $configs[] = ['media' => $config['media']];
            }
        }

        try {
            /** @var array{media: array<string, mixed>} $processed */
            $processed = $this->processConfiguration(new Configuration(), $configs);
        } catch (InvalidConfigurationException) {
            // A value only valid once resolved (environment variable in a constrained node): the defaults are
            // exposed, the services still use the resolved configuration.
            /** @var array{media: array<string, mixed>} $processed */
            $processed = $this->processConfiguration(new Configuration(), []);
        }

        return $processed['media'];
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
