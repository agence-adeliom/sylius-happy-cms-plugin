<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Route\CalculateRouteStaticPrefixEvent;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
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

    /**
     * Placeholders: {{locale}}, {{other_entity_path}} (path of a parent of another type, see
     * CalculateRouteStaticPrefixEvent) and {{current_entity_path}} (CmsRoutableInterface::getRouteStaticPrefix()).
     */
    public const DEFAULT_URL_PATTERN = '/{{locale}}{{other_entity_path}}{{current_entity_path}}';

    public array $entitiesToManage = [];

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
    protected function buildStaticPrefix(string $locale, string $otherEntityPath, string $currentEntityPath): string
    {
        $path = str_replace(
            ['{{locale}}', '{{other_entity_path}}', '{{current_entity_path}}'],
            [$locale, $otherEntityPath, $currentEntityPath],
            $this->urlPattern,
        );

        if (!$this->collapseSlashes) {
            return str_starts_with($path, '/') ? $path : '/' . $path;
        }

        return '/' . ltrim((string) preg_replace('#/{2,}#', '/', $path), '/');
    }

    /**
     * If an uri is modified, ex: /mapage-1 => /ma-page-1, we need to update all other routes starting with the
     * same prefix.
     * This method will find all routes that start with the previous static prefix and update them to use the new
     * TODO: replace findAll() with an optimized query to avoid loading all routes
     *
     * @param Collection<int, RouteInterface> $excludedRoutes
     */
    protected function rewriteOtherStaticPrefix(
        string $staticPrefix,
        string $previousStaticPrefix,
        Collection $excludedRoutes,
    ): void {
        if ($previousStaticPrefix && $previousStaticPrefix !== $staticPrefix) {
            $allRoutes = $this->manager->getRepository(RouteInterface::class)->findAll();

            if (count($allRoutes) > 0) {
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
