<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Route\CalculateRouteStaticPrefixEvent;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ObjectManager;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\ContentRepository;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityRouteIndexer
{
    public const ROUTE_PREVIEW = 'route_preview_';

    public const ROUTE_ONLINE = 'route_online_';

    public const OPTION_PREVIEW = 'preview_behavior';

    public array $entitiesToManage = [];

    public function __construct(
        protected ContentRepository $contentRepository,
        protected ParameterBag $parameterBag,
        protected EventDispatcherInterface $dispatcher,
        protected EntityManagerInterface $manager,
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

        if (!in_array($entity, $this->entitiesToManage)) {
            $this->entitiesToManage[] = $entity;
            $this->manageRoutes($entity, $event->getObjectManager());
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

        if (!in_array($entity, $this->entitiesToManage)) {
            $this->entitiesToManage[] = $entity;
            $this->manageRoutes($entity, $event->getObjectManager());
        }
    }

    private function manageRoutes(CmsRoutableInterface $entity, ObjectManager $objectManager): void
    {
        $routesChanges = [];

        if ($entity->isOnline()) {
            $this->computeRoutes($routesChanges, $entity, self::ROUTE_ONLINE, $objectManager);
        }

        if ($entity->previewIsAvailable()) {
            $this->computeRoutes($routesChanges, $entity, self::ROUTE_PREVIEW, $objectManager);
        }

        foreach ($entity->getRoutes() as $route) {
            $this->manager->persist($route);
        }
        $this->manager->flush();

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

    private function computeRoutes(
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

            $urlPattern = '/{{locale}}{{other_entity_path}}{{current_entity_path}}';

            // Get locale
            $locale = $translation->getLocale();

            // Get specific parent slug (for other entities)
            $event = $this->dispatcher->dispatch(
                new CalculateRouteStaticPrefixEvent($entity, $translation),
            );
            $otherEntityPath = $event->getRouteStaticPrefix();

            // Get current entity path
            $currentEntityPath = $entity->getRouteStaticPrefix($translation, $routeNamePrefix === self::ROUTE_PREVIEW);

            // Set all complete path for the current entity route
            $route->setStaticPrefix(
                str_replace(
                    [
                                '{{locale}}',
                                '{{other_entity_path}}',
                                '{{current_entity_path}}',
                            ],
                    [
                                $locale,
                                $otherEntityPath,
                                $currentEntityPath,
                            ],
                    $urlPattern,
                ),
            );

            $route->setVariablePattern(
                $entity->getVariablePattern($translation, $routeNamePrefix === self::ROUTE_PREVIEW),
            );

            $route->setLastModification(new \DateTime());
            $route->setPreview($routeNamePrefix === self::ROUTE_PREVIEW);
            $route->setOption(self::OPTION_PREVIEW, $routeNamePrefix === self::ROUTE_PREVIEW);
            $route->setDefault(RouteObjectInterface::CONTENT_ID, $this->contentRepository->getContentId($entity));

            $entity->addRoute($route);

            $this->manager->persist($route);
            $this->manager->persist($entity);

            $routesChanges[$previousStaticPrefix] = $route->getStaticPrefix();
        }
    }

    /**
     * If an uri is modified, ex: /mapage-1 => /ma-page-1, we need to update all other routes starting with the
     * same prefix.
     * This method will find all routes that start with the previous static prefix and update them to use the new
     * TODO: replace findAll() with an optimized query to avoid loading all routes
     *
     * @param PersistentCollection<int, RouteInterface> $excludedRoutes
     */
    private function rewriteOtherStaticPrefix(
        string $staticPrefix,
        string $previousStaticPrefix,
        PersistentCollection $excludedRoutes,
    ): void {
        if ($previousStaticPrefix && $previousStaticPrefix !== $staticPrefix) {
            $allRoutes = $this->manager->getRepository(RouteInterface::class)->findAll();

            if (is_array($allRoutes) && count($allRoutes) > 0) {
                $routesToUpdate = array_filter($allRoutes, function (RouteInterface $route) use ($previousStaticPrefix) {
                    return str_starts_with($route->getStaticPrefix(), $previousStaticPrefix);
                });

                $this->manager->getConnection()->beginTransaction();
                foreach ($routesToUpdate as $routeToUpdate) {
                    if ($excludedRoutes->contains($routeToUpdate)) {
                        continue;
                    }
                    // Update the static prefix of the route
                    $routeToUpdate->setStaticPrefix(
                        str_replace($previousStaticPrefix, $staticPrefix, $routeToUpdate->getStaticPrefix()),
                    );
                    $this->manager->persist($routeToUpdate);
                }
                $this->manager->flush();
                $this->manager->getConnection()->commit();
            }
        }
    }
}
