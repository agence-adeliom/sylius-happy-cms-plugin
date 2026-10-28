<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\Admin\Cmf\RedirectRouteAdminInterface;
use Adeliom\SyliusHappyCMSPlugin\Admin\Cmf\RouteAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Cmf\RouteAdminInterface;
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
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RedirectRouteRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepositoryInterface;
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

            ->arrayNode('page_builder')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('preview_template')
            ->defaultValue(null)
            ->info('Custom template to use for page builder preview. If not set, the default page template will be used.')
            ->end()
            ->scalarNode('ai_generate_content_prompt')
            ->defaultValue(null)
            ->info('Custom system prompt for AI content generation. If not set, a default prompt will be used.')
            ->end()
            ->end()
            ->end()

            ->arrayNode('seo')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enable_twig_extension')
            ->defaultTrue()
            ->info('Register the seo_metas(), seo_title() and seo_breadcrumb() Twig functions and the happy_cms_seo global. Disable it when your project defines its own SEO functions.')
            ->end()
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
            ->arrayNode('templates')
            ->addDefaultsIfNotSet()
            ->info('Templates rendered by seo_metas() and seo_breadcrumb(). They receive the SEO object (metas) or the breadcrumb items (breadcrumb) as "data".')
            ->children()
            ->scalarNode('metas')->cannotBeEmpty()->defaultValue('@SyliusHappyCMSPlugin/front/seo/block-metas.html.twig')->end()
            ->scalarNode('breadcrumb')->cannotBeEmpty()->defaultValue('@SyliusHappyCMSPlugin/front/seo/block-breadcrumb.html.twig')->end()
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
            ->scalarNode('admin_path')
            ->defaultValue('happy-cms-media/medias')
            ->info('Path of the media manager routes (media.*), below the prefix of their import: "medias" serves the media manager on /admin/medias/ with the default imports. Stored without leading and trailing "/".')
            ->beforeNormalization()
            ->ifString()
            ->then(static fn (string $value): string => trim($value, '/'))
            ->end()
            ->validate()
            ->ifTrue(static fn ($value): bool => !is_string($value) || 1 !== preg_match('#^[\w\-]+(/[\w\-]+)*$#', $value))
            ->thenInvalid('The media manager path must be made of path segments such as "medias" or "cms/medias", %s given.')
            ->end()
            ->end()
            ->scalarNode('media_entity')
            ->defaultValue(Media::class)
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
            ->arrayNode('image')->scalarPrototype()->end()->defaultValue(['binary/octet-stream'])->end()
            ->arrayNode('archive')->scalarPrototype()->end()->defaultValue(['application/x-tar', 'application/zip'])->end()
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
            // Security: File size limits
            ->integerNode('max_file_size')
            ->defaultValue(0)
            ->min(0)
            ->info('Maximum file size in MB (0 = no limit, uses PHP upload_max_filesize)')
            ->end()
            ->integerNode('max_total_upload_size')
            ->defaultValue(0)
            ->min(0)
            ->info('Maximum total upload size for multiple files in MB (0 = no limit)')
            ->end()
            ->integerNode('max_files_per_upload')
            ->defaultValue(0)
            ->min(0)
            ->info('Maximum number of files per upload request (0 = no limit)')
            ->end()
            // Security: Rate limiting
            ->arrayNode('rate_limiting')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')
            ->defaultFalse()
            ->info('Enable rate limiting for upload requests')
            ->end()
            ->integerNode('max_requests')
            ->defaultValue(60)
            ->min(1)
            ->info('Maximum number of upload requests per period')
            ->end()
            ->integerNode('period')
            ->defaultValue(60)
            ->min(1)
            ->info('Time period in seconds for rate limiting')
            ->end()
            ->integerNode('ban_duration')
            ->defaultValue(300)
            ->min(0)
            ->info('Ban duration in seconds after exceeding limit (0 = no ban, just rate limit)')
            ->end()
            ->scalarNode('cache_pool')
            ->defaultValue('cache.app')
            ->info('Cache pool service ID for storing rate limit data')
            ->end()
            ->end()
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
            ->end()

            ->arrayNode('route')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('route_model')
            ->isRequired()
            ->validate()
            ->ifString()
            ->then(static function ($value) {
                if (!class_exists($value) || !is_a($value, RouteInterface::class, true)) {
                    throw new InvalidConfigurationException(sprintf('Route class must be a valid class extending %s. "%s" given.', RouteInterface::class, $value));
                }

                return $value;
            })
            ->end()
            ->end()
            ->scalarNode('route_repository')
            ->defaultValue(RouteRepository::class)
            ->validate()
            ->ifString()
            ->then(static function ($value) {
                if (!class_exists($value) || !is_a($value, RouteRepositoryInterface::class, true)) {
                    throw new InvalidConfigurationException(sprintf('Route repository must be a valid class extending %s. "%s" given.', RouteRepositoryInterface::class, $value));
                }

                return $value;
            })
            ->end()
            ->end()
            ->scalarNode('route_admin')
            ->defaultValue(RouteAdmin::class)
            ->validate()
            ->ifString()
            ->then(static function ($value) {
                if (!class_exists($value) || !is_a($value, RouteAdminInterface::class, true)) {
                    throw new InvalidConfigurationException(sprintf('Route admin must be a valid class extending %s. "%s" given.', RouteAdminInterface::class, $value));
                }

                return $value;
            })
            ->end()
            ->end()
            ->end()
            ->end()

            ->arrayNode('redirect_route')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enable_redirection')
            ->defaultTrue()
            ->info('On each 404, redirect to the matching redirect route when one is configured (target URI or route). Disable it when your project handles redirections itself.')
            ->end()
            ->booleanNode('enable_not_found_recording')
            ->defaultTrue()
            ->info('On each 404, record the unknown URL as a new redirect route (one row per URL and host) to configure from the admin. Disable it to keep the redirect_route table free of crawler and typo URLs.')
            ->end()
            ->scalarNode('redirect_route_model')
            ->isRequired()
            ->validate()
            ->ifString()
            ->then(static function ($value) {
                if (!class_exists($value) || !is_a($value, RedirectRouteInterface::class, true)) {
                    throw new InvalidConfigurationException(sprintf('Redirect Route class must be a valid class extending %s. "%s" given.', RedirectRouteInterface::class, $value));
                }

                return $value;
            })
            ->end()
            ->end()
            ->scalarNode('redirect_route_repository')
            ->defaultValue(RouteRepository::class)
            ->validate()
            ->ifString()
            ->then(static function ($value) {
                if (!class_exists($value) || !is_a($value, RedirectRouteRepositoryInterface::class, true)) {
                    throw new InvalidConfigurationException(sprintf('Redirect Route repository must be a valid class extending %s. "%s" given.', RedirectRouteRepositoryInterface::class, $value));
                }

                return $value;
            })
            ->end()
            ->end()
            ->scalarNode('redirect_route_admin')
            ->defaultValue(RouteAdmin::class)
            ->validate()
            ->ifString()
            ->then(static function ($value) {
                if (!class_exists($value) || !is_a($value, RedirectRouteAdminInterface::class, true)) {
                    throw new InvalidConfigurationException(sprintf('Redirect Route admin must be a valid class extending %s. "%s" given.', RedirectRouteAdminInterface::class, $value));
                }

                return $value;
            })
            ->end()
            ->end()
            ->end()
            ->end()

            ->arrayNode('routing')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('url_pattern')
            ->defaultValue(EntityRouteIndexer::DEFAULT_URL_PATTERN)
            ->info('Path of the CMS routes. Placeholders: {{locale}}, {{other_entity_path}} (parent of another type), {{current_entity_path}} (required). Remove {{locale}} for URLs without locale prefix.')
            ->example('{{other_entity_path}}{{current_entity_path}}')
            ->validate()
            ->ifTrue(static fn (mixed $value): bool => !is_string($value) || !str_contains($value, '{{current_entity_path}}'))
            ->thenInvalid('The URL pattern must contain the "{{current_entity_path}}" placeholder, %s given.')
            ->end()
            ->end()
            ->booleanNode('collapse_slashes')
            ->defaultNull()
            ->info('true: collapse the repeated slashes of a computed route path ("/en_US//about" becomes "/en_US/about", an empty placeholder leaves no double slash). Not set or false: the path is kept as filled (deprecated). In 3.0 the option is removed and collapsing is the only behaviour.')
            ->end()
            ->booleanNode('trailing_slash')
            ->defaultFalse()
            ->info('End the CMS routes with "/" and redirect (301) the same URL without the final slash to them.')
            ->end()
            ->booleanNode('index_on_flush')
            ->defaultNull()
            ->info('true: compute the routes within the flush that saves the entity (onFlush, after the slug listeners), without nested flush. Not set or false: the routes are computed in postPersist / postUpdate and written by a nested flush (deprecated). In 3.0 the option is removed and indexing within the flush is the only behaviour.')
            ->end()
            ->booleanNode('child_routes_prefix_boundary')
            ->defaultNull()
            ->info('true: when the path of an entity changes, rewrite only the stored routes below it at a "/" boundary ("/door" moves "/door/wooden", not "/doors"), replacing the leading prefix only. Not set or false: every route starting with the previous path is rewritten (deprecated). In 3.0 the option is removed and the "/" boundary is the only behaviour.')
            ->end()
            ->booleanNode('rewrite_child_routes')
            ->defaultTrue()
            ->info('When the path of an entity changes, move the stored routes below its previous path ("/door/…" when "/door" becomes "/gate"). Disable it when a path does not identify a single parent: two contents of different types with the same slug, whose children share the same prefix.')
            ->end()
            ->end()
            ->end()

            ->end();

        return $treeBuilder;
    }
}
