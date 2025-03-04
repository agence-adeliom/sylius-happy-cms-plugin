<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\ContentRepository;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

#[AsDoctrineListener('postPersist')]
#[AsDoctrineListener('postUpdate')]
class EntityRouteIndexer
{
    public const ROUTE_PREVIEW = 'route_preview_';

    public const ROUTE_ONLINE = 'route_online_';

    public const OPTION_PREVIEW = 'preview_behavior';

    public function __construct(
        protected ContentRepository $contentRepository,
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

        if ($entity->isOnline()) {
            $this->manageRoutes($entity, self::ROUTE_ONLINE);
        }

        if ($entity->previewIsAvailable()) {
            $this->manageRoutes($entity, self::ROUTE_PREVIEW);
        }

        $event->getObjectManager()->persist($entity);
        $event->getObjectManager()->flush();
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

        if ($entity->isOnline()) {
            $this->manageRoutes($entity, self::ROUTE_ONLINE);
        }

        if ($entity->previewIsAvailable()) {
            $this->manageRoutes($entity, self::ROUTE_PREVIEW);
        }

        $event->getObjectManager()->persist($entity);
        $event->getObjectManager()->flush();
    }

    private function manageRoutes(CmsRoutableInterface &$entity, string $routeNamePrefix = ''): void
    {
        foreach ($entity->getTranslations() as $translation) {
            $routeName = $routeNamePrefix .
                $translation->getLocale() . '_' .
                $entity->getRouteUnikName()
            ;

            // Route exists ?
            $route = $entity->getRoutes()->filter(
                static fn (Route $route) => $route->getName() === $routeName,
            )->first();

            if (!($route instanceof Route)) {
                $route = new Route();
                $route->setName($routeName);
            }

            $route->setMethods($entity->getRouteMethods());
            $route->setRequirements($entity->getRouteRequirements($translation));
            $route->setDefaults($entity->getRouteDefaults($translation));
            $route->setSchemes($entity->getRouteSchemes($translation));
            $route->setHost($entity->getRouteHost($translation));
            $route->setStaticPrefix(
                $entity->getRouteStaticPrefix($translation, $routeNamePrefix === self::ROUTE_PREVIEW),
            );
            $route->setVariablePattern(
                $entity->getVariablePattern($translation, $routeNamePrefix === self::ROUTE_PREVIEW),
            );
            $route->setOption(self::OPTION_PREVIEW, $routeNamePrefix === self::ROUTE_PREVIEW);
            $route->setDefault(RouteObjectInterface::CONTENT_ID, $this->contentRepository->getContentId($entity));
            $entity->addRoute($route);
        }
    }
}
