<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Route\CalculateRouteStaticPrefixEvent;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\ContentRepository;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Route as SymfonyRoute;

class EntityRouteIndexer
{
    public const ROUTE_PREVIEW = 'route_preview_';

    public const ROUTE_ONLINE = 'route_online_';

    public const OPTION_PREVIEW = 'preview_behavior';

    /**
     * Placeholders: {{locale}}, {{calculated_static_prefix}} (prefix computed by CalculateRouteStaticPrefixEvent, e.g.
     * the path of a parent of another type) and {{current_entity_path}} (CmsRoutableInterface::getRouteStaticPrefix()).
     */
    public const DEFAULT_URL_PATTERN = '/{{locale}}{{calculated_static_prefix}}{{current_entity_path}}';

    /**
     * The entities indexed during the current flush, so that the flushes the indexer runs itself do not index them
     * again. Emptied at the end of the flush.
     */
    public array $entitiesToManage = [];

    /** Above 0 while the indexer computes routes: the flushes it runs then are its own. */
    private int $indexingDepth = 0;

    public function __construct(
        protected ContentRepository $contentRepository,
        protected ParameterBag $parameterBag,
        protected EventDispatcherInterface $dispatcher,
        protected EntityManagerInterface $manager,
        protected string $urlPattern = self::DEFAULT_URL_PATTERN,
        protected bool $collapseSlashes = false,
    ) {
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $entity = $event->getObject();

        if ($entity instanceof TranslationInterface) {
            $entity = $entity->getTranslatable();
        }

        if (!$entity instanceof CmsRoutableInterface) {
            return;
        }

        if (!in_array($entity, $this->entitiesToManage, true)) {
            $this->entitiesToManage[] = $entity;
            $this->indexRoutes($entity, $event->getObjectManager());
        }
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $entity = $event->getObject();

        if ($entity instanceof TranslationInterface) {
            $entity = $entity->getTranslatable();
        }

        if (!$entity instanceof CmsRoutableInterface) {
            return;
        }

        if (!in_array($entity, $this->entitiesToManage, true)) {
            $this->entitiesToManage[] = $entity;
            $this->indexRoutes($entity, $event->getObjectManager());
        }
    }

    /**
     * The flush is over: an entity saved again in the same process (worker, command, test) gets its routes computed
     * again. The flushes the indexer runs itself end while it indexes and keep the list.
     */
    public function postFlush(PostFlushEventArgs $event): void
    {
        if (0 === $this->indexingDepth) {
            $this->entitiesToManage = [];
        }
    }

    protected function indexRoutes(CmsRoutableInterface $entity, ObjectManager $objectManager): void
    {
        ++$this->indexingDepth;

        try {
            $this->manageRoutes($entity, $objectManager);
        } finally {
            --$this->indexingDepth;
        }
    }

    /**
     * Computes (creates or updates) the routes of an entity outside of a flush, e.g. to index existing entities
     * (happycms:routes:index). The routes are persisted, not flushed: flush the entity manager afterwards. The routes
     * below its previous path are not rewritten.
     */
    public function index(CmsRoutableInterface $entity): void
    {
        /** @var array<string, string> $routesChanges */
        $routesChanges = [];

        $this->computeRoutes($routesChanges, $entity, self::ROUTE_ONLINE, $this->manager);
    }

    protected function manageRoutes(CmsRoutableInterface $entity, ObjectManager $objectManager): void
    {
        /** @var array<string, string> $routesChanges */
        $routesChanges = [];

        $this->computeRoutes($routesChanges, $entity, self::ROUTE_ONLINE, $objectManager);

        foreach ($entity->getRoutes() as $route) {
            $this->manager->persist($route);
        }
        $this->manager->flush();

        foreach ($routesChanges as $previousStaticPrefix => $staticPrefix) {
            $this->recomputeRoutesBelowStaticPrefix($staticPrefix, $previousStaticPrefix, $entity->getRoutes());
        }
    }

    //private function removeRoutes(CmsRoutableInterface &$entity, string $routeNamePrefix = ''): void
    //{
    //    $routesToRemove = $entity->getRoutes()->filter(
    //        static fn (RouteInterface $route) => str_starts_with($route->getName(), $routeNamePrefix),
    //    );
    //
    //    foreach ($routesToRemove as $route) {
    //        $entity->removeRoute($route);
    //    }
    //}

