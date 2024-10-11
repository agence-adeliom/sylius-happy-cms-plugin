<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\Admin\Config\ConfigAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Config\ConfigAdminInterface;
use Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuAdminInterface;
use Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuItemAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuItemAdminInterface;
use Adeliom\SyliusHappyCMSPlugin\Admin\Page\PageAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Page\PageAdminInterface;
use Adeliom\SyliusHappyCMSPlugin\Admin\SharedBlock\SharedBlockAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\SharedBlock\SharedBlockAdminInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\SharedBlock\SharedBlockRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\SharedBlock\SharedBlockRepositoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sylius_happy_cms');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('page')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('page_model')
                            ->isRequired()
                            ->validate()
                                ->ifString()
                                ->then(function ($value) {
                                    if (!class_exists($value) || !is_a($value, PageInterface::class, true)) {
                                        throw new InvalidConfigurationException(sprintf('Page class must be a valid class extending %s. "%s" given.', PageInterface::class, $value));
                                    }

                                    return $value;
                                })
                            ->end()
                        ->end()

                        ->scalarNode('page_repository')
                            ->defaultValue(PageRepository::class)
                            ->validate()
                                ->ifString()
                                ->then(function ($value) {
                                    if (!class_exists($value) || !is_a($value, PageRepositoryInterface::class, true)) {
                                        throw new InvalidConfigurationException(sprintf('Page repository must be a valid class extending %s. "%s" given.', PageRepositoryInterface::class, $value));
                                    }

                                    return $value;
                                })
                            ->end()
                        ->end()

                        ->scalarNode('page_admin')
                            ->defaultValue(PageAdmin::class)
                            ->validate()
                                ->ifString()
                                ->then(function ($value) {
                                    if (!class_exists($value) || !is_a($value, PageAdminInterface::class, true)) {
                                        throw new InvalidConfigurationException(sprintf('Page repository must be a valid class extending %s. "%s" given.', PageAdminInterface::class, $value));
                                    }

                                    return $value;
                                })
                            ->end()
                        ->end()

                        ->booleanNode('sitemap')
                            ->defaultValue(true)
                        ->end()

                    ->end()
                ->end()

                ->arrayNode('seo')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('enable_profiler')->defaultValue('%kernel.debug%')->end()
                        ->arrayNode('ignore_profiler')
                            ->defaultValue([
                                   '^/admin*',
                               ])->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('title')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('separator')->defaultValue('|')->end()
                                ->scalarNode('suffix')->defaultValue('')->end()
                            ->end()
                        ->end()
                        ->arrayNode('breadcrumbs')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->defaultValue('breadcrumb')->end()
                                ->scalarNode('item_class')->defaultValue('breadcrumb-item')->end()
                                ->scalarNode('link_class')->defaultValue('')->end()
                                ->scalarNode('current_class')->defaultValue('active')->end()
                                ->scalarNode('separator')->defaultValue('>')->end()
                                ->scalarNode('separator_class')->defaultValue('breadcrumb-separator')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('config')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('config_model')
                            ->isRequired()
                            ->validate()
                                ->ifString()
                                ->then(static function ($value) {
                                    if (!class_exists($value) || !is_a($value, ConfigInterface::class, true)) {
                                        throw new InvalidConfigurationException(sprintf('Config class must be a valid class extending %s. "%s" given.', ConfigInterface::class, $value));
                                    }

                                    return $value;
                                })
                            ->end()
                        ->end()
                        ->scalarNode('config_repository')
                            ->defaultValue(ConfigRepository::class)
                            ->validate()
                                ->ifString()
                                ->then(static function ($value) {
                                    if (!class_exists($value) || !is_a($value, ConfigRepositoryInterface::class, true)) {
                                        throw new InvalidConfigurationException(sprintf('Config repository must be a valid class extending %s. "%s" given.', ConfigRepositoryInterface::class, $value));
                                    }

                                    return $value;
                                })
                            ->end()
                        ->end()
                        ->scalarNode('config_admin')
                            ->defaultValue(ConfigAdmin::class)
                            ->validate()
                                ->ifString()
                                ->then(static function ($value) {
                                    if (!class_exists($value) || !is_a($value, ConfigAdminInterface::class, true)) {
                                        throw new InvalidConfigurationException(sprintf('Config admin must be a valid class extending %s. "%s" given.', ConfigAdminInterface::class, $value));
                                    }

                                    return $value;
                                })
                            ->end()
                        ->end()

                    ->end()
                ->end()

                ->arrayNode('menu')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('menu')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('menu_model')
                                    ->isRequired()
                                    ->validate()
                                        ->ifString()
                                        ->then(function ($value) {
                                            if (!class_exists($value) || !is_a($value, MenuInterface::class, true)) {
                                                throw new InvalidConfigurationException(sprintf('Entry class must be a valid class extending %s. "%s" given.', MenuInterface::class, $value));
                                            }

                                            return $value;
                                        })
                                    ->end()
                                ->end()
                                ->scalarNode('menu_repository')
                                    ->defaultValue(MenuRepository::class)
                                    ->validate()
                                        ->ifString()
                                        ->then(function ($value) {
                                            if (!class_exists($value) || !is_a($value, MenuRepositoryInterface::class, true)) {
                                                throw new InvalidConfigurationException(sprintf('Entry repository must be a valid class extending %s. "%s" given.', MenuRepositoryInterface::class, $value));
                                            }

                                            return $value;
                                        })
                                    ->end()
                                ->end()
                                ->scalarNode('menu_admin')
                                    ->defaultValue(MenuAdmin::class)
                                    ->validate()
                                        ->ifString()
                                        ->then(static function ($value) {
                                            if (!class_exists($value) || !is_a($value, MenuAdminInterface::class, true)) {
                                                throw new InvalidConfigurationException(sprintf('Menu admin must be a valid class extending %s. "%s" given.', MenuAdminInterface::class, $value));
                                            }

                                            return $value;
                                        })
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('menu_item')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('menu_item_model')
                                    ->isRequired()
                                    ->validate()
                                        ->ifString()
                                        ->then(function ($value) {
                                            if (!class_exists($value) || !is_a($value, MenuItemInterface::class, true)) {
                                                throw new InvalidConfigurationException(sprintf('Category class must be a valid class extending %s. "%s" given.', MenuItemInterface::class, $value));
                                            }

                                            return $value;
                                        })
                                    ->end()
                                ->end()
                                ->scalarNode('menu_item_repository')
                                    ->defaultValue(MenuItemRepository::class)
                                    ->validate()
                                        ->ifString()
                                        ->then(function ($value) {
                                            if (!class_exists($value) || !is_a($value, MenuItemRepositoryInterface::class, true)) {
                                                throw new InvalidConfigurationException(sprintf('Category repository must be a valid class extending %s. "%s" given.', MenuItemRepositoryInterface::class, $value));
                                            }

                                            return $value;
                                        })
                                    ->end()
                                ->end()
                                ->scalarNode('menu_item_admin')
                                ->defaultValue(MenuItemAdmin::class)
                                    ->validate()
                                        ->ifString()
                                        ->then(static function ($value) {
                                            if (!class_exists($value) || !is_a($value, MenuItemAdminInterface::class, true)) {
                                                throw new InvalidConfigurationException(sprintf('Menu admin must be a valid class extending %s. "%s" given.', MenuItemAdminInterface::class, $value));
                                            }

                                            return $value;
                                        })
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('cache')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultFalse()->end()
                                ->integerNode('ttl')->defaultValue(300)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('media')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('storage_name')
                            ->defaultValue('default.storage')
                        ->end()
                        ->scalarNode('base_url')
                            ->defaultValue('/')
                        ->end()
                        ->scalarNode('media_entity')
                            ->defaultValue(Media::class)
                            ->isRequired()
                            ->validate()
                            ->ifString()
                            ->then(static function ($value) {
                                if (!class_exists($value) || !is_a($value, MediaInterface::class, true)) {
                                    throw new InvalidConfigurationException(sprintf('Media class must be a valid class extending %s. "%s" given.', MediaInterface::class, $value));
                                }

                                return $value;
                            })
                            ->end()
                        ->end()
                        ->scalarNode('folder_entity')
                            ->defaultValue(Folder::class)
                            ->isRequired()
                            ->validate()
                            ->ifString()
                            ->then(static function ($value) {
                                if (!class_exists($value) || !is_a($value, FolderInterface::class, true)) {
                                    throw new InvalidConfigurationException(sprintf('Media Folder class must be a valid class extending %s. "%s" given.', FolderInterface::class, $value));
                                }

                                return $value;
                            })
                            ->end()
                        ->end()
                        ->scalarNode('ignore_files')
                            ->defaultValue('/^\..*/')
                        ->end()
                        ->scalarNode('allowed_fileNames_chars')
                            ->defaultValue("\._\-\'\s\(\),")
                        ->end()
                        ->scalarNode('allowed_folderNames_chars')
                            ->defaultValue("_\-\s")
                        ->end()
                        ->arrayNode('unallowed_mimes')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                'php',
                                'java',
                            ])
                        ->end()
                        ->arrayNode('locales')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                'en_US',
                                'de_DE',
                                'fr_FR',
                                'es_ES',
                                'es_MX',
                                'pl_PL',
                                'pt_PT',
                                'zh_CN',
                            ])
                        ->end()
                        ->arrayNode('unallowed_ext')
                            ->defaultValue([
                                'php',
                                'jav',
                                'py',
                            ])
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('extended_mimes')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('image')->scalarPrototype()->end()->isRequired()->defaultValue(['binary/octet-stream'])->end()
                                ->arrayNode('archive')->scalarPrototype()->end()->isRequired()->defaultValue(['application/x-tar', 'application/zip'])->end()
                            ->end()
                        ->end()
                        ->scalarNode('sanitized_text')
                            ->defaultValue('uniqid')
                        ->end()
                        ->scalarNode('last_modified_format')
                            ->defaultValue('Y-m-d')
                        ->end()
                        ->booleanNode('hide_files_ext')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('get_folder_info')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('enable_broadcasting')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('enable_generating_alts')
                            ->defaultFalse()
                        ->end()
                        ->integerNode('pagination_amount')
                            ->defaultValue(50)
                            ->min(4)
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('shared_block')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('shared_block_model')
                            ->isRequired()
                            ->validate()
                                ->ifString()
                                ->then(static function ($value) {
                                    if (!class_exists($value) || !is_a($value, SharedBlockInterface::class, true)) {
                                        throw new InvalidConfigurationException(sprintf('Block class must be a valid class extending %s. "%s" given.', SharedBlockInterface::class, $value));
                                    }

                                    return $value;
                                })
                            ->end()
                        ->end()
                        ->scalarNode('shared_block_repository')
                            ->defaultValue(SharedBlockRepository::class)
                            ->validate()
                                ->ifString()
                                ->then(static function ($value) {
                                    if (!class_exists($value) || !is_a($value, SharedBlockRepositoryInterface::class, true)) {
                                        throw new InvalidConfigurationException(sprintf('Shared block repository must be a valid class extending %s. "%s" given.', SharedBlockRepositoryInterface::class, $value));
                                    }

                                    return $value;
                                })
                            ->end()
                        ->end()
                        ->scalarNode('shared_block_admin')
                            ->defaultValue(SharedBlockAdmin::class)
                            ->validate()
                            ->ifString()
                                ->then(static function ($value) {
                                    if (!class_exists($value) || !is_a($value, SharedBlockAdminInterface::class, true)) {
                                        throw new InvalidConfigurationException(sprintf('Shared block amin must be a valid class extending %s. "%s" given.', SharedBlockAdminInterface::class, $value));
                                    }

                                    return $value;
                                })
                            ->end()
                        ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
