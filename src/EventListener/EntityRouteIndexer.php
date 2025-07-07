<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\ContentRepository;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

#[AsDoctrineListener('postPersist')]
#[AsDoctrineListener('postUpdate')]
class EntityRouteIndexer
{
    public const ROUTE_PREVIEW = 'route_preview_';

    public const ROUTE_ONLINE = 'route_online_';

    public const OPTION_PREVIEW = 'preview_behavior';

    public const OPTION_LAST_MODIFICATION_TIMESTAMP = 'last_modification_timestamp';

    public function __construct(
        protected ContentRepository $contentRepository,
        protected ParameterBag $parameterBag,
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
            $this->computeRoutes($entity, self::ROUTE_ONLINE);
        }

        if ($entity->previewIsAvailable()) {
            $this->computeRoutes($entity, self::ROUTE_PREVIEW);
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
            $this->computeRoutes($entity, self::ROUTE_ONLINE);
        }
        // TODO : check si ça marche et si nécessaire
        //else {
        //    $this->removeRoutes($entity, self::ROUTE_ONLINE);
        //}

        if ($entity->previewIsAvailable()) {
            $this->computeRoutes($entity, self::ROUTE_PREVIEW);
        }

        $event->getObjectManager()->persist($entity);
        $event->getObjectManager()->flush();
    }

    private function removeRoutes(CmsRoutableInterface &$entity, string $routeNamePrefix = ''): void
    {
        $routesToRemove = $entity->getRoutes()->filter(
            static fn (RouteInterface $route) => str_starts_with($route->getName(), $routeNamePrefix),
        );

        foreach ($routesToRemove as $route) {
            $entity->removeRoute($route);
        }
    }

    private function computeRoutes(CmsRoutableInterface &$entity, string $routeNamePrefix = ''): void
    {
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
            $route->setStaticPrefix(
                $entity->getRouteStaticPrefix($translation, $routeNamePrefix === self::ROUTE_PREVIEW),
            );
            $route->setVariablePattern(
                $entity->getVariablePattern($translation, $routeNamePrefix === self::ROUTE_PREVIEW),
            );
            $route->setOption(self::OPTION_PREVIEW, $routeNamePrefix === self::ROUTE_PREVIEW);
            $route->setOption(
                self::OPTION_LAST_MODIFICATION_TIMESTAMP,
                (new \DateTimeImmutable('now'))->getTimestamp(),
            );
            $route->setDefault(RouteObjectInterface::CONTENT_ID, $this->contentRepository->getContentId($entity));
            $entity->addRoute($route);
        }
    }
}