    /**
     * @param array<string, string> $routesChanges
     */
    protected function computeRoutes(
        array &$routesChanges,
        CmsRoutableInterface &$entity,
        string $routeNamePrefix,
        ObjectManager $objectManager,
    ): void {
        foreach ($entity->getTranslations() as $translation) {
            $routeName = $routeNamePrefix .
                $translation->getLocale() . '_' .
                $entity->getRouteUnikName()
            ;

            // Route exists ?
            $route = $entity->getRoutes()->filter(
                static fn (RouteInterface $route) => $route->getName() === $routeName,
            )->first();

            if (!($route instanceof RouteInterface)) {
                $routeClass = $this->parameterBag->get('cmf_routing.dynamic.persistence.orm.route_class');
                /**
                 * @var RouteInterface $route
                 */
                $route = new $routeClass();
                $route->setName($routeName);
            }

            $route->setMethods($entity->getRouteMethods());
            $route->setRequirements($entity->getRouteRequirements($translation));
            $route->setDefaults($entity->getRouteDefaults($translation));
            $route->setSchemes($entity->getRouteSchemes($translation));
            $route->setHost($entity->getRouteHost($translation));

            // Get previus static prefix
            $previousStaticPrefix = $route->getStaticPrefix();

            // Does the current entity is a child route of an other entity?
            // We dispath an event to give the possibility to add a static prefix before this one
            // Example: the current entity is linked to PostInterface (for a blog) and his static route is
            // "article-123"
            // and you want to prefix it with "blog" to have "slug-page/blog-slug/article-123"
            // slug "blog-slug" is a PageInterface entity slug
            // slug "slug-page is the PageInterface parent entity slug of "blog-slug"

            // Get specific parent slug (for other entities)
            $event = $this->dispatcher->dispatch(
                new CalculateRouteStaticPrefixEvent($entity, $translation),
            );

            // Set all complete path for the current entity route
            $route->setStaticPrefix($this->buildStaticPrefix(
                (string) $translation->getLocale(),
                $event->getRouteStaticPrefix(),
                $entity->getRouteStaticPrefix($translation),
            ));

            $route->setVariablePattern(
                $entity->getVariablePattern($translation),
            );

            $route->setLastModification(new \DateTime());
            $route->setDefault(RouteObjectInterface::CONTENT_ID, $this->contentRepository->getContentId($entity));

            $entity->addRoute($route);

            $this->manager->persist($route);
            $this->manager->persist($entity);

            $routesChanges[$previousStaticPrefix] = $route->getStaticPrefix();
        }
    }

    /**
     * Fills the URL pattern. With "sylius_happy_cms.routing.collapse_slashes", repeated slashes are then collapsed: a
     * placeholder may be empty (no parent, homepage). Without it, the path is kept as filled ("/en_US//about" when a
     * listener returns a path ending with "/"), with a leading "/" for a pattern that does not start with one.
     */
    protected function buildStaticPrefix(string $locale, string $calculatedStaticPrefix, string $currentEntityPath): string
    {
        $path = str_replace(
            ['{{locale}}', '{{calculated_static_prefix}}', '{{current_entity_path}}'],
            [$locale, $calculatedStaticPrefix, $currentEntityPath],
            $this->urlPattern,
        );

        if (!$this->collapseSlashes) {
            return str_starts_with($path, '/') ? $path : '/' . $path;
        }

        return '/' . ltrim((string) preg_replace('#/{2,}#', '/', $path), '/');
    }

    /**
     * Public entry point to move the routes below a path that is not a CMS route, e.g. the slug of a parent of
     * another type has changed (CmsRoutableInterface::getRouteStaticPrefix()). Call it outside of a Doctrine flush; the routes
     * are flushed. The routes of a routable content are recomputed (the parent of another type must already have its
     * new slug): $staticPrefix only moves the routes without routable content.
     */
    public function rewriteStaticPrefix(string $previousStaticPrefix, string $staticPrefix): void
    {
        $this->recomputeRoutesBelowStaticPrefix($staticPrefix, $previousStaticPrefix, new ArrayCollection());
    }

