<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Route\CalculateRouteStaticPrefixEvent;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteParentPathResolverInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ObjectManager;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\ContentRepository;
use Symfony\Cmf\Bundle\RoutingBundle\Model\Route as RouteModel;
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
     * Placeholders: {{locale}}, {{other_entity_path}} (path of a parent of another type, see
     * CalculateRouteStaticPrefixEvent) and {{current_entity_path}} (CmsRoutableInterface::getRouteStaticPrefix()).
     */
    public const DEFAULT_URL_PATTERN = '/{{locale}}{{other_entity_path}}{{current_entity_path}}';

    /**
     * Routable entities indexed during the current flush.
     *
     * @var CmsRoutableInterface[]
     */
    public array $entitiesToManage = [];

    /**
     * True while the routes are computed inside a flush (onFlush): changes are then registered in the unit of work
     * instead of being persisted for a later flush.
     */
    protected bool $insideFlush = false;

    /**
     * Routes created for entities without identifier yet (inserted in the current flush), by entity: their name and
     * content id are completed in postPersist, once the identifier is known.
     *
     * @var array<int, array{entity: CmsRoutableInterface, routes: array<int, array{route: RouteInterface, namePrefix: string}>}>
     */
    protected array $routesAwaitingIdentifier = [];

    /**
     * @param iterable<RouteParentPathResolverInterface> $parentPathResolvers
     */
    public function __construct(
        protected ContentRepository $contentRepository,
        protected ParameterBag $parameterBag,
        protected EventDispatcherInterface $dispatcher,
        protected EntityManagerInterface $manager,
        protected string $urlPattern = self::DEFAULT_URL_PATTERN,
        protected bool $trailingSlash = false,
        protected iterable $parentPathResolvers = [],
        protected bool $rewriteChildRoutes = true,
    ) {
    }

    /**
     * Computes the routes of the routable entities (or of the translatable of the translations) inserted or updated
     * by the current flush, and registers the route changes in the same unit of work: no flush() inside the flush.
     */
    public function onFlush(OnFlushEventArgs $event): void
    {
        $unitOfWork = $this->manager->getUnitOfWork();
        $this->entitiesToManage = [];
        $this->insideFlush = true;

        try {
            foreach ([...$unitOfWork->getScheduledEntityInsertions(), ...$unitOfWork->getScheduledEntityUpdates()] as $object) {
                $entity = $object instanceof TranslationInterface ? $object->getTranslatable() : $object;
                if (!$entity instanceof CmsRoutableInterface || in_array($entity, $this->entitiesToManage, true)) {
                    continue;
                }

                $this->entitiesToManage[] = $entity;
                $this->manageRoutes($entity, $this->manager);
            }
        } finally {
            $this->insideFlush = false;
        }
    }

    /**
     * Completes the routes of an entity inserted by the current flush, now that its identifier is known: the
     * route name and content id are written by an extra update of the same flush.
     */
    public function postPersist(PostPersistEventArgs $event): void
    {
        $oid = spl_object_id($event->getObject());
        if (!isset($this->routesAwaitingIdentifier[$oid])) {
            return;
        }

        ['entity' => $entity, 'routes' => $routes] = $this->routesAwaitingIdentifier[$oid];
        unset($this->routesAwaitingIdentifier[$oid]);
        $unitOfWork = $this->manager->getUnitOfWork();

        foreach ($routes as ['route' => $route, 'namePrefix' => $namePrefix]) {
            if (!$route instanceof SymfonyRoute) {
                continue;
            }
            $name = $route->getName();
            $defaults = $route->getDefaults();
            $route->setName($namePrefix . $entity->getRouteUnikName());
            $route->setDefault(RouteObjectInterface::CONTENT_ID, $this->contentRepository->getContentId($entity));

            $unitOfWork->scheduleExtraUpdate($route, [
                'name' => [$name, $route->getName()],
                'defaults' => [$defaults, $route->getDefaults()],
            ]);
            $unitOfWork->setOriginalEntityProperty(spl_object_id($route), 'name', $route->getName());
            $unitOfWork->setOriginalEntityProperty(spl_object_id($route), 'defaults', $route->getDefaults());
        }
    }

    /**
     * Routes are computed in onFlush since 2.3.x: kept for service definitions still listening to postUpdate.
     */
    public function postUpdate(PostUpdateEventArgs $event): void
    {
    }

    /**
     * Computes (creates or updates) the routes of an entity outside of a flush, e.g. after a change of a parent of
     * another type, or to index existing entities. The routes are persisted: flush the entity manager afterwards.
     */
    public function index(CmsRoutableInterface $entity): void
    {
        $this->manageRoutes($entity, $this->manager);
    }

    protected function manageRoutes(CmsRoutableInterface $entity, ObjectManager $objectManager): void
    {
        /** @var array<string, string> $routesChanges */
        $routesChanges = [];

        $this->computeRoutes($routesChanges, $entity, self::ROUTE_ONLINE, $objectManager);

        // "sylius_happy_cms.routing.rewrite_child_routes: false": a stored path below the previous one may belong to
        // a content of another type with the same slug (its children would be moved with this entity)
        if (!$this->rewriteChildRoutes) {
            return;
        }

        foreach ($routesChanges as $previousStaticPrefix => $staticPrefix) {
            $this->rewriteOtherStaticPrefix($staticPrefix, $previousStaticPrefix, $entity->getRoutes());
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

            // Get specific parent slug (for other entities): resolvers first, then listeners
            $event = new CalculateRouteStaticPrefixEvent($entity, $translation);
            $event->setRouteStaticPrefix($this->resolveParentPath($entity, $translation));
            $event = $this->dispatcher->dispatch($event);

            // Set all complete path for the current entity route
            $route->setStaticPrefix($this->buildStaticPrefix(
                (string) $translation->getLocale(),
                $event->getRouteStaticPrefix(),
                $entity->getRouteStaticPrefix($translation),
            ));

            $route->setVariablePattern(
                $entity->getVariablePattern($translation),
            );

            if ($this->trailingSlash) {
                $this->addTrailingSlash($route);
            }

            $route->setLastModification(new \DateTime());
            $contentId = $this->contentRepository->getContentId($entity);
            $route->setDefault(RouteObjectInterface::CONTENT_ID, $contentId);
            if (null === $contentId) {
                // Entity inserted by the current flush: unique temporary name, completed in postPersist
                $route->setName($routeName . '_' . bin2hex(random_bytes(8)));
                $this->routesAwaitingIdentifier[spl_object_id($entity)]['entity'] = $entity;
                $this->routesAwaitingIdentifier[spl_object_id($entity)]['routes'][] = [
                    'route' => $route,
                    'namePrefix' => $routeNamePrefix . $translation->getLocale() . '_',
                ];
            }

            $entity->addRoute($route);
            $this->saveRoute($route);

            $routesChanges[$previousStaticPrefix] = $route->getStaticPrefix();
        }

        $this->saveEntity($entity);
    }

    /**
     * Inside a flush, registers the new or changed route in the unit of work; outside, persists it.
     */
    protected function saveRoute(RouteInterface $route): void
    {
        if (!$this->insideFlush) {
            $this->manager->persist($route);

            return;
        }

        $unitOfWork = $this->manager->getUnitOfWork();
        $metadata = $this->manager->getClassMetadata($route::class);
        if (UnitOfWork::STATE_NEW === $unitOfWork->getEntityState($route)) {
            $this->manager->persist($route);
            $unitOfWork->computeChangeSet($metadata, $route);

            return;
        }

        $unitOfWork->recomputeSingleEntityChangeSet($metadata, $route);
    }

    /**
     * Inside a flush, takes the route collection changes of the entity into account; outside, persists it.
     */
    protected function saveEntity(CmsRoutableInterface $entity): void
    {
        if (!$this->insideFlush) {
            $this->manager->persist($entity);

            return;
        }

        $this->manager->getUnitOfWork()->computeChangeSet($this->manager->getClassMetadata($entity::class), $entity);
    }

    protected function resolveParentPath(CmsRoutableInterface $entity, TranslationInterface $translation): string
    {
        foreach ($this->parentPathResolvers as $resolver) {
            if ($resolver->supports($entity, $translation)) {
                return $resolver->resolveParentPath($entity, $translation);
            }
        }

        return '';
    }

    /**
     * Fills the URL pattern, then collapses repeated slashes: a placeholder may be empty (no parent, homepage).
     */
    protected function buildStaticPrefix(string $locale, string $otherEntityPath, string $currentEntityPath): string
    {
        $path = str_replace(
            ['{{locale}}', '{{other_entity_path}}', '{{current_entity_path}}'],
            [$locale, $otherEntityPath, $currentEntityPath],
            $this->urlPattern,
        );

        return '/' . ltrim((string) preg_replace('#/{2,}#', '/', $path), '/');
    }

    /**
     * Ends the route path with "/": the static prefix when there is no variable part, the variable pattern otherwise.
     */
    protected function addTrailingSlash(RouteInterface $route): void
    {
        $variablePattern = $route instanceof RouteModel ? $route->getVariablePattern() : '';
        if ('' === $variablePattern) {
            $route->setStaticPrefix(rtrim($route->getStaticPrefix(), '/') . '/');

            return;
        }

        $route->setVariablePattern(rtrim($variablePattern, '/') . '/');
    }

    /**
     * If an uri is modified, ex: /mapage-1 => /ma-page-1, we need to update all other routes starting with the
     * same prefix: the route with exactly the previous prefix and the routes below it ("/mapage-1/…"), never a
     * sibling sharing the first characters ("/mapage-12"). Only the leading prefix is replaced.
     *
     * @param Collection<int, RouteInterface> $excludedRoutes
     */
    protected function rewriteOtherStaticPrefix(
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

        foreach ($this->findRoutesUnderStaticPrefix($previousBase) as $routeToUpdate) {
            // skip the routes of the entity, and routes already recomputed in memory during this flush
            if ($excludedRoutes->contains($routeToUpdate) || !self::isUnderStaticPrefix($routeToUpdate->getStaticPrefix(), $previousBase)) {
                continue;
            }
            // Update the static prefix of the route
            $rewritten = $newBase . substr($routeToUpdate->getStaticPrefix(), strlen($previousBase));
            $routeToUpdate->setStaticPrefix('' === $rewritten ? '/' : $rewritten);
            $this->saveRoute($routeToUpdate);
        }
    }

    /**
     * Public entry point to move the routes below a path that is not a CMS route, e.g. the slug of a parent of
     * another type has changed (see RouteParentPathResolverInterface). Flush the entity manager afterwards.
     * Explicit call: not affected by "sylius_happy_cms.routing.rewrite_child_routes".
     */
    public function rewriteStaticPrefix(string $previousStaticPrefix, string $staticPrefix): void
    {
        $this->rewriteOtherStaticPrefix($staticPrefix, $previousStaticPrefix, new ArrayCollection());
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
