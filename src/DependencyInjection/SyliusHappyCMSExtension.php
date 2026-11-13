<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\EventListener\Media\LongSlugsListener;
use Adeliom\SyliusHappyCMSPlugin\EventListener\TrailingSlashRedirectListener;
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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class SyliusHappyCMSExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public const NOT_FOUND_LISTENER = 'sylius.happy_cms.exception_listener';

    public const REDIRECTION_MANAGER = 'sylius.happy_cms.plugin.listener.redirection_manager';

    public const LONG_SLUGS_LISTENER = 'happy.cms.media.event_listener.long_slugs';

    /** @psalm-suppress UnusedVariable */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        /** @var array<string, array<string, mixed>> $config */
        $config = $this->processConfiguration($configuration, $configs);

        $this->processPageConfiguration($config['page'], $container);
        $this->processRoutingConfiguration($config['routing'], $container);
        $this->processPageBuilderConfiguration($config['page_builder'], $container);
        $this->processBlockRenderConfiguration($config['block_render'], $container);
        $this->processSeoConfiguration($config['seo'], $container);
        $this->processConfigConfiguration($config['config'], $container);
        $this->processMenuConfiguration($config['menu'], $container);
        $this->processBlockConfiguration($config['shared_block'], $container);
        $this->processFlexibleContentConfiguration($config['flexible_content'], $container);
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
    private function processRoutingConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $value) {
            /** @phpstan-ignore-next-line */
            $container->setParameter('sylius_happy_cms.routing.' . $key, $value);
        }

        $container->setParameter('sylius_happy_cms.routing.collapse_slashes', $this->resolveRoutingFlag(
            $config['collapse_slashes'] ?? null,
            'collapse_slashes',
            'collapsing the repeated slashes of a computed route path',
        ));
        $container->setParameter('sylius_happy_cms.routing.recompute_child_routes', $this->resolveRoutingFlag(
            $config['recompute_child_routes'] ?? null,
            'recompute_child_routes',
            'recomputing the routes below a renamed path from their content',
        ));

        if (true === $config['trailing_slash']) {
            $container->register(TrailingSlashRedirectListener::class, TrailingSlashRedirectListener::class)
                ->setArguments([new Reference('cmf_routing.dynamic_router'), new Reference('cmf_routing.orm_content_repository', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
                ->addTag('kernel.event_listener', ['event' => 'kernel.exception', 'priority' => TrailingSlashRedirectListener::PRIORITY])
            ;
        }
    }

    /**
     * Compatibility flag of the "sylius_happy_cms.routing" section: null (not set) or false keep the current behaviour
     * and trigger a deprecation, true opts in the behaviour of 2.4.
     */
    private function resolveRoutingFlag(mixed $value, string $option, string $newBehaviour): bool
    {
        if (true === $value) {
            return true;
        }

        trigger_deprecation(
            'agence-adeliom/sylius-happy-cms-plugin',
            '2.3.3',
            sprintf(
                '%s the "sylius_happy_cms.routing.%s" option %sis deprecated. It will be removed in 2.4, where %s is the only behaviour. Set it to true to opt in now (see UPGRADE.md).',
                null === $value ? 'Not setting' : 'Setting',
                $option,
                null === $value ? '' : 'to false ',
                $newBehaviour,
            ),
        );

        return false;
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
    private function processBlockRenderConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $value) {
            /** @phpstan-ignore-next-line */
            $container->setParameter('sylius_happy_cms.block_render.' . $key, $value);
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
    private function processFlexibleContentConfiguration(array $config, ContainerBuilder $container): void
    {
        $saveAsList = $config['save_as_list'] ?? null;

        if (true !== $saveAsList) {
            trigger_deprecation(
                'agence-adeliom/sylius-happy-cms-plugin',
                '2.3.3',
                '%s the "sylius_happy_cms.flexible_content.save_as_list" option %s deprecated. It will be removed in 2.4, where flexible contents are always saved as a JSON list in display order. Set it to true to opt in now (see UPGRADE.md).',
                null === $saveAsList ? 'Not setting' : 'Setting',
                null === $saveAsList ? 'is' : 'to false is',
            );
        }

        $container->setParameter('sylius_happy_cms.flexible_content.save_as_list', true === $saveAsList);
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

        $longSlugs = $config['long_slugs'] ?? null;
        if (null === $longSlugs) {
            trigger_deprecation('agence-adeliom/sylius-happy-cms-plugin', '2.3.3', 'Not setting the "sylius_happy_cms.media.long_slugs" option is deprecated. It will be removed in 2.4, where a 255-character slug for medias and folders is the only behaviour. Set it to true to opt in now (see UPGRADE.md).');
        } elseif (false === $longSlugs) {
            trigger_deprecation('agence-adeliom/sylius-happy-cms-plugin', '2.3.3', 'Setting the "sylius_happy_cms.media.long_slugs" option to false is deprecated. It will be removed in 2.4, where a 255-character slug for medias and folders is the only behaviour. Set it to true to opt in now (see UPGRADE.md).');
        }
        $container->setParameter('sylius_happy_cms.media.long_slugs', true === $longSlugs);

        if (true === $longSlugs) {
            // Default mapping (100) untouched: the listener widens the slugs only for the projects that opt in.
            $container->register(self::LONG_SLUGS_LISTENER, LongSlugsListener::class)
                ->addTag('doctrine.event_listener', ['event' => 'loadClassMetadata']);
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);

        $mediaGlobals = $this->getMediaConfiguration($container);
        $twigConfig = [
            'paths' => [__DIR__ . '/../../templates/media' => 'media'],
            'globals' => ['sylius_happy_cms' => ['media' => $mediaGlobals]],
        ];

        $container->prependExtensionConfig('twig', $twigConfig);
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