    /**
     * Recomputes the stored routes whose static prefix is the previous one or starts with it followed by "/": the
     * path of a route is computed again from its content (CmsRoutableInterface::getRouteStaticPrefix(),
     * CalculateRouteStaticPrefixEvent), as when the content is saved. A route below the previous path that
     * does not belong to the renamed entity (a content of another type with the same slug, and its children) keeps its
     * path. A route without routable content (or that is not an online route) has its leading prefix replaced.
     *
     * The descendants are all found by the prefix query: each content is recomputed once, without rewriting the
     * routes below its own path again.
     *
     * @param Collection<int, RouteInterface> $excludedRoutes
     */
    protected function recomputeRoutesBelowStaticPrefix(
        string $staticPrefix,
        string $previousStaticPrefix,
        Collection $excludedRoutes,
    ): void {
        $previousBase = rtrim($previousStaticPrefix, '/');
        // "" (new route) has no descendants; "/" is the prefix of every route
        if ('' === $previousBase || $previousStaticPrefix === $staticPrefix) {
            return;
        }
        $newBase = rtrim($staticPrefix, '/');

        $routesToUpdate = $this->findRoutesUnderStaticPrefix($previousBase);
        if ([] === $routesToUpdate) {
            return;
        }

        $this->manager->getConnection()->beginTransaction();
        /** @var array<int, true> $recomputedContents */
        $recomputedContents = [];
        foreach ($routesToUpdate as $routeToUpdate) {
            // skip the routes of the entity
            if ($excludedRoutes->contains($routeToUpdate) || !self::isUnderStaticPrefix($routeToUpdate->getStaticPrefix(), $previousBase)) {
                continue;
            }

            $content = $this->findRoutableContent($routeToUpdate);
            if (null !== $content) {
                if (!isset($recomputedContents[spl_object_id($content)])) {
                    $recomputedContents[spl_object_id($content)] = true;
                    $routesChanges = [];
                    $this->computeRoutes($routesChanges, $content, self::ROUTE_ONLINE, $this->manager);
                }

                continue;
            }

            // No routable content: move the leading prefix
            $rewritten = $newBase . substr($routeToUpdate->getStaticPrefix(), strlen($previousBase));
            $routeToUpdate->setStaticPrefix('' === $rewritten ? '/' : $rewritten);
            $this->manager->persist($routeToUpdate);
        }
        $this->manager->flush();
        $this->manager->getConnection()->commit();
    }

    /**
     * The routable content of an online route computed by this indexer, null for another route (no content, content
     * removed or not routable).
     */
    protected function findRoutableContent(RouteInterface $route): ?CmsRoutableInterface
    {
        if (!$route instanceof SymfonyRoute || !str_starts_with($route->getName(), self::ROUTE_ONLINE)) {
            return null;
        }

        $contentId = $route->getDefault(RouteObjectInterface::CONTENT_ID);
        if (!is_string($contentId) || '' === $contentId) {
            return null;
        }

        $content = $this->contentRepository->findById($contentId);

        return $content instanceof CmsRoutableInterface ? $content : null;
    }

    /**
     * @return RouteInterface[] the stored routes whose static prefix is $base or starts with "$base/"
     */
    protected function findRoutesUnderStaticPrefix(string $base): array
    {
        /** @var RouteInterface[] $routes */
        $routes = $this->manager->createQueryBuilder()
            ->select('route')
            ->from($this->getRouteClass(), 'route')
            ->where('route.staticPrefix = :base')
            ->orWhere("route.staticPrefix LIKE :descendants ESCAPE '!'")
            ->setParameter('base', $base)
            ->setParameter('descendants', str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $base) . '/%')
            ->getQuery()
            ->getResult()
        ;

        return $routes;
    }

    protected static function isUnderStaticPrefix(string $staticPrefix, string $base): bool
    {
        return $staticPrefix === $base || str_starts_with($staticPrefix, $base . '/');
    }

    /**
     * @return class-string<RouteInterface>
     */
    protected function getRouteClass(): string
    {
        /** @var class-string<RouteInterface> $routeClass */
        $routeClass = $this->parameterBag->get('cmf_routing.dynamic.persistence.orm.route_class');

        return $routeClass;
    }
}
